import { add as addToPending } from './pendingStore';
import { Buttons } from '../ui/buttons';

export function init() {
  const recBtn = document.getElementById('rec-toggle');
  if (!recBtn) return;

  let mediaRecorder = null;
  let recChunks = [];
  let recActive = false;

  async function toggleRecording() {
    if (!recActive) {
      try {
        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        mediaRecorder = new MediaRecorder(stream);
        recChunks = [];

        mediaRecorder.ondataavailable = (e) => {
          if (e.data?.size) recChunks.push(e.data);
        };

        mediaRecorder.onstop = () => {
          // crea file audio e aggiungilo alla coda
          const blob = new Blob(recChunks, { type: 'audio/webm' });
          const file = new File([blob], `rec_${Date.now()}.webm`, { type: 'audio/webm' });
          addToPending([file]);

          // ✅ lascia il bottone acceso (c'è un audio in coda)
          Buttons.setBtnGreen(recBtn, true);

          // stop tracce
          try { stream.getTracks().forEach(t => t.stop()); } catch {}
        };

        mediaRecorder.start();
        recActive = true;

        // registrazione attiva → pulsante verde e icona stop
        recBtn.textContent = '⏹️';
        Buttons.setBtnGreen(recBtn, true);

      } catch {
        alert('Microfono non disponibile');
      }
    } else {
      try { mediaRecorder?.stop(); } catch {}
      recActive = false;

      // torna all’icona microfono, ma NON spegnere il verde: c’è la coda
      recBtn.textContent = '🎙️';
    }
  }

  recBtn.addEventListener('click', (e) => {
    e.preventDefault();
    toggleRecording();
  });
}
