// Solo CSRF helper: la post la facciamo con FormData in features/inputHandlers.js
export let CSRF = null;

export function ensureCSRF() {
  if (!CSRF) {
    CSRF = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  }
  return CSRF;
}
