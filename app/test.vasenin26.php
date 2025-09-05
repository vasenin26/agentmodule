<?php

use Anymodule\Agentmodule\Entity\LLMResult;
use Anymodule\Agentmodule\Services\ApiService\Service;
use Ramsey\Uuid\Uuid;

require __DIR__ . '/vendor/autoload.php';

$api = new Service("http://docmodule-development-1:8000/api");
$uuid = Uuid::fromString("9d00a734-865c-4032-9915-0ad86d2204d7");

$task = $api->getTask($uuid);

$api->sendResult($uuid, $task->id, new LLMResult(
    answer: 'suck',
    messages: [],
    prompt_tokens: 0,
    completion_tokens: 0,
    total_tokens: 0
));