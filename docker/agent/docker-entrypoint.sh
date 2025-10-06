#!/bin/sh
set -e

# Цвета для логов
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo "${GREEN}═══════════════════════════════════════════════════${NC}"
echo "${GREEN}  AgentModule Docker Container Initialization${NC}"
echo "${GREEN}═══════════════════════════════════════════════════${NC}"
echo ""

# Определение режима работы
ORCHESTRATED_MODE=false
if [ -n "$TASK_ID" ] && [ -n "$AGENT_UUID" ] && [ -n "$AGENT_ID" ]; then
    ORCHESTRATED_MODE=true
    echo "${BLUE}[entrypoint]${NC} 🎭 Running in ${GREEN}ORCHESTRATED${NC} mode (managed by AgentManager)"
    echo "${BLUE}[entrypoint]${NC}   📋 TASK_ID: $TASK_ID"
    echo "${BLUE}[entrypoint]${NC}   🆔 AGENT_UUID: $AGENT_UUID"
    echo "${BLUE}[entrypoint]${NC}   🤖 AGENT_ID: $AGENT_ID"
else
    echo "${BLUE}[entrypoint]${NC} 🔄 Running in ${YELLOW}STANDALONE${NC} mode"
fi
echo ""

# Настройка Git пользователя
echo "${BLUE}[entrypoint]${NC} 👤 Configuring Git user..."
if [ -n "$GIT_USER_NAME" ] && [ -n "$GIT_USER_EMAIL" ]; then
    git config --global user.name "$GIT_USER_NAME"
    git config --global user.email "$GIT_USER_EMAIL"
    echo "${GREEN}[entrypoint]${NC} ✅ Git user configured: $GIT_USER_NAME <$GIT_USER_EMAIL>"
else
    echo "${YELLOW}[entrypoint]${NC} ⚠️  GIT_USER_NAME or GIT_USER_EMAIL not set"
    echo "${YELLOW}[entrypoint]${NC} ⚠️  Git operations (commit, push) may fail"
fi
echo ""

# Настройка SSH ключа
echo "${BLUE}[entrypoint]${NC} 🔑 Configuring SSH key..."
if [ -n "$SSH_PRIVATE_KEY" ]; then
    echo "${BLUE}[entrypoint]${NC} 🔐 Initializing project SSH key..."
    
    # Создать директорию .ssh если нет
    mkdir -p ~/.ssh
    chmod 700 ~/.ssh
    
    # Сохранить приватный ключ
    echo "$SSH_PRIVATE_KEY" | tr -d '\r' > ~/.ssh/id_rsa
    chmod 600 ~/.ssh/id_rsa
    
    # Добавить GitHub/GitLab в known_hosts
    echo "${BLUE}[entrypoint]${NC} 📝 Adding GitHub and GitLab to known_hosts..."
    ssh-keyscan -t rsa github.com >> ~/.ssh/known_hosts 2>/dev/null
    ssh-keyscan -t rsa gitlab.com >> ~/.ssh/known_hosts 2>/dev/null
    chmod 644 ~/.ssh/known_hosts
    
    echo "${GREEN}[entrypoint]${NC} ✅ SSH key initialized successfully"
    echo "${GREEN}[entrypoint]${NC}    Location: ~/.ssh/id_rsa"
else
    if [ "$ORCHESTRATED_MODE" = true ]; then
        echo "${RED}[entrypoint]${NC} ❌ ERROR: SSH_PRIVATE_KEY not set in orchestrated mode"
        echo "${RED}[entrypoint]${NC} ❌ Project SSH key is REQUIRED for orchestrated mode"
        echo "${RED}[entrypoint]${NC} ❌ Container will exit"
        exit 1
    else
        echo "${YELLOW}[entrypoint]${NC} ⚠️  SSH_PRIVATE_KEY not set"
        echo "${YELLOW}[entrypoint]${NC} ⚠️  Private Git repositories will not be accessible"
    fi
fi
echo ""

echo "${GREEN}═══════════════════════════════════════════════════${NC}"
echo "${GREEN}  Initialization complete. Starting application...${NC}"
echo "${GREEN}═══════════════════════════════════════════════════${NC}"
echo ""

# Запустить основное приложение (передать все аргументы CMD)
exec "$@"
