# OLT Monitoring Dashboard + Telegram Bot Project

This project is an OLT (Optical Line Terminal) monitoring system with a web dashboard and Telegram bot integration for real-time notifications.

## Architecture
- **Frontend**: React + Material UI (dashboard, tables, charts)
- **Backend**: Node.js + Express (API, OLT integration)
- **Database**: MySQL (OLT data, ONT data, event logs)
- **Bot**: node-telegram-bot-api (notifications & commands)
- **Communication**: SSH/SNMP for OLT polling

## Features
- Real-time ONT status monitoring (online/offline, LOS)
- RX/TX power monitoring with color-coded thresholds
- Cable distance monitoring
- Multi-OLT support
- Telegram notifications for status changes
- Settings page for bot and OLT configuration
- Event logging and history

## Threshold Defaults
- 🟢 Safe: RX -8 dBm to -25 dBm
- 🟡 Warning: -25 dBm to -27 dBm
- 🔴 Danger: < -27 dBm or > -8 dBm
- Distance: Warning > 20 km, Danger > 25 km

## Development Guidelines
- Use async/await for database operations
- Implement proper error handling
- Follow REST API conventions
- Use Material UI components consistently
- Implement responsive design
- Add proper logging for debugging