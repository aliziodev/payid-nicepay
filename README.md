# PayID Nicepay Driver

Driver Nicepay untuk aliziodev/payid.

Package ini mengikuti fitur API pada SDK official Nicepay (SNAP + V2) dengan pola:
- API manager PayID untuk flow generik: charge, status, webhook
- Extension method driver untuk flow Nicepay-specific yang lebih luas

## Cakupan fitur utama

- Redirect Payment (via manager charge)
- Status / inquiry
- Webhook verification + parsing
- SNAP: access token, VA, ewallet, QRIS, payout
- V2: VA, CVS, Payloan, QRIS, Card, Ewallet
- Additional: verify signature SHA256 helper

## Status

- Stable (production-ready)

## Instalasi

```bash
composer require aliziodev/payid
composer require aliziodev/payid-nicepay
```

## Lisensi

MIT
