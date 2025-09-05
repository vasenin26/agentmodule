<?php

namespace Anymodule\Agentmodule\Services\ApiService\Response\Pages;

use Anymodule\Agentmodule\Services\ApiService\Response\ResponseInterface;

readonly class RelatedPagesResponse implements ResponseInterface
{
    /**
     * @param PageListDTO[] $related
     */
    public function __construct(
        private array $related
    ) {
    }

    /**
     * @return PageListDTO[]
     */
    public function getRelated(): array
    {
        return $this->related;
    }
}
