<?php

namespace App\Services\Chat\MockChatService;

use MongoDB\BSON\UTCDateTime;

final class StoryWriter
{
    public static function storyCollection(?string $phone)
    {
        if (!$phone) return null;
        $db = \DB::connection('mongodb')->getMongoDB();
        return $db->selectCollection("{$phone}_story");
    }

    public static function write(?string $phone, array $doc): void
    {
        $coll = self::storyCollection($phone);
        if (!$coll) return;

        $now = now();
        $doc['ts']         = $doc['ts'] ?? $now->toIso8601String();
        $doc['created_at'] = new UTCDateTime($now->valueOf());

        $coll->insertOne($doc);
    }
}
