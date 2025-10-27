<?php

namespace Anymodule\Agentmodule\Services\ApiService\Response\Projects;

class PreferredModelsDTO
{
    public function __construct(
        public string $name,
        public string $generation_type
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            generation_type: $data['generation_type']
        );
    }
}
