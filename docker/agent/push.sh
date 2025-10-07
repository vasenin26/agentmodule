#!/bin/bash
set -e

# ═══════════════════════════════════════════════════════════════
# AgentModule Docker Images Publisher
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
echo -e "${BLUE}  AgentModule - Pushing Docker Images${NC}"
echo -e "${BLUE}════════════════════════════════════════════════════${NC}"
echo ""
echo -e "${GREEN}Version:${NC} $VERSION"
echo -e "${GREEN}Registry:${NC} $REGISTRY"
echo ""

# ─────────────────────────────────────────────────────────────
# Push Development Image
# ─────────────────────────────────────────────────────────────
echo -e "${YELLOW}[1/7]${NC} Pushing ${BLUE}development${NC} image..."
docker push $REGISTRY/$IMAGE_NAME:develop
echo -e "${GREEN}✅ Pushed: $REGISTRY/$IMAGE_NAME:develop${NC}"
echo ""

# ─────────────────────────────────────────────────────────────
# Push Standalone Images
# ─────────────────────────────────────────────────────────────
echo -e "${YELLOW}[2/7]${NC} Pushing ${BLUE}standalone${NC} versioned image..."
docker push $REGISTRY/$IMAGE_NAME:$VERSION-standalone
echo -e "${GREEN}✅ Pushed: $REGISTRY/$IMAGE_NAME:$VERSION-standalone${NC}"
echo ""

echo -e "${YELLOW}[3/7]${NC} Pushing ${BLUE}standalone${NC} latest image..."
docker push $REGISTRY/$IMAGE_NAME:standalone
echo -e "${GREEN}✅ Pushed: $REGISTRY/$IMAGE_NAME:standalone${NC}"
echo ""

# ─────────────────────────────────────────────────────────────
# Push Orchestrated Images
# ─────────────────────────────────────────────────────────────
echo -e "${YELLOW}[4/7]${NC} Pushing ${BLUE}orchestrated${NC} versioned image..."
docker push $REGISTRY/$IMAGE_NAME:$VERSION-orchestrated
echo -e "${GREEN}✅ Pushed: $REGISTRY/$IMAGE_NAME:$VERSION-orchestrated${NC}"
echo ""

echo -e "${YELLOW}[5/7]${NC} Pushing ${BLUE}orchestrated${NC} version tag..."
docker push $REGISTRY/$IMAGE_NAME:$VERSION
echo -e "${GREEN}✅ Pushed: $REGISTRY/$IMAGE_NAME:$VERSION${NC}"
echo ""

echo -e "${YELLOW}[6/7]${NC} Pushing ${BLUE}orchestrated${NC} latest..."
docker push $REGISTRY/$IMAGE_NAME:orchestrated
echo -e "${GREEN}✅ Pushed: $REGISTRY/$IMAGE_NAME:orchestrated${NC}"
echo ""

echo -e "${YELLOW}[7/7]${NC} Pushing ${BLUE}latest${NC} tag..."
docker push $REGISTRY/$IMAGE_NAME:latest
echo -e "${GREEN}✅ Pushed: $REGISTRY/$IMAGE_NAME:latest${NC}"
echo ""

# ─────────────────────────────────────────────────────────────
# Summary
# ─────────────────────────────────────────────────────────────
echo -e "${GREEN}════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}  ✅ All images pushed successfully!${NC}"
echo -e "${GREEN}════════════════════════════════════════════════════${NC}"
echo ""
echo -e "${BLUE}Published images:${NC}"
echo -e "  🚀 $REGISTRY/$IMAGE_NAME:develop"
echo -e "  🚀 $REGISTRY/$IMAGE_NAME:$VERSION-standalone"
echo -e "  🚀 $REGISTRY/$IMAGE_NAME:standalone"
echo -e "  🚀 $REGISTRY/$IMAGE_NAME:$VERSION-orchestrated"
echo -e "  🚀 $REGISTRY/$IMAGE_NAME:$VERSION"
echo -e "  🚀 $REGISTRY/$IMAGE_NAME:orchestrated"
echo -e "  🚀 $REGISTRY/$IMAGE_NAME:latest"
echo ""

