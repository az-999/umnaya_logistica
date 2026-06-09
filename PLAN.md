# Микросервис уведомлений — план реализации

## Архитектура: один Laravel, два deployable-сервиса

Принцип: **единый codebase** (`services/notification/`) + **два независимых процесса** в docker-compose. Один Docker-образ, разные entrypoint. Основа для Kubernetes: API и Worker — отдельные Deployment/Pod с независимым `replicas`.

| Роль | Контейнер | Процесс | Масштабирование |
|------|-----------|---------|-----------------|
| API | `notification-api` | `php artisan serve` | по HTTP-нагрузке |
| Worker | `notification-worker` | `php artisan queue:work rabbitmq` | по глубине очереди |

```bash
docker compose up --scale notification-worker=3
```

## Структура репозитория

```
umnaya_logistica/
├── docker-compose.yml
├── .env.example
├── PLAN.md
├── README.md
├── docs/
│   ├── openapi.yaml
│   └── postman/
└── services/
    ├── notification/     # Единый Laravel 11
    ├── api/              # Dockerfile + entrypoint API
    ├── worker/           # Dockerfile + entrypoint Worker
    └── Dockerfile        # Multi-stage build (base → api / worker)
```

## API

- `POST /api/v1/notifications/bulk` — массовая рассылка (заголовок `Idempotency-Key`)
- `GET /api/v1/subscribers/{id}/notifications` — история и статусы
- `POST /api/v1/notifications/{id}/delivery-callback` — callback провайдера

## Приоритеты

| Приоритет | Поведение |
|-----------|-----------|
| `transactional` | `dispatchSync` — вне очереди |
| `marketing` | RabbitMQ `notifications.marketing` |

## Надёжность

- At-least-once: durable RabbitMQ + retry (3 попытки, backoff 10/60/300)
- Exactly-once (бизнес): unique `(idempotency_key, subscriber_id)` + Redis
- Идемпотентность: `Cache::add` + кэш ответа 24ч

## Стек

Laravel 11, PostgreSQL, RabbitMQ (`vladimir-yuldashev/laravel-queue-rabbitmq`), Redis, PHPUnit.
