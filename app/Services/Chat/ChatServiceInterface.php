<?php

namespace App\Services\Chat;

interface ChatServiceInterface
{
    /** @return array<int, array{role:string, content:string, ts:string, customer?:string}> */
    public function history(string $userId): array;

    public function storeUserMessage(string $userId, string $content, ?string $customer = null): void;

    public function reply(string $userId, string $lastUserMessage, ?string $customer = null): string;
}

