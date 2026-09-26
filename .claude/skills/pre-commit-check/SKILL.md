---
name: pre-commit-check
description: Quando il lavoro su AnimalAmo è finito e si sta per committare — sequenza canonica di verifica: Pint, test in Docker dentro WSL (sull'host Windows non gira nulla di PHP), build asset se servono, formato del messaggio di commit. Usare SEMPRE prima di ogni commit, anche per modifiche piccole.
---

# Checklist pre-commit AnimalAmo

Esegui in ordine. Non dichiarare "fatto" senza l'output di ogni passo.

Sull'host Windows **non gira niente di PHP**: la sua versione è 7.4, l'app ne vuole ≥8.3 e Pint ≥8.2. Vale per i test *e* per lo stile. La vecchia VM Homestead (192.168.56.56) non esiste più.

Tutto passa da `~/t.sh` dentro WSL: fa l'rsync da `D:` alla copia su filesystem ext4 (`~/aa`) e lancia il comando in Docker (Sail, PHP 8.3).

## 1. Pint

```bash
wsl -d Ubuntu -e bash -lc '~/t.sh --pint --dirty'
```

## 2. Test

```bash
wsl -d Ubuntu -e bash -lc '~/t.sh'
```

Test singolo: `~/t.sh --filter=NomeTest`. Senza container (più veloce per un giro rapido): `~/t.sh --native`.

**Atteso: `20 failed, 1998 passed` in Docker (`20 failed, 1 skipped, 1997 passed` con `--native`).** I 20 rossi sono preesistenti e stanno in tre classi — `PhoneInputTest`, `BecomePartnerFromAccountTest`, `WorkWithUsFlowTest` — tutti per la regola `email:rfc,dns` sulle candidature partner, che senza DNS raggiungibile rifiuta l'email. Un numero diverso da 20 è roba tua: si sistema prima di committare, non si committa "con nota".

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
