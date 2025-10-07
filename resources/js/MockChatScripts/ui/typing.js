let injected = false;

export function initTypingCSS() {
  if (injected) return;
  injected = true;
  const style = document.createElement('style');
  style.textContent = `
    .typing-dots{display:inline-flex;gap:.25rem;align-items:center}
    .typing-dots .dot{width:.35rem;height:.35rem;border-radius:9999px;background:currentColor;opacity:.2;animation:blink 1.4s infinite both}
    .typing-dots .dot:nth-child(2){animation-delay:.2s}
    .typing-dots .dot:nth-child(3){animation-delay:.4s}
    @keyframes blink{0%,80%,100%{opacity:.2}40%{opacity:1}}
  `;
  document.head.appendChild(style);
}
