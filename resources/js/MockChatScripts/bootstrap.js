import * as Buttons from './ui/buttons';
import * as Textarea from './ui/textarea';
import * as InputHandlers from './features/inputHandlers';
import * as Webcam from './media/webcam';
import * as CameraInput from './media/cameraInput';
import * as Gallery from './media/gallery';
import * as AudioRecorder from './media/audioRecorder';
import { initTypingCSS } from './ui/typing';
import { ensureCSRF } from './transport/api';

document.addEventListener('DOMContentLoaded', () => {
  // CSS per i tre puntini (inietta una sola volta)
  initTypingCSS();
  // CSRF da <meta>
  ensureCSRF();

  // UI basics
  Buttons.init();
  Textarea.init();

  // Media
  Webcam.init();
  CameraInput.init();
  Gallery.init();
  AudioRecorder.init();

  // Handlers (submit, enter/shift+enter, validazioni, preview ecc.)
  InputHandlers.init();
});
