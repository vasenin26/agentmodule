<?php

namespace Anymodule\Agentmodule\Interface;

interface MessageTypeValidatorFactoryInterface
{
    public function getValidator(string $messageType): ?MessageTypeValidatorInterface;
    public function getSupportedTypes(): array;
}
