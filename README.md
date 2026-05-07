# UltraDev_BRTracker

OpenMage / Magento 1.x module for order tracking notifications via **email** and **WhatsApp**.

## Compatibility
- OpenMage 20.x / Magento 1.9.x
- PHP 8.2+
- Ultimo 1.19.x (theme)

## Supported Carriers
| Carrier | Default URL included |
|---|---|
| Correios | ✅ |
| Frenet (multi-carrier) | ✅ |
| Melhor Envio | ✅ |
| JadLog | ✅ |
| Loggi | ✅ |
| Total Express | ✅ |
| Custom carriers | ✅ via JSON in admin |

## WhatsApp Providers
| Provider | Type |
|---|---|
| Evolution API | Self-hosted, free |
| Z-API | SaaS |
| 1msg.io | SaaS |

## Installation via Composer
```bash
composer require ultradev/magento-brtracker
```

## Manual Installation (modman)
```bash
modman clone https://github.com/LuizSantos22/ultradev-brtracker
```

## Configuration
`Admin → UltraDev → BRTracker → Settings`

1. Enable the module
2. Set the email sender identity
3. (Optional) Enable WhatsApp and fill in the API credentials
4. Adjust tracking URLs per carrier

## Notification Events
| Event | Email | WhatsApp |
|---|---|---|
| Order shipped (shipment created) | ✅ | ✅ |
| In transit | ✅ | ✅ |
| Out for delivery | ✅ | ✅ |
| Delivered | ✅ | ✅ |
| Delivery exception | ✅ | ✅ |

## Brazilian Phone Normalization
The module automatically normalizes phone numbers to E.164 format:
- `(11) 98403-9303` → `5511984039303`
- Validates the mandatory 9th digit for Brazilian mobile numbers (DDD + 9 + 8 digits)

## Notification Log
`Admin → UltraDev → BRTracker → Notification Log`

All sent (and failed) notifications are recorded with channel, event, recipient, status and timestamp.

## License
MIT — © UltraDev 
