import { ensureCSRF } from '../transport/api';
import { pendingFiles, clear as clearPending } from '../media/pendingStore';
import { makeBubble, scrollToBottom } from '../ui/bubbles';
import { Buttons } from '../ui/buttons';

export function init() {
  const scroller   = document.getElementById('chat-scroll');
  const form       = document.getElementById('chat-form');
  const input      = document.getElementById('chat-input');
  const sendBtn    = document.getElementById('chat-send');
  const phoneInput = document.getElementById('chat-customer');
  const csrf       = ensureCSRF();

  const fileCam  = document.getElementById('file-camera');
  const fileImg  = document.getElementById('file-image');
  const btnCam   = document.getElementById('btn-camera');
  const btnImg   = document.getElementById('btn-image');
  const recBtn   = document.getElementById('rec-toggle');

  if (!form || !input) return;

  const updateButtonsState = () => {
    const anyImage = pendingFiles.some(f => f.type?.startsWith('image/'));
    Buttons.setBtnGreen(btnCam, anyImage || (fileCam?.files && fileCam.files.length > 0));
    Buttons.setBtnGreen(btnImg, (fileImg?.files && fileImg.files.length > 0));
    // recBtn viene gestito nel modulo audioRecorder
  };

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    e.stopPropagation();

    let text = (input.value || '').replace(/^\s+/, '');
    const customer = (phoneInput?.value || '').trim();

    if (!customer) { phoneInput?.focus(); return; }
    if (!text && pendingFiles.length === 0) return;

    // Preview media (utenti)
    if (pendingFiles.length > 0) {
      const captionText = text;
      const lastIndex = pendingFiles.length - 1;
      pendingFiles.forEach((f, idx) => {
        const isImg = f.type?.startsWith('image/');
        const isAud = f.type?.startsWith('audio/') || /\.webm$/i.test(f.name);
        const url   = URL.createObjectURL(f);
        const txt   = (idx === lastIndex) ? captionText : '';
        scroller.appendChild(makeBubble({
          role:'user',
          text: (txt || (isImg ? '[immagine]' : isAud ? '[audio]' : '')),
          ts:null,
          type: isImg ? 'image' : (isAud ? 'audio' : null),
          url
        }));
      });
    } else {
      scroller.appendChild(makeBubble({ role:'user', text, ts:null }));
    }

    const typing = makeBubble({ role:'assistant', text:'', pending:true });
    scroller.appendChild(typing);
    scrollToBottom();

    input.disabled = true; sendBtn.disabled = true;

    try {
      const fd = new FormData();
      fd.set('_token', csrf || '');
      fd.set('message', text || (pendingFiles.length ? '[media]' : ''));
      fd.set('customer', customer);

      if (pendingFiles.length > 0) {
        pendingFiles.forEach(f => fd.append('files[]', f, f.name));
        // compat per server: anche "file" singolo
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
        try { const err = await res.json(); if (err?.error) message = err.error; } catch {}
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
      input.disabled = false; sendBtn.disabled = false;
      input.value = ''; input.dispatchEvent(new Event('input'));

      clearPending();
      if (fileCam) fileCam.value = '';
      if (fileImg) fileImg.value = '';
      updateButtonsState();
      if (recBtn) { Buttons.setBtnGreen(recBtn, false); recBtn.textContent = '🎙️'; }
    }
  });
}
