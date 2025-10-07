<?php

namespace App\Services\Chat\MockChatService;

use Illuminate\Support\Facades\Cache;

final class State
{
    public static function key(string $userId): string { return "chat:state:$userId"; }

    public static function get(string $userId, string $default = 'idle'): string
    {
        return Cache::get(self::key($userId), $default);
    }

    public static function put(string $userId, string $value, int $hours = 12): void
    {
        Cache::put(self::key($userId), $value, now()->addHours($hours));
    }
}
