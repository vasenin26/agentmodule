<?php

namespace Anymodule\Agentmodule\Application\Workflow;

use Anymodule\Agentmodule\Services\Workflows\Interface\Context;

class ContextRouter
{
    private $rules = null;

    public function __construct(mixed $rules)
    {
        $this->rules = $rules;
    }

    public function route(Context $ctx): ?string
    {
        if(is_callable($this->rules)) {
            return call_user_func($this->rules, $ctx);
        }

        return null;
    }

}