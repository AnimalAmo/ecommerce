---
name: pre-commit-check
description: Quando il lavoro su AnimalAmo è finito e si sta per committare — sequenza canonica di verifica: Pint, test dentro la VM Homestead (il php dell'host è troppo vecchio), build asset se servono, formato del messaggio di commit. Usare SEMPRE prima di ogni commit, anche per modifiche piccole.
---

# Checklist pre-commit AnimalAmo

Esegui in ordine. Non dichiarare "fatto" senza l'output di ogni passo.

## 1. Pint (host, ok)

```bash
vendor/bin/pint --dirty
```

## 2. Test — SOLO dentro la VM

Il php dell'host è 8.2, l'app richiede ≥8.3: `php artisan test` sull'host fallisce o mente. Sempre via ssh:

```bash
ssh vagrant@192.168.56.56 'cd /home/vagrant/Code/algomera/animal_amo/ecommerce && php artisan test'
```

Test singolo: aggiungi `--filter=NomeTest`. Suite completa attesa verde (~620 test). Un test rosso = si sistema prima di committare, non si committa "con nota".

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
