<?php

namespace Anymodule\Agentmodule\Application\ModelsDirectory;

use Anymodule\Agentmodule\Application\ModelsDirectory\Exception\ModelNotFound;
use Anymodule\Agentmodule\Entity\ModelMeta;

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
            throw new ModelNotFound('Model ' . $name . ' not found');
        }

        return new ModelMeta($opts['name'], $opts['context']);
    }
}