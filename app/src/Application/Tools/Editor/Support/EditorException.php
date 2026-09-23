<?php

namespace Anymodule\Agentmodule\Application\Tools\Editor\Support;

/**
 * Ошибка, текст которой возвращается модели как есть.
 * Сообщение должно объяснять, что пошло не так и как это исправить.
 */
class EditorException extends \RuntimeException
{
    public function __construct(
        string $message,
        public readonly string $errorCode,
        public readonly array $details = [],
    ) {
        parent::__construct($message);
    }
}
