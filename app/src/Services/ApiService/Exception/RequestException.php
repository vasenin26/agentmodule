<?php

namespace Anymodule\Agentmodule\Services\ApiService\Exception;

class RequestException extends \Exception
{
    public function __construct(
        string $method,
        string $url,
        string $error,
    )
    {
        parent::__construct(
            "$method $url $error",
        );
    }
}