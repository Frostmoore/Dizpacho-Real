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
        $customer = preg_replace('/\D+/', '', (string)($request->input('customer') ?? $request->session()->get('chat.customer', '')));

        if ($customer === '') {
            return response()->json(['error' => 'Customer (phone) is required'], 422);
        }

        // persisti il customer in sessione (prima volta)
        $request->session()->put('chat.customer', $customer);

        $userId = (string)auth()->id();

        // ---- Validazione media opzionale
        $hasFile = $request->hasFile('file');
        $extra   = [];

        if ($hasFile) {
            // immagini o audio
            $request->validate([
                'file' => 'required|file|max:20480' // 20MB (adatta)
            ]);

            $file = $request->file('file');

            // detect tipo
            $mime = $file->getMimeType() ?: '';
            $isImage = str_starts_with($mime, 'image/');
            $isAudio = str_starts_with($mime, 'audio/') || in_array($file->extension(), ['mp3','m4a','aac','wav','ogg','webm']);

            if (!$isImage && !$isAudio) {
                return response()->json(['error' => 'Tipo file non supportato'], 422);
            }

            // salva in: public/chat/{phone}/
            $dir = "chat/{$customer}";
            $path = $file->store($dir, 'public'); // richiede: php artisan storage:link
            $url  = asset("storage/{$path}");

            $extra = [
                'type' => $isImage ? 'image' : 'audio',
                'url'  => $url,
                'mime' => $mime,
                'name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
            ];

            // se non c'è testo, metti un placeholder "media"
            if ($text === '') {
                $text = $isImage ? '[immagine]' : '[audio]';
            }
        }

        if ($text === '') {
            return response()->json(['error' => 'Message is empty'], 422);
        }

        try {
            // salva messaggio utente (con meta media se presenti)
            $this->chat->storeUserMessage($userId, $text, $customer, $extra);

            // reply mock (su media può comunque rispondere con testo generico)
            $assistantText = $this->chat->reply($userId, $text, $customer);

            return response()->json([
                'message' => [
                    'role'     => 'assistant',
                    'content'  => $assistantText,
                    'ts'       => now()->toIso8601String(),
                    'customer' => $customer,
                    // niente media per la reply mock (per ora)
                ],
                // echo back del messaggio utente con meta (per render immediato)
                'echo' => array_merge([
                    'role'     => 'user',
                    'content'  => $text,
                    'ts'       => now()->toIso8601String(),
                    'customer' => $customer,
                ], $extra),
            ], 200);

        } catch (\Throwable $e) {
            \Log::error('chat.send.failed', [
                'user_id'  => $userId,
                'customer' => $customer,
                'error'    => $e->getMessage(),
            ]);
            return response()->json(['error' => 'Server error'], 500);
        }
    }



}
