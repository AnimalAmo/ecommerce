#!/usr/bin/env bash
# Confronta i record DNS che Mailgun si aspetta con quelli davvero pubblicati e
# chiede la verifica del dominio. Serve a chiudere il giro DNS senza passare
# dalla dashboard e senza fidarsi del suo "verified" (che resta in cache):
# `dig` interroga i nameserver reali, Mailgun rilegge e aggiorna lo stato.
#
#   ./docs/mailgun-verify.sh [dominio]        # default: MAILGUN_DOMAIN dal .env
#
# Uscita 0 solo se il dominio risulta "active" a fine controllo.
set -euo pipefail

cd "$(dirname "$0")/.."

env_value() { grep -m1 "^$1=" .env | cut -d= -f2- | tr -d '"' | tr -d "'"; }

KEY=$(env_value MAILGUN_SECRET)
DOMAIN=${1:-$(env_value MAILGUN_DOMAIN)}
# L'endpoint segue il dominio passato, non il .env: durante la migrazione dal
# sandbox (US) al dominio custom (EU) i due divergono, ed è proprio allora che
# serve questo script. MAILGUN_ENDPOINT nell'ambiente ha comunque la precedenza.
case "$DOMAIN" in
    sandbox*) DEFAULT_ENDPOINT=api.mailgun.net ;;
    *) DEFAULT_ENDPOINT=api.eu.mailgun.net ;;
esac
ENDPOINT=${MAILGUN_ENDPOINT:-$DEFAULT_ENDPOINT}

if [ -z "$KEY" ] || [ -z "$DOMAIN" ]; then
    echo "MAILGUN_SECRET o MAILGUN_DOMAIN mancanti in .env" >&2
    exit 1
fi

echo "dominio:  $DOMAIN"
echo "endpoint: $ENDPOINT"
echo

# La delega NS è il passo che si dimentica: senza, i record esistono su
# DigitalOcean ma nessun resolver al mondo li vede (vedi §8.3bis della doc).
echo "── Delega NS ────────────────────────────────────────────────────────────"
NS=$(dig +short NS "$DOMAIN" || true)
if [ -z "$NS" ]; then
    echo "  nessun NS per $DOMAIN — la zona non è delegata, i record sono invisibili"
    echo "  (atteso solo con la rotta B; con la rotta A i record stanno nella zona madre)"
else
    echo "$NS" | sed 's/^/  /'
fi
echo

echo "── Record attesi vs pubblicati ──────────────────────────────────────────"
PAYLOAD=$(mktemp)
trap 'rm -f "$PAYLOAD"' EXIT
curl -sS --max-time 30 -u "api:$KEY" "https://${ENDPOINT}/v3/domains/${DOMAIN}" >"$PAYLOAD"

MAILGUN_PAYLOAD="$PAYLOAD" python3 <<'PY' || echo "  → record mancanti: pubblicali prima di chiedere la verifica"
import json, os, subprocess, sys

with open(os.environ["MAILGUN_PAYLOAD"]) as handle:
    data = json.load(handle)

if "domain" not in data:
    print(data.get("message") or data.get("error") or data)
    raise SystemExit(1)

records = data.get("sending_dns_records", []) + data.get("receiving_dns_records", [])
missing = 0

for record in records:
    kind = record.get("record_type", "?")
    name = record.get("name") or data["domain"]["name"]
    expected = record.get("value", "")
    valid = record.get("valid", "unknown")

    published = subprocess.run(
        ["dig", "+short", kind, name],
        capture_output=True, text=True,
    ).stdout.strip()

    # Un TXT oltre i 255 caratteri (il DKIM a 2048 bit lo è) viaggia spezzato in
    # più stringhe: dig lo restituisce come "pezzo1" "pezzo2". Togliendo virgolette
    # e spazi si confronta il valore ricomposto, che è quello che conta.
    def normalize(value):
        return value.replace(chr(34), "").replace(" ", "").replace("\n", "")

    needle = expected.split(" ")[-1] if kind == "MX" else expected
    ok = bool(needle) and normalize(needle) in normalize(published)

    if not ok:
        missing += 1

    print(f"  [{'ok' if ok else '--'}] {kind:6} {name}")
    print(f"        atteso     {expected[:110]}")
    shown = published.replace("\n", " ")[:110] or "(niente)"
    print(f"        pubblicato {shown}   mailgun: {valid}")

print()
print(f"  {len(records) - missing}/{len(records)} record corrispondono")
raise SystemExit(1 if missing else 0)
PY

echo
echo "── Verifica su Mailgun ──────────────────────────────────────────────────"
STATE=$(curl -sS --max-time 60 -u "api:$KEY" -X PUT "https://${ENDPOINT}/v3/domains/${DOMAIN}/verify" |
    python3 -c 'import sys, json; print(json.load(sys.stdin).get("domain", {}).get("state", "?"))')

echo "  stato: $STATE"

if [ "$STATE" != "active" ]; then
    echo
    echo "  La propagazione DNS può richiedere fino a 24-48h su Register.it."
    echo "  Rilancia questo script più tardi: è idempotente."
    exit 1
fi

echo
echo "  Dominio verificato. Ora in .env (server):"
echo "    MAIL_MAILER=mailgun"
echo "    MAILGUN_DOMAIN=${DOMAIN}"
echo "    MAILGUN_ENDPOINT=${ENDPOINT}"
echo "    MAIL_FROM_ADDRESS=\"no-reply@${DOMAIN}\""
echo "  poi: php artisan config:clear && php artisan mail:test <indirizzo esterno>"
