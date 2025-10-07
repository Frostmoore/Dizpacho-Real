import { pendingFiles, add as addToPending, hasImage } from './pendingStore';
import { Buttons } from '../ui/buttons';

export function init() {
  const btnCam   = document.getElementById('btn-camera');
  const fileCam  = document.getElementById('file-camera'); // fallback mobile

  const camModal   = document.getElementById('cam-modal');
  const camVideo   = document.getElementById('cam-video');
  const camCanvas  = document.getElementById('cam-canvas');
  const camCapture = document.getElementById('cam-capture');
  const camDone    = document.getElementById('cam-done');
  const camCancel  = document.getElementById('cam-cancel');
  const camCountEl = document.getElementById('cam-count');

  if (!btnCam) return;

  const hasWebcam = !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia);
  let camStream = null;
  let camShots = 0;

  const openCamModal = async () => {
    try {
      camStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode:'environment' }, audio:false });
      camVideo.srcObject = camStream;
      camShots = 0; if (camCountEl) camCountEl.textContent = '0';
      camModal.classList.remove('modal-hide'); camModal.classList.add('modal-show');
    } catch {
      fileCam?.click(); // fallback
    }
  };
  const closeCamModal = () => {
    if (camStream) { try { camStream.getTracks().forEach(t => t.stop()); } catch {} camStream=null; }
    camModal.classList.remove('modal-show'); camModal.classList.add('modal-hide');
  };

  btnCam.addEventListener('click', (e) => { e.preventDefault(); hasWebcam ? openCamModal() : fileCam?.click(); });
  camCancel?.addEventListener('click', (e) => { e.preventDefault(); closeCamModal(); });
  camDone?.addEventListener('click', (e) => { e.preventDefault(); closeCamModal(); updateButton(); });

  camCapture?.addEventListener('click', (e) => {
    e.preventDefault();
    if (!camStream) return;
    const trackSettings = camStream.getVideoTracks()[0].getSettings();
    const w = trackSettings.width || 1280;
    const h = trackSettings.height || 720;
    camCanvas.width = w; camCanvas.height = h;
    const ctx = camCanvas.getContext('2d');
    ctx.drawImage(camVideo, 0, 0, w, h);
    camCanvas.toBlob((blob) => {
      if (!blob) return;
      const file = new File([blob], `camera_${Date.now()}.jpg`, { type:'image/jpeg' });
      addToPending([file]);
      camShots += 1;
      if (camCountEl) camCountEl.textContent = String(camShots);
      updateButton();
    }, 'image/jpeg', 0.92);
  });

  fileCam?.addEventListener('change', () => {
    if (fileCam.files?.length) { addToPending(fileCam.files); updateButton(); }
  });

  function updateButton() {
    Buttons.setBtnGreen(btnCam, hasImage() || (fileCam?.files?.length > 0));
  }
}
