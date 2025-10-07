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
        // normalizza il numero a sole cifre (evita +39, spazi, ecc.)
        $customer = preg_replace('/\D+/', '', (string)($request->input('customer') ?? $request->session()->get('chat.customer', '')));

        if ($text === '') {
            return response()->json(['error' => 'Message is empty'], 422);
        }
        if ($customer === '') {
            return response()->json(['error' => 'Customer (phone) is required'], 422);
        }

        // persisti il customer in sessione (prima volta)
        $request->session()->put('chat.customer', $customer);

        $userId = (string)auth()->id();

        try {
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

        } catch (\Throwable $e) {
            \Log::error('chat.send.failed', [
                'user_id'  => $userId,
                'customer' => $customer,
                'error'    => $e->getMessage(),
            ]);
            // evita che il client veda “Errore di rete” generico senza dettagli utili
            return response()->json(['error' => 'Server error'], 500);
        }
    }


}
