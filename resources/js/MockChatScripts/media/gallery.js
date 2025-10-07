import { add as addToPending } from './pendingStore';
import { Buttons } from '../ui/buttons';

export function init() {
  const fileImg = document.getElementById('file-image');
  if (!fileImg) return;

  // Funziona sia con <button id="btn-image"> sia con <label> contenitore dell'input
  let triggerEl = document.getElementById('btn-image');
  if (!triggerEl) {
    // prova a trovare il label che contiene l'input (setup attuale)
    triggerEl = fileImg.closest('label') || document.querySelector('label[for="file-image"]');
  }

  const setGreen = (on) => Buttons.setBtnGreen(triggerEl, !!on);

  if (triggerEl) {
    triggerEl.addEventListener('click', (e) => {
      // Se è un vero <label> che contiene l'input, il click già apre il picker.
      // Se è un <button>, apriamo noi il picker.
      if (triggerEl.tagName !== 'LABEL') {
        e.preventDefault();
        fileImg.click();
      }
    });
  }

  fileImg.addEventListener('change', () => {
    if (fileImg.files?.length) {
      addToPending(fileImg.files);
      setGreen(true);
    } else {
      setGreen(false);
    }
  });
}
