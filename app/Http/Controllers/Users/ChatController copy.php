<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Chat\ChatServiceInterface;

class ChatController extends Controller
{
    public function __construct(private ChatServiceInterface $chat) {}

    public function index(Request $request)
    {
        // Messaggi iniziali (mockati dal service)
        $messages = $this->chat->history(userId: (string)auth()->id());

        return view('Users.chat.index', [
            'messages' => $messages,
        ]);
    }

    public function send(Request $request)
    {
        $text = trim((string) $request->input('message', ''));
        if ($text === '') {
            return response()->json(['error' => 'Message is empty'], 422);
        }

        $userId = (string) auth()->id();

        // Salva il messaggio utente nello storico
        $this->chat->storeUserMessage($userId, $text);

        // Ottieni la risposta mock (poi diventerà GPT)
        $assistantText = $this->chat->reply($userId, $text);

        return response()->json([
            'message' => [
                'role'    => 'assistant',
                'content' => $assistantText,
                'ts'      => now()->tz(config('app.timezone'))->toISOString(),
            ],
        ], 200);
    }

}
