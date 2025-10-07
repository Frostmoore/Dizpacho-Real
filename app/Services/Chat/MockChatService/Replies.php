<?php

namespace App\Services\Chat\MockChatService;

use Illuminate\Support\Facades\Cache;

final class Replies
{
    public static function historyKey(string $userId): string { return "chat:users:$userId"; }

    public static function history(string $userId): array
    {
        return Cache::get(self::historyKey($userId), []);
    }

    public static function putHistory(string $userId, array $messages, int $hours = 12): void
    {
        Cache::put(self::historyKey($userId), $messages, now()->addHours($hours));
    }

    public static function say(string $userId, ?string $customer, string $text): string
    {
        $all = self::history($userId);
        $msg = [
            'role'     => 'assistant',
            'content'  => $text,
            'ts'       => now()->toIso8601String(),
            'customer' => $customer,
            'user_id'  => $userId,
        ];
        $all[] = $msg;
        self::putHistory($userId, $all);
        StoryWriter::write($customer, $msg);
        return $text;
    }
}
