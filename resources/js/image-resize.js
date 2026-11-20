// Riduce le foto nel browser prima che Livewire le carichi.
//
// Una foto da telefono pesa 3-7 MB e Livewire manda tutte quelle scelte in
// una sola richiesta: sette foto superavano il limite del server (413) e il
// partner leggeva solo «Caricamento non riuscito» (test in produzione del
// 20/11/2026). E quelle che passavano finivano sul sito intere, 3,4 MB l'una.
//
// Ogni flux:file-upload (scelta da file o trascinamento) passa da addFiles()
// dell'elemento <ui-file-upload>: lo si avvolge una volta qui, così vale per
// tutti i caricamenti di foto (wizard partner, stanze, pannello admin) senza
// toccare le viste. Un file che non si riesce a ridurre passa com'era: il
// limite per foto resta lato server (`max:8192`).

const MAX_SIDE = 2000;
const QUALITY = 0.82;
// Sotto questa soglia la foto è già leggera: ridurla costa tempo e qualità.
const SKIP_BELOW_BYTES = 500 * 1024;
// GIF animate e SVG non si toccano: il canvas le appiattirebbe.
const RESIZABLE = /^image\/(jpeg|png|webp|heic|heif)$/i;

async function decode(file) {
    // imageOrientation: la rotazione EXIF delle foto da telefono si applica ai
    // pixel, altrimenti una foto verticale verrebbe salvata coricata.
    if ('createImageBitmap' in window) {
        try {
            return await createImageBitmap(file, { imageOrientation: 'from-image' });
        } catch {
            // Formato che createImageBitmap non legge (HEIC fuori da Safari): si prova con <img>.
        }
    }

    const url = URL.createObjectURL(file);

    try {
        const img = new Image();
        img.src = url;
        await img.decode();

        return img;
    } finally {
        URL.revokeObjectURL(url);
    }
}

export async function shrinkImage(file) {
    if (!(file instanceof File) || !RESIZABLE.test(file.type) || file.size < SKIP_BELOW_BYTES) {
        return file;
    }

    try {
        const image = await decode(file);
        const width = image.width;
        const height = image.height;
        const scale = Math.min(1, MAX_SIDE / Math.max(width, height));

        const canvas = document.createElement('canvas');
        canvas.width = Math.round(width * scale);
        canvas.height = Math.round(height * scale);

        const ctx = canvas.getContext('2d');
        // JPEG non ha trasparenza: un PNG scontornato verrebbe nero dietro.
        ctx.fillStyle = '#fff';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        ctx.drawImage(image, 0, 0, canvas.width, canvas.height);
        image.close?.();

        const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', QUALITY));

        // Nessun guadagno (foto già compressa e piccola): si tiene l'originale.
        if (!blob || blob.size >= file.size) {
            return file;
        }

        const name = file.name.replace(/\.[^.]+$/, '') + '.jpg';

        return new File([blob], name, { type: 'image/jpeg', lastModified: file.lastModified });
    } catch {
        return file;
    }
}

customElements.whenDefined('ui-file-upload').then(() => {
    const proto = customElements.get('ui-file-upload').prototype;
    const addFiles = proto.addFiles;

    if (typeof addFiles !== 'function' || proto.addFiles.__shrinks) {
        return;
    }

    proto.addFiles = function (newFiles) {
        const files = Array.from(newFiles);

        // Stesso stato di caricamento che Flux mette durante l'upload, così
        // il partner vede che qualcosa sta succedendo anche mentre si riduce.
        this.setAttribute('data-loading', '');

        Promise.all(files.map(shrinkImage))
            .then((shrunk) => addFiles.call(this, shrunk))
            .catch(() => addFiles.call(this, files))
            .finally(() => {
                // Livewire rimette data-loading con livewire-upload-start: qui
                // si toglie solo se nessun upload è partito.
                if (!this.disabled) this.removeAttribute('data-loading');
            });
    };

    proto.addFiles.__shrinks = true;
});
