<?php

namespace App\Services\Chat\MockChatService;

use App\Models\User;

final class UserOps
{
    public static function findByPhone(?string $normalizedPhone): ?User
    {
        if (!$normalizedPhone) return null;
        return User::where('phone', $normalizedPhone)->first();
    }

    public static function createMinimal(string $phone, string $piva): User
    {
        return User::create([
            'phone'     => $phone,
            'piva'      => $piva,
            'role'      => \App\Models\User::ROLE_USER,
            'fornitori' => [],
        ]);
    }

    public static function addSupplierVat(string $phone, string $supplierVat): void
    {
        $db        = \DB::connection('mongodb')->getMongoDB();
        $usersColl = $db->selectCollection('users');

        $user    = self::findByPhone($phone);
        $initial = [];
        if ($user) {
            $cur = $user->fornitori ?? null;
            if (is_array($cur)) $initial = $cur;
            elseif (is_string($cur) && trim($cur) !== '') $initial = [trim($cur)];
        }

        $usersColl->updateOne(['phone' => $phone], [
            '$set' => ['fornitori' => array_values(array_unique($initial))],
        ]);

        $usersColl->updateOne(['phone' => $phone], [
            '$addToSet' => ['fornitori' => (string) $supplierVat],
        ]);
    }
}
