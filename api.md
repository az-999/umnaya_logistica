# API-контракт (реализация)

Базовый URL: `http://localhost:8080/api/v1`

Проверка сервиса: `GET /` — JSON со статусом, окружением и версией.

Документация: [docs/openapi.yaml](docs/openapi.yaml), [Postman-коллекция](docs/postman/notification-service.postman_collection.json).

## 1. Массовая рассылка

`POST /api/v1/notifications/bulk`

Запускает отправку SMS или Email одному или нескольким подписчикам.

**Заголовки**

| Заголовок | Обязательный | Описание |
|-----------|--------------|----------|
| `Content-Type` | да | `application/json` |
| `Idempotency-Key` | нет | Ключ идемпотентности; повтор с тем же ключом вернёт уже созданные уведомления |

**Тело запроса**

```json
{
  "channel": "sms",
  "message": "Ваш код: 1234",
  "recipient_ids": ["sub-001", "sub-002"],
  "priority": "transactional"
}
```

| Поле | Тип | Обязательное | Описание |
|------|-----|--------------|----------|
| `channel` | string | да | `sms` или `email` |
| `message` | string | да | Текст сообщения, до 5000 символов |
| `recipient_ids` | string[] | да | ID подписчиков, от 1 до 1000 |
| `priority` | string | нет | `transactional` или `marketing` (по умолчанию `marketing`) |

**Поведение по приоритету**

| `priority` | Обработка |
|------------|-----------|
| `transactional` | Немедленная отправка в процессе API (`dispatchSync`), без RabbitMQ |
| `marketing` | Задача в очередь RabbitMQ `notifications.marketing`, обрабатывается worker |

**Ответы**

| Код | Когда |
|-----|-------|
| `202 Accepted` | Новый запрос принят |
| `200 OK` | Повтор идемпотентного запроса — возвращены существующие уведомления |
| `422 Unprocessable Entity` | Ошибка валидации |

**Тело ответа**

```json
{
  "data": [
    {
      "id": "a1faf8c2-efe7-4ac3-8073-5b047640222e",
      "subscriber_id": "sub-001",
      "channel": "sms",
      "message": "Ваш код: 1234",
      "status": "sent",
      "priority": "transactional",
      "provider_ref": "sms-05843ca9b4ee6e62f9533d9c76ff6939",
      "error_message": null,
      "attempts": 1,
      "created_at": "2026-06-09T08:14:34+00:00",
      "sent_at": "2026-06-09T08:14:34+00:00",
      "delivered_at": null
    }
  ]
}
```

**Статусы уведомления:** `queued`, `sent`, `delivered`, `rejected`.

---

## 2. История уведомлений подписчика

`GET /api/v1/subscribers/{subscriberId}/notifications`

Возвращает историю и текущий статус всех уведомлений подписчика с пагинацией.

**Параметры пути**

| Параметр | Описание |
|----------|----------|
| `subscriberId` | Идентификатор подписчика |

**Query-параметры**

| Параметр | Тип | Описание |
|----------|-----|----------|
| `status` | string | Фильтр: `queued`, `sent`, `delivered`, `rejected` |
| `channel` | string | Фильтр: `sms`, `email` |
| `page` | integer | Номер страницы (с 1) |
| `per_page` | integer | Размер страницы, 1–100 (по умолчанию 20) |

**Ответ `200 OK`**

```json
{
  "data": [
    {
      "id": "a1faf8c2-efe7-4ac3-8073-5b047640222e",
      "subscriber_id": "sub-001",
      "channel": "sms",
      "message": "Ваш код: 1234",
      "status": "delivered",
      "priority": "transactional",
      "provider_ref": "sms-05843ca9b4ee6e62f9533d9c76ff6939",
      "error_message": null,
      "attempts": 1,
      "created_at": "2026-06-09T08:14:34+00:00",
      "sent_at": "2026-06-09T08:14:34+00:00",
      "delivered_at": "2026-06-09T08:14:36+00:00"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 20,
    "total": 1
  }
}
```

---

## 3. Callback доставки (mock-провайдер)

`POST /api/v1/notifications/{id}/delivery-callback`

Внутренний webhook: имитирует подтверждение или отклонение доставки от внешнего провайдера.

**Параметры пути**

| Параметр | Описание |
|----------|----------|
| `id` | UUID уведомления |

**Тело запроса**

```json
{
  "status": "delivered"
}
```

| Поле | Значения |
|------|----------|
| `status` | `delivered` — подтверждено провайдером; `rejected` — отброшено |

**Ответы**

| Код | Когда |
|-----|-------|
| `200 OK` | Статус обновлён, в `data` — актуальное уведомление |
| `404 Not Found` | Уведомление не найдено |
| `422 Unprocessable Entity` | Ошибка валидации |

---

## Идемпотентность bulk-запроса

1. Redis: блокировка ключа `Idempotency-Key` на 24 ч.
2. Redis: кэш ответа с ID созданных уведомлений.
3. PostgreSQL: unique `(idempotency_key, subscriber_id)`.

При повторном запросе с тем же ключом API возвращает **200** и те же уведомления без повторной отправки.
