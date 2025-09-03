# Инструкция по созданию новых классов запросов к API

## Обзор архитектуры

Система API запросов построена на основе интерфейса `RequestInterface` и состоит из следующих компонентов:

- **RequestInterface** - базовый интерфейс для всех запросов
- **ApiClient** - клиент для выполнения HTTP запросов через GuzzleHttp
- **Response** - класс-обертка для ответов API
- **Классы запросов** - конкретные реализации для различных API методов

## Структура папок

```
Request/
├── RequestInterface.php      # Базовый интерфейс
├── Tasks/                   # Запросы для работы с задачами
│   └── GetTask.php
└── Pages/                   # Запросы для работы со страницами
    └── GetPage.php
```

## Базовый интерфейс RequestInterface

Все классы запросов должны реализовывать интерфейс `RequestInterface`:

```php
interface RequestInterface
{
    public function getMethod(): string;      // HTTP метод (GET, POST, PUT, DELETE)
    public function getUrl(): string;         // URL эндпоинта (относительный)
    public function getPayload(): array;      // Данные для отправки (тело запроса)
    public function getToken(): ?string;      // Токен авторизации (опционально)
    public function exec(ApiClient $client): ResponseInterface; // Выполнение запроса и возврат DTO
}
```

## Шаги создания нового класса запроса

### 1. Определите категорию запроса

Выберите подходящую папку или создайте новую:
- `Tasks/` - для запросов, связанных с задачами
- `Pages/` - для запросов, связанных со страницами
- Или создайте новую папку для новой категории (например, `Users/`, `Orders/` и т.д.)

### 2. Создайте класс запроса

#### Базовая структура:

```php
<?php

namespace Anymodule\Agentmodule\Services\ApiService\Request\{Category};

use Anymodule\Agentmodule\Services\ApiService\ApiClient;
use Anymodule\Agentmodule\Services\ApiService\Request\RequestInterface;
use Anymodule\Agentmodule\Services\ApiService\Response\ResponseInterface;
use Anymodule\Agentmodule\Services\ApiService\Response\{Category}\{ResponseName}DTO;

final readonly class {RequestName} implements RequestInterface
{
    public function __construct(
        // Параметры, необходимые для запроса
    )
    {
    }

    public function getMethod(): string
    {
        return '{HTTP_METHOD}'; // GET, POST, PUT, DELETE
    }

    public function getUrl(): string
    {
        return '{endpoint_url}'; // Например: "users/{$this->userId}"
    }

    public function getPayload(): array
    {
        return []; // Для GET запросов обычно пустой массив
        // Для POST/PUT запросов - массив с данными
    }

    public function getToken(): ?string
    {
        return null; // Или токен авторизации, если требуется
    }

    public function exec(ApiClient $client): ResponseInterface
    {
        $response = $client->call($this);
        $data = $response->getData(); // Для JSON ответов
        // $content = $response->getBody(); // Для текстовых ответов
        
        // Всегда возвращаем DTO, который реализует ResponseInterface
        return new {ResponseName}DTO(
            // Маппинг данных из ответа в DTO
        );
    }
}
```

### 3. Примеры различных типов запросов

#### GET запрос (получение данных):

```php
final readonly class GetUser implements RequestInterface
{
    public function __construct(
        private int $userId
    ) {}

    public function getMethod(): string
    {
        return 'GET';
    }

    public function getUrl(): string
    {
        return "users/{$this->userId}";
    }

    public function getPayload(): array
    {
        return [];
    }

    public function getToken(): ?string
    {
        return null;
    }

    public function exec(ApiClient $client): ResponseInterface
    {
        $response = $client->call($this);
        $data = $response->getData();
        
        return new UserDTO(
            id: $data['id'] ?? 0,
            name: $data['name'] ?? '',
            email: $data['email'] ?? ''
        );
    }
}
```

#### POST запрос (создание данных):

```php
final readonly class CreateUser implements RequestInterface
{
    public function __construct(
        private string $name,
        private string $email,
        private string $authToken
    ) {}

    public function getMethod(): string
    {
        return 'POST';
    }

    public function getUrl(): string
    {
        return 'users';
    }

    public function getPayload(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email
        ];
    }

    public function getToken(): ?string
    {
        return $this->authToken;
    }

    public function exec(ApiClient $client): ResponseInterface
    {
        $response = $client->call($this);
        $data = $response->getData();
        
        return new UserDTO(
            id: $data['id'] ?? 0,
            name: $data['name'] ?? '',
            email: $data['email'] ?? ''
        );
    }
}
```

#### PUT запрос (обновление данных):

```php
final readonly class UpdateUser implements RequestInterface
{
    public function __construct(
        private int $userId,
        private array $updateData,
        private string $authToken
    ) {}

    public function getMethod(): string
    {
        return 'PUT';
    }

    public function getUrl(): string
    {
        return "users/{$this->userId}";
    }

    public function getPayload(): array
    {
        return $this->updateData;
    }

    public function getToken(): ?string
    {
        return $this->authToken;
    }

    public function exec(ApiClient $client): ResponseInterface
    {
        $response = $client->call($this);
        $data = $response->getData();
        
        return new UpdateUserDTO(
            success: $data['success'] ?? false,
            message: $data['message'] ?? ''
        );
    }
}
```

#### DELETE запрос (удаление данных):

```php
final readonly class DeleteUser implements RequestInterface
{
    public function __construct(
        private int $userId,
        private string $authToken
    ) {}

    public function getMethod(): string
    {
        return 'DELETE';
    }

    public function getUrl(): string
    {
        return "users/{$this->userId}";
    }

    public function getPayload(): array
    {
        return [];
    }

    public function getToken(): ?string
    {
        return $this->authToken;
    }

    public function exec(ApiClient $client): ResponseInterface
    {
        $response = $client->call($this);
        $data = $response->getData();
        
        return new DeleteUserDTO(
            success: $data['success'] ?? true,
            message: $data['message'] ?? 'User deleted successfully'
        );
    }
}
```

## Рекомендации и лучшие практики

### 1. Именование классов
- Используйте описательные имена: `GetUser`, `CreateTask`, `UpdatePage`
- Начинайте с действия: `Get`, `Create`, `Update`, `Delete`
- Используйте PascalCase

### 2. Namespace
- Следуйте структуре: `Anymodule\Agentmodule\Services\ApiService\Request\{Category}`
- Категория должна соответствовать имени папки

### 3. Модификаторы класса
- Используйте `final readonly` для неизменяемых классов запросов
- Это обеспечивает безопасность и предсказуемость

### 4. Конструктор
- Принимайте только необходимые для запроса параметры
- Используйте типизированные параметры
- Делайте свойства `private`

### 5. Создание DTO классов для ответов
- **Обязательно создавайте DTO** для каждого типа ответа API
- DTO должны реализовывать `ResponseInterface`
- Используйте `readonly` классы для неизменяемости
- Поместите DTO в соответствующую папку Response/{Category}/

#### Пример DTO:
```php
<?php

namespace Anymodule\Agentmodule\Services\ApiService\Response\Users;

use Anymodule\Agentmodule\Services\ApiService\Response\ResponseInterface;

readonly class UserDTO implements ResponseInterface
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
    )
    {
    }
}
```

### 6. Обработка ответов в exec()
- `$response->getData()` - для JSON ответов (возвращает массив)
- `$response->getBody()` - для текстовых ответов (возвращает строку)
- **Всегда возвращайте DTO**, который реализует `ResponseInterface`
- Обрабатывайте ошибки перед созданием DTO

### 7. Авторизация
- Если API требует токен, добавьте его в конструктор
- Возвращайте токен в методе `getToken()`
- Если токен не нужен, возвращайте `null`

### 8. Payload для разных методов
- **GET/DELETE**: обычно пустой массив `[]`
- **POST/PUT**: массив с данными для отправки

## Использование созданного класса

```php
// Создание экземпляра запроса
$request = new GetUser(userId: 123);

// Выполнение через ApiClient
$apiClient = new ApiClient('https://api.example.com');
$userDTO = $request->exec($apiClient); // Возвращает UserDTO

// Использование DTO
echo "User ID: " . $userDTO->id;
echo "Name: " . $userDTO->name;
echo "Email: " . $userDTO->email;
```

## Пример создания новой категории запросов

Если вам нужно создать запросы для новой сущности (например, "Users"):

1. **Создайте папку для запросов**: `Request/Users/`
2. **Создайте папку для DTO**: `Response/Users/`
3. **Создайте DTO классы** для всех типов ответов
4. **Создайте классы запросов** в папке Request/Users/
5. **Используйте правильные namespace**:
   - Запросы: `Anymodule\Agentmodule\Services\ApiService\Request\Users`
   - DTO: `Anymodule\Agentmodule\Services\ApiService\Response\Users`

## Проверка

После создания нового класса убедитесь, что:
- [ ] Создан соответствующий DTO класс, реализующий `ResponseInterface`
- [ ] Класс запроса реализует `RequestInterface`
- [ ] Все методы интерфейса реализованы
- [ ] Используется правильный namespace для запроса и DTO
- [ ] Конструктор принимает необходимые параметры
- [ ] Метод `exec()` возвращает DTO (не array/string/mixed)
- [ ] Классы помещены в правильные папки категории
- [ ] Импорты DTO и ResponseInterface добавлены
