<?php

namespace Anymodule\Agentmodule\Application\Tools\Tasks;

interface TaskStorageInterface
{
    /**
     * Get all tasks
     * @return array<int, array{id:int,title:string,done:bool}>
     */
    public function list(): array;

    /**
     * Add multiple tasks
     * @param array<int, string> $titles
     * @return array<int, array{id:int,title:string,done:bool}>
     */
    public function addMany(array $titles): array;

    /**
     * Mark a task as completed
     * @param int $id
     * @return array{id:int,title:string,done:bool}|null
     */
    public function complete(int $id): ?array;

    /**
     * Get task statistics
     * @return array{total:int,completed:int,remaining:int}
     */
    public function getStats(): array;

    /**
     * Clear all tasks and reset storage
     */
    public function clear(): void;
}