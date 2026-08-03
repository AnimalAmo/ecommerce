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
// il bottone, e il click sembra non fare niente. Portiamo a schermo il primo
// errore visibile del pezzo di DOM che ha appena risposto.
const scrollToFirstError = (root) =>
    requestAnimationFrame(() => {
        const error = [...root.querySelectorAll('[data-flux-error]:not(.hidden)')]
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
    });

document.addEventListener('livewire:init', () => {
    // Errore già presente al primo render: succede quando un componente lo
    // aggiunge in mount() dopo un redirect (iscrizione partner, email di un
    // altro account). Lì non c'è nessun commit a cui agganciarsi.
    scrollToFirstError(document);

    Livewire.hook('commit', ({ component, succeed }) => {
        succeed(() => scrollToFirstError(component.el));
    });
});
