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
        $messages = $this->chat->history((string)auth()->id());

        return view('Users.chat.index', [
            'messages' => $messages,
            'customer' => $request->session()->get('chat.customer'), // <—
        ]);
    }

    public function send(Request $request)
    {
        $text = trim((string)$request->input('message', ''));
        $customer = (string)($request->input('customer') ?? $request->session()->get('chat.customer', ''));

        if ($text === '') {
            return response()->json(['error' => 'Message is empty'], 422);
        }
        if ($customer === '') {
            return response()->json(['error' => 'Customer (phone) is required'], 422);
        }

        // persisti il customer in sessione (prima volta)
        $request->session()->put('chat.customer', $customer);

        $userId = (string)auth()->id();

        // salva il messaggio utente nello storico
        $this->chat->storeUserMessage($userId, $text, $customer);

        // reply mock
        $assistantText = $this->chat->reply($userId, $text, $customer);

        return response()->json([
            'message' => [
                'role'     => 'assistant',
                'content'  => $assistantText,
                'ts'       => now()->toIso8601String(),
                'customer' => $customer,
            ],
        ], 200);
    }

}
