// Stato condiviso a runtime
export const state = {
  csrf: null,
  pendingFiles: /** @type {File[]} */([]),
  recording: false,
  mediaRecorder: /** @type {MediaRecorder|null} */(null),
  audioChunks: /** @type {BlobPart[]} */([]),
  stream: /** @type {MediaStream|null} */(null), // webcam/audio stream
};

export const setCSRF = (token) => { state.csrf = token || null; };
