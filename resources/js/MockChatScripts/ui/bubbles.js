export const fmtDDMM_HHMM = (ts) => {
  const d = ts ? new Date(ts) : new Date();
  const dd = String(d.getDate()).padStart(2,'0');
  const mm = String(d.getMonth()+1).padStart(2,'0');
  const hh = String(d.getHours()).padStart(2,'0');
  const mi = String(d.getMinutes()).padStart(2,'0');
  return `${dd}/${mm} ${hh}:${mi}`;
};

export function scrollToBottom() {
  const s = document.getElementById('chat-scroll');
  if (s) s.scrollTop = s.scrollHeight;
}

export function makeBubble({role, text, ts, pending=false, type=null, url=null}) {
  const isUser = role === 'user';
  const wrap = document.createElement('div');
  wrap.className = `flex ${isUser ? 'justify-end' : 'justify-start'} mt-3 px-1`;

  const bubble = document.createElement('div');
  bubble.className = `relative w-fit max-w-[80%] break-words rounded-2xl px-2.5 py-1.5 leading-normal shadow-sm text-left ${
    isUser ? 'bg-whatsapp-600 text-white rounded-br-md' : 'bg-gray-100 text-gray-900 rounded-bl-md'
  }`;

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
    img.src = url; img.alt = 'immagine';
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
    audio.controls = true; audio.src = url; audio.className = 'block w-60';
    bubble.appendChild(audio);
    if (text && text !== '[audio]') {
      const cap = document.createElement('div');
      cap.className = 'mt-1';
      cap.textContent = text;
      bubble.appendChild(cap);
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
