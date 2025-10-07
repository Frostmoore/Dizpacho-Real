import { any as hasPendingFiles } from '../media/pendingStore';

export function init() {
  const input = document.getElementById('chat-input');
  const form  = document.getElementById('chat-form');
  if (!input || !form) return;

  const autoResize = () => { 
    input.style.height = 'auto'; 
    input.style.height = Math.min(input.scrollHeight, 160) + 'px'; 
  };
  input.addEventListener('input', autoResize); 
  autoResize();

  input.addEventListener('keydown', (e) => {
    if (e.key !== 'Enter') return;

    const isProbablyMobile = ('ontouchstart' in window) || /Android|iPhone|iPad|iPod/i.test(navigator.userAgent);

    // ENTER su desktop invia, SHIFT+ENTER va a capo,
    // su mobile ENTER va a capo di default.
    if (!e.shiftKey && !isProbablyMobile) {
      e.preventDefault();

      // ⚠️ Bypass della validazione "required" quando ci sono media
      // Evita requestSubmit (che fa scattare la validazione nativa).
      const text = (input.value || '').trim();
      if (text.length === 0 && hasPendingFiles()) {
        // Dispatch manuale dell'evento submit (il nostro handler in features/inputHandlers.js farà e.preventDefault)
        form.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
      } else {
        // Se c'è testo, possiamo usare il dispatch manuale lo stesso per coerenza
        form.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
      }
    }
  });
}
