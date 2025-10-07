<x-app-layout>
    {{-- Header verde --}}
    <x-slot name="header" class="bg-whatsapp-600 text-white">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl leading-tight">Chat</h2>
            <span class="text-sm opacity-90">Utente: {{ auth()->user()->name }}</span>
        </div>
    </x-slot>

    {{-- Typing dots CSS --}}
    <style>
        .typing-dots { display:inline-flex; gap:.25rem; align-items:center; }
        .typing-dots .dot { width:.35rem; height:.35rem; border-radius:9999px; background:currentColor; opacity:.2; animation: blink 1.4s infinite both; }
        .typing-dots .dot:nth-child(2){ animation-delay:.2s }
        .typing-dots .dot:nth-child(3){ animation-delay:.4s }
        @keyframes blink { 0%,80%,100%{opacity:.2} 40%{opacity:1} }

        /* Modal semplice per webcam */
        .modal-hide { display: none; }
        .modal-show { display: flex; }
    </style>

    <div class="py-6">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white border rounded-lg h-[70vh] flex flex-col">

                {{-- Messaggi --}}
                <div id="chat-scroll" class="flex-1 overflow-y-auto p-4">
                    @php $prevRole = null; @endphp

                    @forelse($messages as $m)
                        @php
                            $role       = $m['role'] ?? 'assistant';
                            $isUser     = $role === 'user';
                            $isNewGroup = $prevRole !== $role;
                            $prevRole   = $role;

                            $rowGap = $isNewGroup ? 'mt-6' : 'mt-3';
                            $bubbleBase = 'relative w-fit max-w-[80%] break-words rounded-2xl px-2.5 py-1.5 leading-normal shadow-sm text-left';
                            $bubbleTone = $isUser
                                ? 'bg-whatsapp-600 text-white rounded-br-md'
                                : 'bg-gray-100 text-gray-900 rounded-bl-md';

                            // pulizia: togli righe vuote iniziali e de-indent comune
                            $raw = $m['content'] ?? '';
                            $raw = preg_replace("/^\h*\R+/u", '', $raw);
                            $lines = preg_split("/\R/u", $raw);
                            $nonEmpty = array_filter($lines, fn($l) => trim($l) !== '');
                            $indentLen = null;
                            foreach ($nonEmpty as $l) {
                                preg_match('/^[ \t]*/', $l, $mIndent);
                                $len = strlen($mIndent[0] ?? '');
                                $indentLen = is_null($indentLen) ? $len : min($indentLen, $len);
                            }
                            if ($indentLen) {
                                $lines = array_map(fn($l) => preg_replace('/^[ \t]{0,' . $indentLen . '}/', '', $l, 1), $lines);
                            }
                            $text = implode("\n", $lines);

                            $type = $m['type'] ?? null;
                            $url  = $m['url'] ?? null;
                        @endphp

                        <div class="flex {{ $isUser ? 'justify-end' : 'justify-start' }} {{ $rowGap }} px-1">
                            <div class="{{ $bubbleBase }} {{ $bubbleTone }}">
                                {{-- code triangolari --}}
                                @if($isUser)
                                    <span class="absolute -right-2 bottom-2 w-0 h-0 border-t-[10px] border-t-transparent border-b-[10px] border-b-transparent border-l-[10px] border-l-whatsapp-600"></span>
                                @else
                                    <span class="absolute -left-2 bottom-2 w-0 h-0 border-t-[10px] border-t-transparent border-b-[10px] border-b-transparent border-r-[10px] border-r-gray-100"></span>
                                @endif

                                {{-- contenuto --}}
                                @if($type === 'image' && $url)
                                    <img src="{{ $url }}" alt="immagine" class="block max-w-full rounded-md">
                                    @if($text && $text !== '[immagine]')
                                        <div class="mt-1 text-sm">{{ $text }}</div>
                                    @endif
                                @elseif($type === 'audio' && $url)
                                    <audio controls src="{{ $url }}" class="block w-60"></audio>
                                    @if($text && $text !== '[audio]')
                                        <div class="mt-1 text-sm">{{ $text }}</div>
                                    @endif
                                @else
                                    <div class="text-sm break-words text-left leading-normal whitespace-pre-wrap"
                                         style="font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono','Courier New', monospace; tab-size:4">{{ e($text) }}</div>
                                @endif

                                <div class="mt-1.5 text-[11px] opacity-75 text-right">
                                    {{ \Illuminate\Support\Carbon::parse($m['ts'])->format('d/m H:i') }}
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-sm text-gray-500 px-1">Nessun messaggio.</div>
                    @endforelse
                </div>

                {{-- Composer --}}
                <form id="chat-form" method="POST" action="{{ route('users.chat.send') }}"
                      class="border-t p-3 flex items-end gap-2 flex-wrap"
                      enctype="multipart/form-data"
                      autocomplete="off">
                    @csrf

                    {{-- Numero di telefono obbligatorio --}}
                    <div class="flex items-center gap-2 w-full sm:w-auto">
                        <label for="chat-customer" class="text-xs text-gray-600">Numero</label>
                        <input
                            id="chat-customer"
                            name="customer"
                            type="tel"
                            inputmode="numeric"
                            pattern="[0-9]*"
                            value="{{ $customer ?? '' }}"
                            placeholder="es. 3331234567"
                            class="w-44 rounded-md border-gray-300 focus:border-whatsapp-600 focus:ring-whatsapp-600"
                            required
                        />
                    </div>

                    {{-- bottoni media --}}
                    <label id="btn-camera" class="inline-flex items-center justify-center rounded-md px-3 py-2 border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 cursor-pointer transition select-none">
                        📷
                        {{-- Fallback mobile: apre camera nativa, ora con multiple --}}
                        <input id="file-camera" type="file" accept="image/*" capture="environment" multiple class="hidden">
                    </label>

                    <label id="btn-image" class="inline-flex items-center justify-center rounded-md px-3 py-2 border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 cursor-pointer transition select-none">
                        🖼️
                        <input id="file-image" type="file" accept="image/*" class="hidden">
                    </label>

                    <button id="rec-toggle" type="button"
                            class="inline-flex items-center rounded-md px-3 py-2 border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 transition">
                        🎙️
                    </button>

                    <textarea
                        id="chat-input"
                        name="message"
                        rows="1"
                        class="flex-1 rounded-md border-gray-300 focus:border-whatsapp-600 focus:ring-whatsapp-600 resize-none min-h-[2.6rem] max-h-40 leading-6"
                        placeholder="Scrivi un messaggio…"
                        autocomplete="off"
                        autocapitalize="off"
                        autocorrect="off"
                        spellcheck="false"
                    ></textarea>

                    <button id="chat-send" type="submit"
                            class="inline-flex items-center rounded-md bg-whatsapp-600 px-4 py-2 text-white font-medium hover:bg-whatsapp-700 transition">
                        Invia
                    </button>
                </form>

            </div>
        </div>
    </div>

    {{-- Modal Webcam --}}
    <div id="cam-modal" class="modal-hide fixed inset-0 z-50 items-center justify-center">
        <div class="absolute inset-0 bg-black/60"></div>
        <div class="relative bg-white rounded-xl shadow-xl w-[95vw] max-w-lg p-4 mx-auto">
            <h3 class="text-lg font-semibold mb-3">Fotocamera</h3>
            <div class="aspect-video bg-black/80 rounded-lg overflow-hidden flex items-center justify-center">
                <video id="cam-video" autoplay playsinline class="w-full h-full object-cover"></video>
                <canvas id="cam-canvas" class="hidden"></canvas>
            </div>
            <div class="mt-3 flex items-center justify-between">
                <div class="text-sm text-gray-600">
                    Scatti: <span id="cam-count">0</span>
                </div>
                <div class="flex gap-2">
                    <button id="cam-cancel" class="px-3 py-2 rounded-md border border-gray-300 bg-white hover:bg-gray-50">Annulla</button>
                    <button id="cam-capture" class="px-3 py-2 rounded-md bg-whatsapp-600 text-white hover:bg-whatsapp-700">Scatta</button>
                    <button id="cam-done" class="px-3 py-2 rounded-md border border-gray-300 bg-white hover:bg-gray-50">Fine</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Entrypoint Vite dei moduli chat --}}
    @vite('resources/js/MockChatScripts.js')
</x-app-layout>
