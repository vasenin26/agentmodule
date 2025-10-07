#!/bin/bash
set -e

# ═══════════════════════════════════════════════════════════════
# AgentModule Docker Images Builder
# ═══════════════════════════════════════════════════════════════

# Цвета для вывода
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Конфигурация
VERSION=${1:-latest}
REGISTRY=${REGISTRY:-ghcr.io/vasenin26}
IMAGE_NAME=agentmodule

echo -e "${BLUE}════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}  AgentModule - Building Docker Images${NC}"
echo -e "${BLUE}════════════════════════════════════════════════════${NC}"
echo ""
echo -e "${GREEN}Version:${NC} $VERSION"
echo -e "${GREEN}Registry:${NC} $REGISTRY"
echo ""

# Проверка что мы в корне проекта
if [ ! -f "docker/agent/Dockerfile" ]; then
    echo -e "${RED}ERROR: docker/agent/Dockerfile not found${NC}"
    echo -e "${RED}Please run this script from project root${NC}"
    exit 1
fi

# ─────────────────────────────────────────────────────────────
# Build Development Image
# ─────────────────────────────────────────────────────────────
echo -e "${YELLOW}[1/3]${NC} Building ${BLUE}development${NC} image..."
docker build \
    --target develop \
    --tag $REGISTRY/$IMAGE_NAME:develop \
    --file docker/agent/Dockerfile \
    --build-arg BUILD_DATE=$(date -u +"%Y-%m-%dT%H:%M:%SZ") \
    --build-arg VERSION=$VERSION \
    .

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Development image built successfully${NC}"
else
    echo -e "${RED}❌ Failed to build development image${NC}"
    exit 1
fi
echo ""

# ─────────────────────────────────────────────────────────────
# Build Standalone Image
# ─────────────────────────────────────────────────────────────
echo -e "${YELLOW}[2/3]${NC} Building ${BLUE}standalone${NC} image..."
docker build \
    --target standalone \
    --tag $REGISTRY/$IMAGE_NAME:$VERSION-standalone \
    --tag $REGISTRY/$IMAGE_NAME:standalone \
    --file docker/agent/Dockerfile \
    --build-arg BUILD_DATE=$(date -u +"%Y-%m-%dT%H:%M:%SZ") \
    --build-arg VERSION=$VERSION \
    .

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Standalone image built successfully${NC}"
else
    echo -e "${RED}❌ Failed to build standalone image${NC}"
    exit 1
fi
echo ""

# ─────────────────────────────────────────────────────────────
# Build Orchestrated Image
# ─────────────────────────────────────────────────────────────
echo -e "${YELLOW}[3/3]${NC} Building ${BLUE}orchestrated${NC} image..."
docker build \
    --target orchestrated \
    --tag $REGISTRY/$IMAGE_NAME:$VERSION-orchestrated \
    --tag $REGISTRY/$IMAGE_NAME:$VERSION \
    --tag $REGISTRY/$IMAGE_NAME:orchestrated \
    --tag $REGISTRY/$IMAGE_NAME:latest \
    --file docker/agent/Dockerfile \
    --build-arg BUILD_DATE=$(date -u +"%Y-%m-%dT%H:%M:%SZ") \
    --build-arg VERSION=$VERSION \
    .

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Orchestrated image built successfully${NC}"
else
    echo -e "${RED}❌ Failed to build orchestrated image${NC}"
    exit 1
fi
echo ""

# ─────────────────────────────────────────────────────────────
# Summary
# ─────────────────────────────────────────────────────────────
echo -e "${GREEN}════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}  ✅ All images built successfully!${NC}"
echo -e "${GREEN}════════════════════════════════════════════════════${NC}"
echo ""
echo -e "${BLUE}Built images:${NC}"
echo -e "  📦 ${GREEN}Development:${NC}   $REGISTRY/$IMAGE_NAME:develop"
echo -e "  📦 ${GREEN}Standalone:${NC}    $REGISTRY/$IMAGE_NAME:$VERSION-standalone"
echo -e "  📦 ${GREEN}                $REGISTRY/$IMAGE_NAME:standalone"
echo -e "  📦 ${GREEN}Orchestrated:${NC}  $REGISTRY/$IMAGE_NAME:$VERSION-orchestrated"
echo -e "  📦 ${GREEN}                $REGISTRY/$IMAGE_NAME:$VERSION"
echo -e "  📦 ${GREEN}                $REGISTRY/$IMAGE_NAME:orchestrated"
echo -e "  📦 ${GREEN}                $REGISTRY/$IMAGE_NAME:latest"
echo ""
echo -e "${BLUE}Next steps:${NC}"
echo -e "  1. Test images locally:"
echo -e "     ${YELLOW}docker run --rm $REGISTRY/$IMAGE_NAME:standalone${NC}"
echo -e "     ${YELLOW}docker run --rm -e TASK_ID=123 -e AGENT_UUID=456 -e AGENT_ID=789 $REGISTRY/$IMAGE_NAME:orchestrated${NC}"
echo -e "  2. Push images to registry:"
echo -e "     ${YELLOW}./docker/agent/push.sh $VERSION${NC}"
echo ""

