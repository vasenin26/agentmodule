<?php

namespace Anymodule\Agentmodule\Services\Workflows\Notifier;

use Anymodule\Agentmodule\Services\Workflows\Interface\Context;
use Anymodule\Agentmodule\Services\Workflows\Interface\StepNotifierInterface;

class NoOpStepNotifier implements StepNotifierInterface
{
    public function notifyStepStart(Context $ctx, string $step): void
    {
    }
}
