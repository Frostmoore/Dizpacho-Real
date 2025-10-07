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
                    <label id="btn-camera" class="inline-flex items-center justify-center rounded-md px-3 py-2 border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 cursor-pointer transition">
                        📷
                        <input id="file-camera" type="file" accept="image/*" capture="environment" multiple class="hidden">
                    </label>

                    <label id="btn-image" class="inline-flex items-center justify-center rounded-md px-3 py-2 border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 cursor-pointer transition">
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

    {{-- JS --}}
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const scroller   = document.getElementById('chat-scroll');
        const form       = document.getElementById('chat-form');
        const input      = document.getElementById('chat-input');
        const sendBtn    = document.getElementById('chat-send');
        const csrf       = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const phoneInput = document.getElementById('chat-customer');

        const btnCam   = document.getElementById('btn-camera');
        const btnImg   = document.getElementById('btn-image');
        const fileCam  = document.getElementById('file-camera');
        const fileImg  = document.getElementById('file-image');
        const recBtn   = document.getElementById('rec-toggle');

        // Pending media bucket
        let pendingFiles = [];

        // Helpers
        const fmtDDMM_HHMM = (ts) => {
            const d = ts ? new Date(ts) : new Date();
            const dd = String(d.getDate()).padStart(2, '0');
            const mm = String(d.getMonth() + 1).padStart(2, '0');
            const hh = String(d.getHours()).padStart(2, '0');
            const mi = String(d.getMinutes()).padStart(2, '0');
            return `${dd}/${mm} ${hh}:${mi}`;
        };

        const scrollToBottom = () => { if (scroller) scroller.scrollTop = scroller.scrollHeight; };
        scrollToBottom();

        const autoResize = () => { input.style.height='auto'; input.style.height=Math.min(input.scrollHeight,160)+'px'; };
        input.addEventListener('input', autoResize); autoResize();

        // Enter invia (desktop), Shift+Enter a capo, mobile Enter = a capo
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                const isProbablyMobile = ('ontouchstart' in window) || /Android|iPhone|iPad|iPod/i.test(navigator.userAgent);
                if (!e.shiftKey && !isProbablyMobile) {
                    e.preventDefault();
                    form.requestSubmit();
                }
            }
        });

        function makeBubble({role, text, ts, pending=false, type=null, url=null}) {
            const isUser   = role === 'user';
            const wrap     = document.createElement('div');
            wrap.className = `flex ${isUser ? 'justify-end' : 'justify-start'} mt-3 px-1`;

            const bubble   = document.createElement('div');
            bubble.className = `relative w-fit max-w-[80%] break-words rounded-2xl px-2.5 py-1.5 leading-normal shadow-sm text-left ${isUser ? 'bg-whatsapp-600 text-white rounded-br-md' : 'bg-gray-100 text-gray-900 rounded-bl-md'}`;

            const tail = document.createElement('span');
            tail.className = `absolute ${isUser ? '-right-2 bottom-2 border-l-[10px] border-l-whatsapp-600' : '-left-2 bottom-2 border-r-[10px] border-r-gray-100'} w-0 h-0 border-t-[10px] border-t-transparent border-b-[10px] border-b-transparent`;
            bubble.appendChild(tail);

            const content = document.createElement('div');
            content.className = 'text-sm break-words text-left leading-normal whitespace-pre-wrap';
            content.style.fontFamily = "ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono','Courier New', monospace";
            content.style.tabSize = 4;

            if (pending) {
                content.innerHTML = '<span class="typing-dots"><span class="dot"></span><span class="dot"></span><span class="dot"></span></span>';
                bubble.appendChild(content);
            } else if (type === 'image' && url) {
                const img = document.createElement('img');
                img.src = url;
                img.alt = 'immagine';
                img.className = 'block max-w-full rounded-md';
                bubble.appendChild(img);
                if (text && text !== '[immagine]') {
                    const caption = document.createElement('div');
                    caption.className = 'mt-1';
                    caption.textContent = text;
                    bubble.appendChild(caption);
                }
            } else if (type === 'audio' && url) {
                const audio = document.createElement('audio');
                audio.controls = true;
                audio.src = url;
                audio.className = 'block w-60';
                bubble.appendChild(audio);
                if (text && text !== '[audio]') {
                    const caption = document.createElement('div');
                    caption.className = 'mt-1';
                    caption.textContent = text;
                    bubble.appendChild(caption);
                }
            } else {
                content.textContent = text ?? '';
                bubble.appendChild(content);
            }

            const meta = document.createElement('div');
            meta.className = 'mt-1.5 text-[11px] opacity-75 text-right';
            meta.textContent = ts ? fmtDDMM_HHMM(ts) : fmtDDMM_HHMM();
            bubble.appendChild(meta);

            wrap.appendChild(bubble);
            return wrap;
        }

        // ---- MEDIA PICKERS & STATES (button highlight) ----
        const setBtnGreen = (el, on) => {
            el.classList.toggle('bg-whatsapp-600', on);
            el.classList.toggle('text-white', on);
            el.classList.toggle('border-whatsapp-600', on);
            el.classList.toggle('bg-white', !on);
            el.classList.toggle('text-gray-700', !on);
            el.classList.toggle('border-gray-300', !on);
        };

        const updateButtonsState = () => {
            // camera button green if camera input has files
            setBtnGreen(btnCam, (fileCam.files && fileCam.files.length > 0));
            // gallery button green if gallery input has a file
            setBtnGreen(btnImg, (fileImg.files && fileImg.files.length > 0));
            // recorder handled separately when active
        };

        const addToPending = (files) => {
            for (const f of files) {
                if (f && f.size > 0) pendingFiles.push(f);
            }
        };

        fileCam.addEventListener('change', () => {
            if (fileCam.files?.length) {
                addToPending(fileCam.files);
            }
            updateButtonsState();
        });

        fileImg.addEventListener('change', () => {
            if (fileImg.files?.length) {
                addToPending(fileImg.files);
            }
            updateButtonsState();
        });

        // ---- Recording (MediaRecorder) with green highlight ----
        let mediaRecorder = null;
        let recChunks = [];
        let recActive = false;

        const toggleRecording = async () => {
            if (!recActive) {
                try {
                    const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                    mediaRecorder = new MediaRecorder(stream);
                    recChunks = [];
                    mediaRecorder.ondataavailable = (e) => { if (e.data.size) recChunks.push(e.data); };
                    mediaRecorder.onstop = () => {
                        const blob = new Blob(recChunks, { type: 'audio/webm' });
                        const file = new File([blob], `rec_${Date.now()}.webm`, { type: 'audio/webm' });
                        addToPending([file]);
                        // turn off green (recording finished, but we keep files pending)
                        setBtnGreen(recBtn, false);
                    };
                    mediaRecorder.start();
                    recActive = true;
                    recBtn.textContent = '⏹️';
                    setBtnGreen(recBtn, true); // green while recording
                } catch {
                    alert('Microfono non disponibile');
                }
            } else {
                mediaRecorder?.stop();
                mediaRecorder?.stream.getTracks().forEach(t => t.stop());
                recActive = false;
                recBtn.textContent = '🎙️';
                // keep green reset when onstop runs
            }
        };
        recBtn.addEventListener('click', toggleRecording);

        // ---- Submit ----
        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            if (recActive) {
                await toggleRecording(); // stop recording to create file
            }

            const text = (input.value || '').replace(/^\s+/, '');
            const customer = (phoneInput?.value || '').trim();

            if (!customer) {
                phoneInput?.focus();
                return;
            }
            if (!text && pendingFiles.length === 0) return;

            // Preview bubbles for each media (if any)
            if (pendingFiles.length > 0) {
                const captionText = text;
                const lastIndex = pendingFiles.length - 1;

                pendingFiles.forEach((f, idx) => {
                    const isImg = f.type?.startsWith('image/');
                    const isAud = f.type?.startsWith('audio/') || /\.webm$/i.test(f.name);
                    const url   = URL.createObjectURL(f);

                    const txt = (idx === lastIndex) ? captionText : ''; // caption on last only
                    scroller.appendChild(makeBubble({
                        role: 'user',
                        text: (txt || (isImg ? '[immagine]' : isAud ? '[audio]' : '')),
                        ts: null,
                        type: isImg ? 'image' : (isAud ? 'audio' : null),
                        url
                    }));
                });
            } else {
                // Only text
                scroller.appendChild(makeBubble({ role:'user', text, ts:null }));
            }

            // Typing dots
            const typing = makeBubble({ role:'assistant', text:'', pending:true });
            scroller.appendChild(typing);
            scrollToBottom();

            input.disabled = true; sendBtn.disabled = true;

            try {
                const fd = new FormData();
                fd.set('_token', csrf || '');
                fd.set('message', text || (pendingFiles.length ? '[media]' : ''));
                fd.set('customer', customer);

                // Attach files (multi)
                if (pendingFiles.length > 0) {
                    pendingFiles.forEach(f => fd.append('files[]', f, f.name));
                    // Back-compat: first file also as 'file'
                    fd.set('file', pendingFiles[0], pendingFiles[0].name);
                }

                const res = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrf
                    },
                    body: fd
                });

                if (!res.ok) {
                    let message = `HTTP_${res.status}`;
                    try {
                        const err = await res.json();
                        if (err?.error) message = err.error;
                    } catch {}
                    throw new Error(message);
                }

                const payload = await res.json();
                typing.remove();

                const replyText = payload?.message?.content ?? '...';
                const replyTs   = payload?.message?.ts ?? null;
                scroller.appendChild(makeBubble({ role:'assistant', text: replyText, ts: replyTs }));
                scrollToBottom();

            } catch (err) {
                const c = typing.querySelector('.typing-dots');
                if (c) c.parentElement.textContent = 'Errore di rete. Riprova.';
            } finally {
                // Reset UI
                input.disabled = false; sendBtn.disabled = false;
                input.value = '';
                input.dispatchEvent(new Event('input'));
                // clear pending files and inputs
                pendingFiles = [];
                fileCam.value = '';
                fileImg.value = '';
                updateButtonsState();
                setBtnGreen(recBtn, false);
                recBtn.textContent = '🎙️';
            }
        });
    });
    </script>

    <style>
        /* tre puntini */
        .typing-dots { display: inline-flex; gap: 4px; align-items: center; }
        .typing-dots .dot {
            width: 6px; height: 6px; border-radius: 9999px; background: currentColor; opacity: .6;
            animation: tdots 1.2s infinite ease-in-out;
        }
        .typing-dots .dot:nth-child(2) { animation-delay: .15s; }
        .typing-dots .dot:nth-child(3) { animation-delay: .30s; }
        @keyframes tdots { 0%, 80%, 100% { transform: scale(0.6); opacity: .4; } 40% { transform: scale(1); opacity: 1; } }
    </style>
</x-app-layout>
