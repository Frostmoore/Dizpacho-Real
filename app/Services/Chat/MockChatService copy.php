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
        return Cache::get($this->key($userId), function () use ($userId) {
            $seed = [
                ['role' => 'system', 'content' => 'Benvenuto in Dizpacho Chat 👋', 'ts' => Carbon::now()->tz(config('app.timezone'))->toISOString()],
                ['role' => 'assistant', 'content' => 'Sono qui per aiutarti con ordini e listini.', 'ts' => Carbon::now()->tz(config('app.timezone'))->toISOString()],
            ];
            Cache::put($this->key($userId), $seed, now()->tz(config('app.timezone'))->toISOString());
            return $seed;
        });
    }

    public function storeUserMessage(string $userId, string $content): void
    {
        $all = $this->history($userId);
        $all[] = ['role' => 'user', 'content' => $content, 'ts' => now()->tz(config('app.timezone'))->toISOString()];
        Cache::put($this->key($userId), $all, now()->tz(config('app.timezone'))->toISOString());
    }

    public function reply(string $userId, string $lastUserMessage): string
    {
        $reply = "Mock - ho ricevuto: \n---\n{$lastUserMessage}\n---\nPresto risponderà l’AI 🤖";
        $all = $this->history($userId);
        $all[] = ['role' => 'assistant', 'content' => $reply, 'ts' => now()->tz(config('app.timezone'))->toISOString()];
        Cache::put($this->key($userId), $all, now()->tz(config('app.timezone'))->toISOString());
        return $reply;
    }
}
