<?php

namespace Anymodule\Agentmodule\Interface;

interface MessageValidatorInterface
{
    public function isValidMessage(array $messageData): bool;
    public function getValidationErrors(array $messageData): array;
}
