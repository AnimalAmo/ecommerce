#!/usr/bin/env bash
# Ultimi eventi Mailgun del dominio configurato in .env — l'equivalente da
# terminale di "Send > Logs" sulla dashboard, senza il selettore di regione da
# sbagliare (endpoint e dominio vengono letti dal .env, quindi sono sempre
# coerenti con quello che l'app sta davvero usando).
#
#   ./docs/mailgun-logs.sh [numero-eventi]
set -euo pipefail

cd "$(dirname "$0")/.."

env_value() { grep -m1 "^$1=" .env | cut -d= -f2- | tr -d '"' | tr -d "'"; }

KEY=$(env_value MAILGUN_SECRET)
DOMAIN=$(env_value MAILGUN_DOMAIN)
ENDPOINT=$(env_value MAILGUN_ENDPOINT)
LIMIT=${1:-15}

if [ -z "$KEY" ] || [ -z "$DOMAIN" ]; then
    echo "MAILGUN_SECRET o MAILGUN_DOMAIN mancanti in .env" >&2
    exit 1
fi

echo "dominio:  $DOMAIN"
echo "endpoint: ${ENDPOINT:-api.eu.mailgun.net}"
echo

curl -sS --max-time 30 -u "api:$KEY" \
    "https://${ENDPOINT:-api.eu.mailgun.net}/v3/${DOMAIN}/events?limit=${LIMIT}" |
    python3 -c '
import sys, json

data = json.load(sys.stdin)

if "items" not in data:
    # Tipicamente {"message": "Domain not found"} = endpoint della regione sbagliata.
    print(data.get("message") or data.get("error") or data)
    raise SystemExit(1)

if not data["items"]:
    print("nessun evento (ritenzione log scaduta, o niente inviato di recente)")
    raise SystemExit

for e in data["items"]:
    event = e.get("event", "?")
    recipient = e.get("recipient") or "-"
    subject = e.get("message", {}).get("headers", {}).get("subject", "")
    ts = e.get("timestamp", 0)
    print(f"{ts:.0f}  {event:12} {recipient:34} {subject}")

    # Su failed/rejected la causa sta in due campi diversi a seconda del tipo
    # di scarto: senza stamparli entrambi si perde la diagnosi vera.
    if event in ("failed", "rejected"):
        reason = e.get("reason") or e.get("reject", {}).get("reason")
        detail = e.get("delivery-status", {}).get("message")
        for line in (reason, detail):
            if line:
                print(f"    -> {line}")
'
