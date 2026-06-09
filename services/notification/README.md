# Notification Service (Laravel)

Микросервис массовых уведомлений (SMS/Email). Запуск и архитектура — в [корневом README](../../README.md).

Полное описание API: [api.md](../../api.md), OpenAPI: [docs/openapi.yaml](../../docs/openapi.yaml).

## Аутентификация

Все эндпоинты `/api/v1/*` требуют заголовок `X-Api-Key` со значением из `API_KEY` (корневой `.env`).

## Примеры API

### Массовая SMS-рассылка (transactional)

```bash
curl -X POST http://localhost:8080/api/v1/notifications/bulk \
  -H "Content-Type: application/json" \
  -H "X-Api-Key: change-me" \
  -H "Idempotency-Key: $(uuidgen)" \
  -d '{
    "channel": "sms",
    "message": "Ваш код: 1234",
    "recipient_ids": ["sub-001"],
    "priority": "transactional"
  }'
```

### История уведомлений подписчика

```bash
curl "http://localhost:8080/api/v1/subscribers/sub-001/notifications?status=sent" \
  -H "X-Api-Key: change-me"
```

### Callback доставки

```bash
curl -X POST "http://localhost:8080/api/v1/notifications/{notification_id}/delivery-callback" \
  -H "Content-Type: application/json" \
  -H "X-Api-Key: change-me" \
  -d '{"status": "delivered"}'
```

## Тесты

```bash
docker compose run --rm --no-deps --entrypoint php notification-api artisan test
```
