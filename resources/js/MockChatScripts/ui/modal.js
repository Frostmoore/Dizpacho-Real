import { qs, addClass, removeClass } from '../core/utils/dom';

export const openModal = (sel) => {
  const m = qs(sel); if (!m) return;
  removeClass(m, 'hidden'); addClass(m, 'flex');
};

export const closeModal = (sel) => {
  const m = qs(sel); if (!m) return;
  removeClass(m, 'flex'); addClass(m, 'hidden');
};
