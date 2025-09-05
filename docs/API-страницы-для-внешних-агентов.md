# API для работы со страницами для внешних агентов

## Обзор

API предоставляет внешним агентам доступ к страницам проекта через JWT аутентификацию. Все запросы требуют валидный Bearer токен агента.

## Аутентификация

Все запросы должны содержать заголовок:
```
Authorization: Bearer YOUR_JWT_TOKEN
```

JWT токен содержит информацию об агенте и проекте, к которому он имеет доступ.

## Базовый URL

```
http://localhost:8000/api/agent
```

## Методы API

### 1. Получение страницы по ID

**Метод:** `GET`  
**Путь:** `/page/{id}`  
**Описание:** Получает полную информацию о странице по её ID

#### Параметры

| Параметр | Тип | Обязательный | Описание |
|----------|-----|--------------|----------|
| id | integer | Да | ID страницы (из URL) |

#### Пример запроса

```bash
curl -X GET "http://localhost:8000/api/agent/page/1" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json"
```

#### Пример ответа

```json
{
  "id": 1,
  "title": "Описание проекта",
  "content": "Содержимое страницы...",
  "files": [
    "https://github.com/user/repo/blob/main/file1.md",
    "https://github.com/user/repo/blob/main/file2.md"
  ]
}
```

#### Коды ответов

- `200 OK` - страница найдена
- `401 Unauthorized` - неверный или отсутствующий JWT токен
- `403 Forbidden` - нет доступа к странице
- `404 Not Found` - страница не найдена

---

### 2. Получение списка всех страниц проекта

**Метод:** `GET`  
**Путь:** `/pages`  
**Описание:** Получает список всех страниц проекта агента

#### Параметры

Нет параметров

#### Пример запроса

```bash
curl -X GET "http://localhost:8000/api/agent/pages" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json"
```

#### Пример ответа

```json
[
  {
    "id": 1,
    "title": "Описание проекта"
  },
  {
    "id": 2,
    "title": "Документация API"
  }
]
```

#### Коды ответов

- `200 OK` - список получен
- `401 Unauthorized` - неверный или отсутствующий JWT токен

---

### 3. Получение иерархии страниц

**Метод:** `GET`  
**Путь:** `/pages/hierarchy`  
**Описание:** Получает иерархическую структуру страниц проекта

#### Параметры

| Параметр | Тип | Обязательный | Описание |
|----------|-----|--------------|----------|
| root_page_id | integer | Нет | ID корневой страницы для построения иерархии (query parameter) |

#### Пример запроса

```bash
# Получить полную иерархию
curl -X GET "http://localhost:8000/api/agent/pages/hierarchy" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json"

# Получить иерархию от конкретной страницы
curl -X GET "http://localhost:8000/api/agent/pages/hierarchy?root_page_id=1" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json"
```

#### Пример ответа

```json
[
  {
    "id": 1,
    "title": "Главная страница",
    "children": [
      {
        "id": 2,
        "title": "Подраздел 1",
        "children": []
      },
      {
        "id": 3,
        "title": "Подраздел 2",
        "children": []
      }
    ]
  }
]
```

#### Коды ответов

- `200 OK` - иерархия получена
- `401 Unauthorized` - неверный или отсутствующий JWT токен
- `403 Forbidden` - нет доступа к корневой странице (если указана)

---

### 4. Получение дочерних страниц

**Метод:** `GET`  
**Путь:** `/page/{id}/children`  
**Описание:** Получает список дочерних страниц для указанной страницы

#### Параметры

| Параметр | Тип | Обязательный | Описание |
|----------|-----|--------------|----------|
| id | integer | Да | ID родительской страницы (из URL) |

#### Пример запроса

```bash
curl -X GET "http://localhost:8000/api/agent/page/1/children" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json"
```

#### Пример ответа

```json
[
  {
    "id": 2,
    "title": "Дочерняя страница 1"
  },
  {
    "id": 3,
    "title": "Дочерняя страница 2"
  }
]
```

#### Коды ответов

- `200 OK` - дочерние страницы получены
- `401 Unauthorized` - неверный или отсутствующий JWT токен
- `403 Forbidden` - нет доступа к странице
- `404 Not Found` - страница не найдена

---

### 5. Получение родительской страницы

**Метод:** `GET`  
**Путь:** `/page/{id}/parent`  
**Описание:** Получает родительскую страницу для указанной страницы

#### Параметры

| Параметр | Тип | Обязательный | Описание |
|----------|-----|--------------|----------|
| id | integer | Да | ID страницы (из URL) |

#### Пример запроса

```bash
curl -X GET "http://localhost:8000/api/agent/page/2/parent" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json"
```

#### Пример ответа

```json
{
  "id": 1,
  "title": "Родительская страница",
  "content": "Содержимое родительской страницы...",
  "files": []
}
```

#### Коды ответов

- `200 OK` - родительская страница найдена
- `204 No Content` - у страницы нет родительской страницы
- `401 Unauthorized` - неверный или отсутствующий JWT токен
- `403 Forbidden` - нет доступа к странице
- `404 Not Found` - страница не найдена

---

### 6. Получение связанных страниц

**Метод:** `GET`  
**Путь:** `/page/{id}/related`  
**Описание:** Получает страницы, связанные с указанной страницей (по общим файлам или иерархии)

#### Параметры

| Параметр | Тип | Обязательный | Описание |
|----------|-----|--------------|----------|
| id | integer | Да | ID страницы (из URL) |

#### Пример запроса

```bash
curl -X GET "http://localhost:8000/api/agent/page/1/related" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json"
```

#### Пример ответа

```json
[
  {
    "id": 2,
    "title": "Связанная страница 1"
  },
  {
    "id": 3,
    "title": "Связанная страница 2"
  }
]
```

#### Коды ответов

- `200 OK` - связанные страницы получены
- `401 Unauthorized` - неверный или отсутствующий JWT токен
- `403 Forbidden` - нет доступа к странице
- `404 Not Found` - страница не найдена

---

### 7. Получение страницы с актуализацией

**Метод:** `GET`  
**Путь:** `/page/{id}/actualization`  
**Описание:** Получает страницу с информацией об актуализации

#### Параметры

| Параметр | Тип | Обязательный | Описание |
|----------|-----|--------------|----------|
| id | integer | Да | ID страницы (из URL) |

#### Пример запроса

```bash
curl -X GET "http://localhost:8000/api/agent/page/1/actualization" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json"
```

#### Пример ответа

```json
{
  "id": 1,
  "title": "Описание проекта",
  "content": "Содержимое страницы...",
  "files": []
}
```

#### Коды ответов

- `200 OK` - страница с актуализацией получена
- `401 Unauthorized` - неверный или отсутствующий JWT токен
- `403 Forbidden` - нет доступа к странице
- `404 Not Found` - страница не найдена

---

### 8. Получение файлов страницы

**Метод:** `GET`  
**Путь:** `/page/{id}/files`  
**Описание:** Получает список файлов, связанных со страницей

#### Параметры

| Параметр | Тип | Обязательный | Описание |
|----------|-----|--------------|----------|
| id | integer | Да | ID страницы (из URL) |

#### Пример запроса

```bash
curl -X GET "http://localhost:8000/api/agent/page/1/files" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json"
```

#### Пример ответа

```json
[
  "https://github.com/user/repo/blob/main/file1.md",
  "https://github.com/user/repo/blob/main/file2.md"
]
```

#### Коды ответов

- `200 OK` - файлы получены
- `401 Unauthorized` - неверный или отсутствующий JWT токен
- `403 Forbidden` - нет доступа к странице
- `404 Not Found` - страница не найдена

---

### 9. Получение истории задач страницы

**Метод:** `GET`  
**Путь:** `/page/{id}/tasks`  
**Описание:** Получает историю задач, связанных со страницей

#### Параметры

| Параметр | Тип | Обязательный | Описание |
|----------|-----|--------------|----------|
| id | integer | Да | ID страницы (из URL) |

#### Пример запроса

```bash
curl -X GET "http://localhost:8000/api/agent/page/1/tasks" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json"
```

#### Пример ответа

```json
[
  {
    "id": 1,
    "status": "completed",
    "created_at": "2024-01-15T10:30:00.000000Z",
    "updated_at": "2024-01-15T11:00:00.000000Z",
    "creator": {
      "id": 1,
      "name": "Иван Иванов",
      "email": "ivan@example.com"
    },
    "techplane": {
      "id": 1,
      "title": "Технический план"
    }
  }
]
```

#### Коды ответов

- `200 OK` - история задач получена
- `401 Unauthorized` - неверный или отсутствующий JWT токен
- `403 Forbidden` - нет доступа к странице
- `404 Not Found` - страница не найдена

---

## Обработка ошибок

### Структура ответа об ошибке

```json
{
  "error": "Описание ошибки",
  "message": "Дополнительная информация об ошибке"
}
```

### Коды ошибок

| Код | Описание |
|-----|----------|
| 200 | Успешный запрос |
| 204 | Успешный запрос, но нет данных (например, нет родительской страницы) |
| 401 | Неверный или отсутствующий JWT токен |
| 403 | Нет доступа к запрашиваемому ресурсу |
| 404 | Ресурс не найден |
| 422 | Ошибка валидации параметров |
| 500 | Внутренняя ошибка сервера |

## Получение JWT токена

Для получения JWT токена агента необходимо:

1. Создать агента через веб-интерфейс проекта
2. Получить токен из базы данных или через административный интерфейс

### Пример получения токена через tinker

```bash
docker compose exec -u local development php artisan tinker --execute="
\$agent = \App\Models\Agent::first();
echo 'Agent UUID: ' . \$agent->uuid . PHP_EOL;
echo 'Agent Token: ' . \$agent->token . PHP_EOL;
echo 'Project ID: ' . \$agent->project_id . PHP_EOL;
"
```

## Ограничения доступа

- Агент может получать доступ только к страницам своего проекта
- Все запросы логируются для аудита
- JWT токены имеют ограниченный срок действия (по умолчанию 1 год)

## Примеры использования

### Получение полной информации о странице

```bash
# Получить страницу по ID
curl -X GET "http://localhost:8000/api/agent/page/1" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

### Навигация по иерархии

```bash
# Получить все страницы
curl -X GET "http://localhost:8000/api/agent/pages" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"

# Получить иерархию
curl -X GET "http://localhost:8000/api/agent/pages/hierarchy" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"

# Получить дочерние страницы
curl -X GET "http://localhost:8000/api/agent/page/1/children" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

### Работа с файлами и задачами

```bash
# Получить файлы страницы
curl -X GET "http://localhost:8000/api/agent/page/1/files" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"

# Получить историю задач
curl -X GET "http://localhost:8000/api/agent/page/1/tasks" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

## Логирование

Все запросы к API логируются с информацией:
- ID агента
- ID проекта
- ID страницы (если применимо)
- Endpoint
- IP адрес

Логи доступны в `storage/logs/laravel.log`.
