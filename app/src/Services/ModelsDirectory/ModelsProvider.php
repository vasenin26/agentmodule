<?php

namespace Anymodule\Agentmodule\Services\ModelsDirectory;

use Anymodule\Agentmodule\Entity\ModelMeta;
use Anymodule\Agentmodule\Services\ModelsDirectory\Exception\ModelNotFound;

class ModelsProvider
{
    private array $models;

    public function __construct()
    {
        $this->models = include(__DIR__ . '/models.php');
    }

    public function get(string $name): ModelMeta
    {
        $opts = $this->models[$name] ?? null;

        if (null === $opts) {
            throw new ModelNotFound();
        }

        return new ModelMeta($name, $opts['context']);
    }
}