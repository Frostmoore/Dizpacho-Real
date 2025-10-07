// resources/js/MockChatScripts/ui/buttons.js

// Helpers per accendere/spegnere i bottoni come nello script originale
function setBtnGreen(el, on) {
  if (!el) return;
  el.classList.toggle('bg-whatsapp-600', on);
  el.classList.toggle('text-white', on);
  el.classList.toggle('border-whatsapp-600', on);
  el.classList.toggle('bg-white', !on);
  el.classList.toggle('text-gray-700', !on);
  el.classList.toggle('border-gray-300', !on);
}

// Esposti per gli altri moduli
export const Buttons = { setBtnGreen };

export function init() {
  // Se vuoi, qui potresti in futuro ascoltare eventi globali per
  // pending/recording e aggiornare automaticamente i bottoni.
  // Per ora non serve: il mic viene acceso in audioRecorder.js
}
