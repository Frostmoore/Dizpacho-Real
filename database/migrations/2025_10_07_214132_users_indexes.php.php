<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $db  = DB::connection('mongodb')->getMongoDB();
        $col = $db->selectCollection('users');

        // Leggi indici esistenti
        $existing = [];
        foreach ($col->listIndexes() as $ix) {
            // $ix->getName() disponibile sulle versioni recenti, fallback su ['name']
            $name = method_exists($ix, 'getName') ? $ix->getName() : ($ix['name'] ?? null);
            if ($name) $existing[$name] = $ix;
        }

        // Se vuoi mantenere quello già presente su phone, NON fare nulla qui.
        // if (!array_key_exists('users_phone_unique', $existing)) { ... }  // opzionale

        // Crea indice su piva se non già presente
        $hasPiva = false;
        foreach ($existing as $ix) {
            $keys = $ix['key'] ?? [];
            if (isset($keys['piva'])) { $hasPiva = true; break; }
        }
        if (!$hasPiva) {
            $col->createIndex(['piva' => 1], ['name' => 'idx_piva', 'background' => true]);
        }

        // Crea indice su fornitori (array) se non già presente
        $hasFornitori = false;
        foreach ($existing as $ix) {
            $keys = $ix['key'] ?? [];
            if (isset($keys['fornitori'])) { $hasFornitori = true; break; }
        }
        if (!$hasFornitori) {
            $col->createIndex(['fornitori' => 1], ['name' => 'idx_fornitori', 'background' => true]);
        }
    }

    public function down(): void
    {
        $db  = DB::connection('mongodb')->getMongoDB();
        $col = $db->selectCollection('users');

        // Droppa SOLO quelli che abbiamo creato noi
        try { $col->dropIndex('idx_piva'); } catch (\Throwable $e) {}
        try { $col->dropIndex('idx_fornitori'); } catch (\Throwable $e) {}
        // NON tocchiamo l’indice esistente su phone
    }
};
