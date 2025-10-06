# 🚀 Быстрый старт: Два режима работы агента

## 📦 Два Docker образа

### 1️⃣ Standalone образ - текущая версия
**Тег:** `agentmodule:standalone`

Сам получает задачи в цикле, работает автономно.

```bash
# Сборка
docker build --target standalone -t agentmodule:standalone .

# Запуск
docker run -d \
  -e API_HOST=https://api.example.com \
  -e API_TOKEN=your-token \
  agentmodule:standalone
```

### 2️⃣ Orchestrated образ - для оркестратора
**Тег:** `agentmodule:latest` / `agentmodule:orchestrated`

Получает задачу от оркестратора, выполняет и завершается.

```bash
# Сборка
docker build --target orchestrated -t agentmodule:orchestrated .

# Запуск (делает оркестратор)
docker run --rm \
  -e TASK_ID=task-123 \
  -e AGENT_UUID=uuid-456 \
  -e AGENT_ID=agent-789 \
  -e API_TOKEN=token-abc \
  -e SSH_PRIVATE_KEY="$(cat key.pem)" \
  -e API_HOST=https://api.example.com \
  agentmodule:orchestrated
```

---

## 🔧 Быстрая сборка обоих образов

```bash
# Через скрипт (после реализации)
./docker/agent/build.sh v1.0.0

# Вручную
docker build --target standalone -t agentmodule:standalone .
docker build --target orchestrated -t agentmodule:orchestrated .
```

---

## 📊 Сравнение

| Что | Standalone | Orchestrated |
|-----|-----------|--------------|
| **Получение задач** | Сам в цикле | От оркестратора через ENV |
| **Количество задач** | Много (цикл) | Одна |
| **SSH ключи** | Генерирует сам | От оркестратора |
| **Завершение** | Не завершается | Exit 0 или 1 |
| **Использование** | Разработка, автономная работа | Управление оркестратором |

---

## 📖 Полная документация

См. [AGENT_ORCHESTRATOR_INTEGRATION_PLAN.md](AGENT_ORCHESTRATOR_INTEGRATION_PLAN.md)

**Этапы реализации:**
1. Создать `OrchestratedRunner.php`
2. Добавить метод `getTaskByUuid()` в API
3. Обновить работу с SSH ключами
4. Создать multi-stage Dockerfile
5. Тестирование

**Время реализации:** 4-5 дней

---

## ✅ Что нужно сделать

- [ ] Прочитать [AGENT_ORCHESTRATOR_INTEGRATION_PLAN.md](AGENT_ORCHESTRATOR_INTEGRATION_PLAN.md)
- [ ] Реализовать изменения по плану
- [ ] Собрать оба образа
- [ ] Протестировать standalone режим
- [ ] Протестировать orchestrated режим
- [ ] Настроить GitHub Actions для автоматической сборки
- [ ] Обновить документацию

---

**Дата:** 2025-10-06  
**Статус:** ✅ План готов к реализации

