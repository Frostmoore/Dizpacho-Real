<?php

namespace App\Services\Chat;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;
use App\Models\User;
use MongoDB\BSON\UTCDateTime;


class MockChatService implements ChatServiceInterface
{
    private function key(string $userId): string { return "chat:users:$userId"; }
    private function stateKey(string $userId): string { return "chat:state:$userId"; }

    /** Normalizza telefono a sole cifre (es. "+39 333-123..." -> "39333123...") */
    private function normalizePhone(?string $raw): ?string
    {
        if ($raw === null) return null;
        $n = preg_replace('/\D+/', '', $raw);
        return $n !== '' ? $n : null;
    }

    public function history(string $userId): array
    {
        return Cache::get($this->key($userId), []);
    }

    public function storeUserMessage(string $userId, string $content, ?string $customer = null): void
    {
        $phone = $this->normalizePhone($customer);
        $all   = $this->history($userId);

        $msg = [
            'role'     => 'user',
            'content'  => $content,
            'ts'       => now()->toIso8601String(),
            'customer' => $phone,
            'user_id'  => $userId,
        ];

        $all[] = $msg;
        Cache::put($this->key($userId), $all, now()->addHours(12));

        // ⬇️ salva nella storia per numero
        $this->writeStory($phone, $msg);
    }


    public function reply(string $userId, string $lastUserMessage, ?string $customer = null): string
    {
        // stato corrente
        $state = Cache::get($this->stateKey($userId), 'idle');

        // normalizza telefono ovunque
        $customer = $this->normalizePhone($customer);

        // tenta lookup utente su telefono normalizzato
        $user = null;
        if ($customer) {
            $user = User::where('phone', $customer)->first();
        }

        // helper risposta (scrive in history già con phone normalizzato)
        $say = function (string $text) use ($userId, $customer) {
            $all   = $this->history($userId);
            $nowIso = now()->toIso8601String();

            $msg = [
                'role'     => 'assistant',
                'content'  => $text,
                'ts'       => $nowIso,
                'customer' => $customer,
                'user_id'  => $userId,
            ];

            $all[] = $msg;
            Cache::put($this->key($userId), $all, now()->addHours(12));

            // ⬇️ salva anche la risposta del bot nella storia
            $this->writeStory($customer, $msg);

            return $text;
        };

        $msg = trim($lastUserMessage ?? '');
        $vatRegex = '/^\d{11}$/';
        $isYes = fn(string $t) => (bool)preg_match('/^(s[iì]|si|sì|ok|va bene|yes|yep|yeah|y|s|k|kk)$/i', trim($t));
        $isNo  = fn(string $t) => (bool)preg_match('/^(no|n|nope|nah|eh no|no no|nono)$/i', trim($t));

        // A) nessun utente trovato
        if (!$user && $state === 'idle') {
            Cache::put($this->stateKey($userId), 'awaiting_piva', now()->addHours(12));
            return $say("Non trovo un utente associato a **{$customer}**.\nPer registrarti, inviami la tua P.IVA (solo numeri, senza IT).");
        }

        // B) attesa P.IVA utente (registrazione)
        if ($state === 'awaiting_piva') {
            if (!preg_match($vatRegex, $msg)) {
                return $say("La P.IVA non sembra valida. Invia **11 cifre** senza spazi e senza IT.");
            }

            // crea utente minimale con telefono NORMALIZZATO
            $user = User::create([
                'phone'     => $customer,      // <-- normalizzato
                'piva'      => $msg,
                'role'      => \App\Models\User::ROLE_USER,
                'fornitori' => [],
            ]);

            Cache::put($this->stateKey($userId), 'confirm_identity', now()->addHours(12));
            return $say("Registrato nuovo utente con P.IVA **{$msg}**.\nConfermi che è tutto corretto? (sì/no)");
        }

        // C) conferma dati
        if ($state === 'confirm_identity') {
            if ($isYes($msg)) {
                Cache::put($this->stateKey($userId), 'awaiting_supplier_piva', now()->addHours(12));
                return $say("Perfetto! Dimmi ora la **P.IVA dell’azienda** a cui desideri effettuare un ordine.");
            }
            if ($isNo($msg)) {
                Cache::put($this->stateKey($userId), 'awaiting_piva', now()->addHours(12));
                return $say("Ok, reinviami la P.IVA (11 cifre).");
            }
            return $say("Rispondi **sì** o **no**, grazie.");
        }

        // D) attesa P.IVA fornitore
        if ($state === 'awaiting_supplier_piva') {
            if (!preg_match($vatRegex, $msg)) {
                return $say("La P.IVA del fornitore non è valida. Invia **11 cifre**.");
            }

            $user = User::where('phone', $customer)->first();
            if (!$user) {
                Cache::put($this->stateKey($userId), 'awaiting_piva', now()->addHours(12));
                return $say("Non trovo più l’utente associato a {$customer}. Inviami la tua P.IVA (11 cifre).");
            }

            try {
                // usa driver nativo per addToSet
                $db        = \DB::connection('mongodb')->getMongoDB();
                $usersColl = $db->selectCollection('users');

                $filter   = ['phone' => $customer]; // telefono normalizzato
                $current  = $user->fornitori ?? null;
                $initial  = [];

                if (is_array($current)) {
                    $initial = $current;
                } elseif (is_string($current) && trim($current) !== '') {
                    $initial = [trim($current)];
                }

                // 1) assicura array
                $usersColl->updateOne($filter, [
                    '$set' => ['fornitori' => array_values(array_unique($initial))],
                ]);

                // 2) aggiungi unico
                $usersColl->updateOne($filter, [
                    '$addToSet' => ['fornitori' => (string) $msg],
                ]);

                return $say("Fornitore **{$msg}** aggiunto ✅\nVuoi aggiungerne un altro? (sì/no)\nSe **no**, dimmi pure cosa vuoi ordinare.");

            } catch (\Throwable $e) {
                \Log::error('chat.fornitori.push_failed', [
                    'user_id'   => $userId,
                    'customer'  => $customer,
                    'piva_forn' => $msg,
                    'error'     => $e->getMessage(),
                ]);
                return $say("Ops, non sono riuscito a salvare il fornitore. Riproviamo tra un attimo?");
            }
        }

        // E) utente già esistente (idle)
        if ($user && $state === 'idle') {
            if (empty($user->fornitori) || !is_array($user->fornitori)) {
                Cache::put($this->stateKey($userId), 'awaiting_supplier_piva', now()->addHours(12));
                return $say("Ciao! Dimmi la **P.IVA dell’azienda** a cui desideri effettuare un ordine.");
            }
            return $say("Ok! Dimmi pure cosa vuoi fare (nuovo ordine, stato ordini, listino, ecc.).");
        }

        // fallback
        return $say("Ricevuto: «{$msg}». (mock) Dimmi pure come posso aiutarti.");
    }

    /** Restituisce la collection di storia per un dato telefono (già normalizzato) */
    private function storyCollection(?string $phone)
    {
        if (!$phone) return null;
        $db = \DB::connection('mongodb')->getMongoDB(); // driver nativo
        // phone è già numerico (normalizzato), quindi il nome è safe
        return $db->selectCollection("{$phone}_story");
    }

    /** Scrive un evento nella storia del cliente */
    private function writeStory(?string $phone, array $doc): void
    {
        $coll = $this->storyCollection($phone);
        if (!$coll) return;

        // garantisci i timestamp
        $now = now();
        $doc['ts'] = $doc['ts'] ?? $now->toIso8601String();
        $doc['created_at'] = new UTCDateTime($now->valueOf()); // ms since epoch

        $coll->insertOne($doc);
    }

}
