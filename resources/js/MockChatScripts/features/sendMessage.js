import { postJSON, uploadAll } from '../transport/api';
import { pending } from '../media/pendingStore';
import { makeBubble, makeTyping, scrollToBottom } from '../ui/bubbles';

export async function sendMessage() {
  const form   = document.getElementById('chat-form');
  const input  = document.getElementById('chat-input');
  const phone  = document.getElementById('chat-customer');
  const send   = document.getElementById('chat-send');
  const uploadUrl = form?.dataset?.uploadUrl || '';

  let text = (input.value || '').replace(/^\s+/, '');
  if (!text && pending.isEmpty()) return;

  const customer = (phone?.value || '').trim();
  if (!customer) { phone?.focus(); return; }

  // bubble utente (testo placeholder se solo media)
  const userText = text || '[media]';
  document.getElementById('chat-scroll')?.appendChild(
    makeBubble({ role:'user', text:userText, ts:null })
  );

  // puntini
  const typing = makeTyping();
  document.getElementById('chat-scroll')?.appendChild(typing);
  scrollToBottom();

  // blocca input
  input.disabled = true; send.disabled = true;

  try {
    // upload allegati
    let uploaded = [];
    if (!pending.isEmpty()) {
      uploaded = await uploadAll(uploadUrl, pending.list);
      // mostra anteprime server-side (URL pubblici)
      uploaded.forEach(f => {
        const t = (f.mime || '').startsWith('audio') ? 'audio' : (f.mime || '').startsWith('image') ? 'image' : null;
        if (t) {
          document.getElementById('chat-scroll')?.appendChild(
            makeBubble({ role:'user', text:'', ts:null, type:t, url:f.url, mime:f.mime })
          );
        }
      });
      scrollToBottom();
    }

    // invia al controller
    const res = await postJSON(form.action, {
      message: text || '[media]',
      customer,
      attachments: uploaded
    });

    // sostituisci puntini con risposta
    typing.remove?.();
    document.getElementById('chat-scroll')?.appendChild(
      makeBubble({ role:'assistant', text: res?.message?.content ?? '...', ts: res?.message?.ts ?? null })
    );
    scrollToBottom();

    // reset UI
    pending.clear();
    input.value = '';
    input.dispatchEvent(new Event('input'));
    ['btn-camera','btn-gallery','btn-mic'].forEach(id => {
      const b = document.getElementById(id);
      b && b.classList.remove('btn-active');
    });
    const f1 = document.getElementById('file-camera'); if (f1) f1.value = '';
    const f2 = document.getElementById('file-gallery'); if (f2) f2.value = '';

  } catch (e) {
    const dots = typing.querySelector?.('.typing-dots');
    if (dots && dots.parentElement) {
      dots.parentElement.textContent = (e.message === 'CSRF')
        ? 'Sessione scaduta. Ricarica la pagina.'
        : 'Errore di rete. Riprova.';
    }
  } finally {
    input.disabled = false; send.disabled = false;
    input.focus();
  }
}
