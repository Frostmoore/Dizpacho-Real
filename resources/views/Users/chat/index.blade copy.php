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
                        @endphp

                        <div class="flex {{ $isUser ? 'justify-end' : 'justify-start' }} {{ $rowGap }} px-1">
                            <div class="{{ $bubbleBase }} {{ $bubbleTone }}">
                                {{-- code triangolari --}}
                                @if($isUser)
                                    <span class="absolute -right-2 bottom-2 w-0 h-0 border-t-[10px] border-t-transparent border-b-[10px] border-b-transparent border-l-[10px] border-l-whatsapp-600"></span>
                                @else
                                    <span class="absolute -left-2 bottom-2 w-0 h-0 border-t-[10px] border-t-transparent border-b-[10px] border-b-transparent border-r-[10px] border-r-gray-100"></span>
                                @endif

                                {{-- testo (font monospace decente, mantiene a capo/spazi/tab) --}}
                                <div class="text-sm break-words text-left leading-normal whitespace-pre-wrap"
                                     style="font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono','Courier New', monospace; tab-size:4">{{ e($text) }}</div>

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
                    class="border-t p-3 flex items-end gap-2"
                    autocomplete="off">
                    @csrf
                    <input
                        id="chat-customer"
                        name="customer"
                        type="tel"
                        value="{{ $customer ?? '' }}"
                        placeholder="Numero di telefono del cliente"
                        class="w-56 rounded-md border-gray-300 focus:border-whatsapp-600 focus:ring-whatsapp-600"
                        {{ isset($customer) && $customer ? '' : 'required' }}
                    />

                    <textarea
                        id="chat-input"
                        name="message"
                        rows="1"
                        class="flex-1 rounded-md border-gray-300 focus:border-whatsapp-600 focus:ring-whatsapp-600 resize-none min-h-[2.6rem] max-h-40 leading-6"
                        placeholder="Scrivi un messaggio…"
                        required
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
        const scroller = document.getElementById('chat-scroll');
        const form     = document.getElementById('chat-form');
        const input    = document.getElementById('chat-input');
        const sendBtn  = document.getElementById('chat-send');
        const csrf     = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        // Helper: from ISO → "DD/MM HH:mm" in orario locale del browser
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

        // --- Auto-resize textarea
        const autoResize = () => {
            input.style.height = 'auto';
            input.style.height = Math.min(input.scrollHeight, 160) + 'px';
        };
        input.addEventListener('input', autoResize);
        autoResize();

        // --- Invio / Shift+Invio (desktop)
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
            const isProbablyMobile = ('ontouchstart' in window) || /Android|iPhone|iPad|iPod/i.test(navigator.userAgent);
            if (!e.shiftKey && !isProbablyMobile) {
                e.preventDefault(); // invia su desktop con Enter
                form.requestSubmit();
            }
            // Shift+Enter: va a capo (default)
            }
        });

        function makeBubble({role, text, ts, pending=false}) {
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
            } else {
            content.textContent = text ?? '';
            }
            bubble.appendChild(content);

            const meta = document.createElement('div');
            meta.className = 'mt-1.5 text-[11px] opacity-75 text-right';
            meta.textContent = fmtDDMM_HHMM(ts);
            bubble.appendChild(meta);

            wrap.appendChild(bubble);
            return wrap;
        }

        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            let text = (input.value || '').replace(/^\s+/, '');
            if (!text) return;

            // 1) Append messaggio utente
            const userBubble   = makeBubble({ role:'user', text, ts:null });
            scroller.appendChild(userBubble);

            // 2) Append "tre puntini"
            const typingBubble = makeBubble({ role:'assistant', text:'', ts:null, pending:true });
            scroller.appendChild(typingBubble);

            const phoneInput = document.getElementById('chat-customer');
            const customer   = (phoneInput?.value || '').trim();

            if (!customer) {
                phoneInput?.focus();
                return; // blocca: customer obbligatorio
            }

            scrollToBottom();

            // 3) Blocca input fino a risposta
            input.disabled = true; sendBtn.disabled = true;

            // 4) fetch → JSON → sostituisci i puntini con la risposta vera
            try {
            

            const res = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrf
                },
                body: JSON.stringify({ message: text, customer })
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
            const replyText = payload?.message?.content ?? '...';
            const replyTs   = payload?.message?.ts      ?? null;

            typingBubble.remove();
            scroller.appendChild(makeBubble({ role:'assistant', text: replyText, ts: replyTs }));
            scrollToBottom();

            } catch (err) {
            const c = typingBubble.querySelector('.typing-dots');
            if (c) {
                c.parentElement.textContent = (err.message === 'CSRF')
                ? 'Sessione scaduta. Ricarica la pagina.'
                : 'Errore di rete. Riprova.';
            }
            } finally {
            input.disabled = false; sendBtn.disabled = false;
            input.value = '';
            input.focus();
            input.dispatchEvent(new Event('input')); // per ri-calcolare l’altezza
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
