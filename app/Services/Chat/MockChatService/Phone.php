<?php

namespace App\Services\Chat\MockChatService;

final class Phone
{
    public static function normalize(?string $raw): ?string
    {
        if ($raw === null) return null;
        $n = preg_replace('/\D+/', '', $raw);
        return $n !== '' ? $n : null;
    }
}
