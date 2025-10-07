// resources/js/MockChatScripts/media/pendingStore.js

export let pendingFiles = [];

// --- util interno: emetti evento per aggiornare i bottoni ---
function emitChange() {
  const detail = {
    hasImage: hasImage(),
    hasAudio: hasAudio(),
    any: any(),
  };
  document.dispatchEvent(new CustomEvent('pending:change', { detail }));
}

export function add(files) {
  for (const f of files) {
    if (f && f.size > 0) pendingFiles.push(f);
  }
  emitChange();
}

export function clear() {
  pendingFiles = [];
  emitChange();
}

// opzionali (comodi se mai serviranno)
export function removeAt(i) {
  if (i >= 0 && i < pendingFiles.length) {
    pendingFiles.splice(i, 1);
    emitChange();
  }
}
export function all() { return pendingFiles.slice(); }

// --- query helpers ---
export function any()      { return pendingFiles.length > 0; }
export function hasImage() { return pendingFiles.some(f => f.type?.startsWith('image/')); }
export function hasAudio() { return pendingFiles.some(f => f.type?.startsWith('audio/') || /\.webm$/i.test(f.name)); }

// --- (facoltativo) notifica in tempo reale lo stato di registrazione ---
export function setRecordingActive(active) {
  document.dispatchEvent(new CustomEvent('recording:state', { detail: { active: !!active } }));
}
