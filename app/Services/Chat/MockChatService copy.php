<?php

namespace App\Services\Chat;

use App\Services\Chat\MockChatService\Phone;
use App\Services\Chat\MockChatService\State;
use App\Services\Chat\MockChatService\StoryWriter;
use App\Services\Chat\MockChatService\UserOps;
use App\Services\Chat\MockChatService\Replies;

class MockChatService implements ChatServiceInterface
{
    public function history(string $userId): array
    {
        return Replies::history($userId);
    }

    public function storeUserMessage(string $userId, string $content, ?string $customer = null): void
    {
        $phone = Phone::normalize($customer);
        $all   = Replies::history($userId);

        $msg = [
            'role'     => 'user',
            'content'  => $content,
            'ts'       => now()->toIso8601String(),
            'customer' => $phone,
            'user_id'  => $userId,
        ];

        $all[] = $msg;
        Replies::putHistory($userId, $all);
        StoryWriter::write($phone, $msg);
    }

    public function reply(string $userId, string $lastUserMessage, ?string $customer = null): string
    {
        $state    = State::get($userId, 'idle');
        $customer = Phone::normalize($customer);

        $user  = $customer ? UserOps::findByPhone($customer) : null;
        $say   = fn(string $text) => Replies::say($userId, $customer, $text);

        $msg      = trim($lastUserMessage ?? '');
        $vatRegex = '/^\d{11}$/';
        $isYes    = fn(string $t) => (bool)preg_match('/^(s[iì]|si|sì|ok|va bene|yes|yep|yeah|y|s|k|kk)$/i', trim($t));
        $isNo     = fn(string $t) => (bool)preg_match('/^(no|n|nope|nah|eh no|no no|nono)$/i', trim($t));

        // A) nessun utente
        if (!$user && $state === 'idle') {
            State::put($userId, 'awaiting_piva');
            return $say("Non trovo un utente associato a **{$customer}**.\nPer registrarti, inviami la tua P.IVA (solo numeri, senza IT).");
        }

        // B) attesa P.IVA utente
        if ($state === 'awaiting_piva') {
            if (!preg_match($vatRegex, $msg)) {
                return $say("La P.IVA non sembra valida. Invia **11 cifre** senza spazi e senza IT.");
            }
            UserOps::createMinimal($customer, $msg);
            State::put($userId, 'confirm_identity');
            return $say("Registrato nuovo utente con P.IVA **{$msg}**.\nConfermi che è tutto corretto? (sì/no)");
        }

        // C) conferma dati
        if ($state === 'confirm_identity') {
            if ($isYes($msg)) {
                State::put($userId, 'awaiting_supplier_piva');
                return $say("Perfetto! Dimmi ora la **P.IVA dell’azienda** a cui desideri effettuare un ordine.");
            }
            if ($isNo($msg)) {
                State::put($userId, 'awaiting_piva');
                return $say("Ok, reinviami la P.IVA (11 cifre).");
            }
            return $say("Rispondi **sì** o **no**, grazie.");
        }

        // D) in attesa P.IVA fornitore
        if ($state === 'awaiting_supplier_piva') {
            if (preg_match($vatRegex, $msg)) {
                try {
                    UserOps::addSupplierVat($customer, $msg);
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

            if ($isYes($msg)) {
                return $say("Perfetto! Inviami la **P.IVA (11 cifre)** del nuovo fornitore.");
            }

            // tutto il resto → NO
            State::put($userId, 'ready_for_order');
            return $say("Perfetto. Dimmi pure **cosa vuoi ordinare** o come posso aiutarti.");
        }

        // E) utente esistente (idle)
        if ($user && $state === 'idle') {
            if (empty($user->fornitori) || !is_array($user->fornitori)) {
                State::put($userId, 'awaiting_supplier_piva');
                return $say("Ciao! Dimmi la **P.IVA dell’azienda** a cui desideri effettuare un ordine.");
            }
            return $say("Ok! Dimmi pure cosa vuoi fare (nuovo ordine, stato ordini, listino, ecc.).");
        }

        // fallback
        return $say("Ricevuto: «{$msg}». (mock) Dimmi pure come posso aiutarti.");
    }
}
