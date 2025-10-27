<?php

namespace Anymodule\Agentmodule\Services\ApiService\Response\Projects;

use Anymodule\Agentmodule\Services\ApiService\Response\ResponseInterface;

class PreferredModelsListDTO implements ResponseInterface
{
    /**
     * @var PreferredModelsDTO[]
     */
    private array $models;

    public function __construct(array $models)
    {
        $this->models = $models;
    }

    public static function fromArray(array $data): self
    {
        $models = [];
        foreach ($data as $modelData) {
            $models[] = PreferredModelsDTO::fromArray($modelData);
        }
        return new self($models);
    }

    /**
     * @return PreferredModelsDTO[]
     */
    public function getModels(): array
    {
        return $this->models;
    }

    /**
     * Возвращает массив в формате generation_type => model_name
     */
    public function getModelsAsArray(): array
    {
        $result = [];
        foreach ($this->models as $model) {
            $result[$model->generation_type] = $model->name;
        }
        return $result;
    }
}
