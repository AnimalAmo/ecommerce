// Componenti Alpine del checkout (Stripe Payment/Express Checkout Element).
import './payment';

// Header nav: dopo un full-reload il mouse resta fermo sopra il link appena cliccato,
// così :hover resta attivo e il link sembra "bloccato" nello stile hover (grassetto).
// Sopprimiamo l'effetto hover della nav finché l'utente non muove il mouse.
document.documentElement.classList.add('nav-hover-idle');
window.addEventListener(
    'mousemove',
    () => document.documentElement.classList.remove('nav-hover-idle'),
    { once: true },
);
