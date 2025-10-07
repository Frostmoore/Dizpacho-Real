<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ChatUploadController extends Controller
{
    public function store(Request $request)
    {
        // Accettiamo sia "files[]" che "file" singolo
        $files = $request->file('files', []);
        if (empty($files) && $request->hasFile('file')) {
            $files = [$request->file('file')];
        }

        if (empty($files)) {
            return response()->json(['error' => 'Nessun file'], 422);
        }

        // Validazione: immagini + audio comuni, fino a 20MB ciascuno
        $validator = Validator::make(
            ['files' => $files],
            ['files.*' => 'file|max:20480|mimetypes:image/jpeg,image/png,image/webp,image/gif,image/heic,image/heif,audio/webm,audio/ogg,audio/mpeg,audio/mp3,audio/wav,audio/x-wav,audio/mp4,audio/aac,audio/m4a']
        );

        if ($validator->fails()) {
            return response()->json([
                'error'  => 'File non valido',
                'detail' => $validator->errors(),
            ], 422);
        }

        $saved = [];
        $baseDir = 'chat/' . now()->format('Y/m/d');

        foreach ($files as $file) {
            // nome univoco leggibile
            $ext   = $file->getClientOriginalExtension() ?: 'bin';
            $name  = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $safe  = str($name)->slug('_')->value();
            $store = $file->storeAs($baseDir, $safe . '_' . uniqid() . '.' . $ext, 'public');

            $saved[] = [
                'url'  => asset('storage/' . $store),
                'mime' => $file->getMimeType(),
                'name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
            ];
        }

        return response()->json(['files' => $saved], 201);
    }
}
