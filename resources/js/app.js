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

// Un submit rifiutato dalla validazione non cambia pagina: su un form lungo
// (iscrizione partner, checkout) il campo invalido può restare parecchio sopra
// il bottone, e il click sembra non fare niente. Dopo ogni commit portiamo a
// schermo il primo errore del componente che ha appena risposto.
document.addEventListener('livewire:init', () => {
    Livewire.hook('commit', ({ component, succeed }) => {
        succeed(() =>
            requestAnimationFrame(() => {
                const error = [...component.el.querySelectorAll('[data-flux-error]:not(.hidden)')]
                    // offsetParent null = dentro un ramo nascosto (modale chiusa, breakpoint).
                    .find((el) => el.offsetParent !== null && el.textContent.trim() !== '');

                if (!error) return;

                // Il field intero, così label e controllo entrano in scena col messaggio.
                const target = error.closest('[data-flux-field]') ?? error;
                const box = target.getBoundingClientRect();

                // Già sotto gli occhi dell'utente: nessuno scroll a sorpresa.
                if (box.top >= 0 && box.bottom <= window.innerHeight) return;

                // 'instant', non 'smooth': l'animazione parte subito dopo il morph
                // di Livewire e resta a metà (verificato in browser, scroll nullo).
                target.scrollIntoView({ block: 'center', behavior: 'instant' });
            }),
        );
    });
});
