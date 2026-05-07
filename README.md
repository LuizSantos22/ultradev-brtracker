# UltraDev_BRTracker

Módulo OpenMage / Magento 1.x para notificação de rastreio de pedidos via **e-mail** e **WhatsApp**.

## Compatibilidade
- OpenMage 20.x / Magento 1.9.x
- PHP 8.2+
- Ultimo 1.19.x (tema)

## Transportadoras suportadas
| Transportadora | URL padrão incluída |
|---|---|
| Correios | ✅ |
| Frenet (multi-carrier) | ✅ |
| Melhor Envio | ✅ |
| JadLog | ✅ |
| Loggi | ✅ |
| Total Express | ✅ |
| Customizadas | ✅ via JSON no admin |

## Provedores WhatsApp
| Provedor | Tipo |
|---|---|
| Evolution API | Self-hosted, gratuito |
| Z-API | SaaS |
| 1msg.io | SaaS |

## Instalação via Composer
```bash
composer require ultradev/magento-brtracker
```

## Instalação manual (modman)
```bash
modman clone https://github.com/LuizSantos22/ultradev-brtracker
```

## Configuração
`Admin → UltraDev → BRTracker → Configurações`

1. Habilitar módulo
2. Configurar remetente de e-mail
3. (Opcional) Habilitar WhatsApp e preencher API
4. Ajustar URLs de rastreio por transportadora

## Eventos notificados
| Evento | E-mail | WhatsApp |
|---|---|---|
| Pedido enviado (shipment criado) | ✅ | ✅ |
| Em trânsito | ✅ | ✅ |
| Saiu para entrega | ✅ | ✅ |
| Entregue | ✅ | ✅ |
| Problema na entrega | ✅ | ✅ |

## Celulares brasileiros
O módulo normaliza automaticamente números para E.164:
- `(11) 98403-9303` → `5511984039303`
- Valida o 9º dígito obrigatório de celulares BR

## Log
`Admin → UltraDev → BRTracker → Log de Notificações`
