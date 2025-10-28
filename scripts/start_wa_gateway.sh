#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
mkdir -p logs payment_whatsapp_module/sessions
nohup node payment_whatsapp_module/server.js > logs/wa_gateway.log 2>&1 &
echo $! > logs/wa_gateway.pid
echo "WA gateway started (PID $(cat logs/wa_gateway.pid))"