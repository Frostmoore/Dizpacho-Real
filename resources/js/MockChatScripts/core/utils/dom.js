export const $  = (sel, root=document) => root.querySelector(sel);
export const $$ = (sel, root=document) => Array.from(root.querySelectorAll(sel));
export const isMobile = () =>
  ('ontouchstart' in window) || /Android|iPhone|iPad|iPod/i.test(navigator.userAgent);
