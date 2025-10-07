<?php

namespace App\Services\Chat;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;

class MockChatService implements ChatServiceInterface
{
    private function key(string $userId): string
    {
        return "chat:users:$userId";
    }

    public function history(string $userId): array
    {
        return Cache::get($this->key($userId), []);
    }

    public function storeUserMessage(string $userId, string $content, ?string $customer = null): void
    {
        $all = $this->history($userId);
        $all[] = [
            'role'     => 'user',
            'content'  => $content,
            'ts'       => now()->toIso8601String(),
            'customer' => $customer,
        ];
        Cache::put($this->key($userId), $all, now()->addHours(12));
    }

    public function reply(string $userId, string $lastUserMessage, ?string $customer = null): string
    {
        $reply = "Mock: ho ricevuto «{$lastUserMessage}». Presto risponderà l’AI 🤖";
        $all = $this->history($userId);
        $all[] = [
            'role'     => 'assistant',
            'content'  => $reply,
            'ts'       => now()->toIso8601String(),
            'customer' => $customer,
        ];
        Cache::put($this->key($userId), $all, now()->addHours(12));
        return $reply;
    }
}
