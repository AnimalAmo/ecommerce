---
name: pre-commit-check
description: Quando il lavoro su AnimalAmo è finito e si sta per committare — sequenza canonica di verifica: lint sintattico, Pint, suite completa (Docker dentro WSL su Windows, VM Homestead su Linux: su nessuno dei due host gira PHP), build asset se servono, formato del messaggio di commit. Usare SEMPRE prima di ogni commit, anche per modifiche piccole.
---

# Checklist pre-commit AnimalAmo

Esegui in ordine. Non dichiarare "fatto" senza l'output di ogni passo.

Su **nessuno dei due host** gira PHP: Windows ha la 7.4, Linux la 8.2 senza `pdo_sqlite`, e l'app vuole ≥8.3 (Pint ≥8.2). Vale per i test *e* per lo stile. Le due macchine hanno due strade diverse, e prima di lanciare qualcosa bisogna sapere su quale si è.

- **Windows**: `~/t.sh` dentro WSL, che fa l'rsync da `D:` alla copia ext4 (`~/aa`) e lancia in Docker (Sail, PHP 8.3).
- **Linux**: la **VM Homestead c'è ed è la strada** — `ssh vagrant@192.168.56.56`, script `../aa-vm-test.sh`, che specchia il repo su disco locale della VM (mai vboxsf) e lancia lì. Un mirror per albero di lavoro: due phpunit sullo stesso mirror si corrompono a vicenda, quindi il primo argomento è il nome del mirror.

## 1. Pint

```bash
# Windows
wsl -d Ubuntu -e bash -lc '~/t.sh --pint --dirty'
# Linux
ssh vagrant@192.168.56.56 'cd ~/Code/animal_amo/ecommerce && vendor/bin/pint --dirty'
```

## 2. Test

```bash
# Windows
wsl -d Ubuntu -e bash -lc '~/t.sh'
# Linux (l'ultimo argomento e i successivi vanno ad artisan test)
ssh vagrant@192.168.56.56 'bash ~/Code/animal_amo/aa-vm-test.sh main .'
ssh vagrant@192.168.56.56 'bash ~/Code/animal_amo/aa-vm-test.sh main . --filter=NomeTest'
```

**Atteso: su Linux zero rossi.** Al 27/09/2026 la suite è a `2094 passed, 1 skipped, 0 failed` (~345s): qualunque rosso è tuo. In Docker su Windows restano **20 rossi ambientali** in tre classi (`PhoneInputTest`, `BecomePartnerFromAccountTest`, `WorkWithUsFlowTest`), tutti per la regola `email:rfc,dns` sulle candidature partner, che senza DNS raggiungibile rifiuta l'email: la VM Linux risolve il DNS, quindi lì quei test girano davvero.

**Prima della suite, il lint sintattico dei file toccati.** Costa secondi e intercetta quello che i test non vedono: un `*/` in mezzo a un docblock (per esempio scrivendo `lang/*/validation.php` in un commento) chiude il commento in anticipo e il file non si parsa più.

```bash
ssh vagrant@192.168.56.56 'cd ~/Code/animal_amo/ecommerce && php -l app/Percorso/File.php'
```

## 3. Asset — NON buildare in locale

In locale gira `npm run dev` (vite hot reload): **non lanciare `npm run build` prima del commit** — richiesta esplicita (20 Lug 2026). Il build serve SOLO in fase di deploy (le classi Tailwind nuove non esistono nei build vecchi: senza build, in produzione lo stile sparisce silenziosamente).

## 4. Commit

- Conventional Commits, in inglese: `feat: …`, `fix: …`, `refactor: …`.
- **Niente** trailer `Co-Authored-By`, `Claude-Session` o altra attribuzione AI (regola esplicita del progetto).
- Non fare `git push` se non richiesto.

```bash
git add <file specifici>   # niente git add -A alla cieca
git commit -m "feat: ..."
```
