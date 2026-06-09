# Notification Service

Микросервис массовых уведомлений (SMS/Email) для тестового задания «Умная Логистика».

**Стек:** Laravel 11, PostgreSQL, RabbitMQ, Redis.

## Архитектура

Один Laravel-код (`services/notification/`), два независимых процесса в Docker:

| Сервис | Роль |
|--------|------|
| `notification-api` | HTTP API, миграции БД |
| `notification-worker` | Потребление очереди RabbitMQ |

Масштабирование воркеров:

```bash
docker compose up --scale notification-worker=3
```

Подробный план: [PLAN.md](PLAN.md).

## Быстрый старт

```bash
cp .env.example .env
# Сгенерируйте APP_KEY (или задайте вручную):
# docker run --rm -v $(pwd)/services/notification:/app -w /app composer:2 php artisan key:generate --show

docker compose up --build
```

API: http://localhost:8080  
RabbitMQ Management: http://localhost:15673 (guest/guest)

Порты на хосте (если 5432/6379/5672 заняты): PostgreSQL `5433`, Redis `6380`, RabbitMQ `5673`.

## Тесты

```bash
docker compose exec notification-api php artisan test
```

## API

| Метод | Endpoint | Описание |
|-------|----------|----------|
| POST | `/api/v1/notifications/bulk` | Массовая рассылка |
| GET | `/api/v1/subscribers/{id}/notifications` | История подписчика |
| POST | `/api/v1/notifications/{id}/delivery-callback` | Callback доставки |

### Пример: массовая SMS-рассылка

```bash
curl -X POST http://localhost:8080/api/v1/notifications/bulk \
  -H "Content-Type: application/json" \
  -H "Idempotency-Key: $(uuidgen)" \
  -d '{
    "channel": "sms",
    "message": "Ваш код: 1234",
    "recipient_ids": ["sub-001"],
    "priority": "transactional"
  }'
```

### Пример: история подписчика

```bash
curl "http://localhost:8080/api/v1/subscribers/sub-001/notifications?status=sent"
```

## Документация API

- **OpenAPI (Swagger):** [docs/openapi.yaml](docs/openapi.yaml) — импорт в [Swagger Editor](https://editor.swagger.io/)
- **Postman:** [docs/postman/notification-service.postman_collection.json](docs/postman/notification-service.postman_collection.json)

## Kubernetes (будущее масштабирование)

- Один Docker-образ → два Deployment (`notification-api`, `notification-worker`)
- API: Service + Ingress
- Worker: HPA/KEDA по метрике глубины очереди RabbitMQ
