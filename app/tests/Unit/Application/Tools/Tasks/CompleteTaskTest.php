<?php

namespace Anymodule\Agentmodule\Tests\Unit\Application\Tools\Tasks;

use Anymodule\Agentmodule\Application\Tools\Tasks\CompleteTask;
use Anymodule\Agentmodule\Application\Tools\Tasks\TasksStorage;
use Anymodule\Agentmodule\Entity\ToolResult;
use PHPUnit\Framework\TestCase;

class CompleteTaskTest extends TestCase
{
    private TasksStorage $storage;
    private CompleteTask $completeTask;

    protected function setUp(): void
    {
        $this->storage = new TasksStorage(sys_get_temp_dir() . '/test_complete_task_' . uniqid() . '.json');
        $this->completeTask = new CompleteTask($this->storage);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->storage->getStoragePath())) {
            unlink($this->storage->getStoragePath());
        }
    }

    public function testGetName(): void
    {
        $this->assertEquals('tasks-complete', $this->completeTask->getName());
    }

    public function testExecuteWithValidId(): void
    {
        // Добавляем задачи
        $this->storage->addMany(['Task 1', 'Task 2', 'Task 3']);
        
        $result = $this->completeTask->execute(['id' => 2]);
        
        $this->assertInstanceOf(ToolResult::class, $result);
        $this->assertTrue($result->status);
        $this->assertEquals('Task completed', $result->message);
        
        $payload = $result->payload;
        $this->assertIsArray($payload);
        $this->assertArrayHasKey('task', $payload);
        $this->assertArrayHasKey('stats', $payload);
        
        // Проверяем завершенную задачу
        $task = $payload['task'];
        $this->assertEquals(2, $task['id']);
        $this->assertEquals('Task 2', $task['title']);
        $this->assertTrue($task['done']);
        
        // Проверяем статистику
        $stats = $payload['stats'];
        $this->assertEquals(3, $stats['total']);
        $this->assertEquals(1, $stats['completed']);
        $this->assertEquals(2, $stats['remaining']);
    }

    public function testExecuteWithNonExistentId(): void
    {
        $this->storage->addMany(['Task 1', 'Task 2']);
        
        $result = $this->completeTask->execute(['id' => 999]);
        
        $this->assertInstanceOf(ToolResult::class, $result);
        $this->assertFalse($result->status);
        $this->assertEquals('Task not found', $result->message);
        $this->assertEquals(['code' => 'TASK_NOT_FOUND'], $result->payload);
    }

    public function testExecuteWithInvalidId(): void
    {
        $result = $this->completeTask->execute(['id' => 0]);
        
        $this->assertInstanceOf(ToolResult::class, $result);
        $this->assertFalse($result->status);
        $this->assertEquals('Invalid id', $result->message);
        $this->assertEquals(['code' => 'INVALID_ID'], $result->payload);
    }

    public function testExecuteWithNegativeId(): void
    {
        $result = $this->completeTask->execute(['id' => -1]);
        
        $this->assertFalse($result->status);
        $this->assertEquals('Invalid id', $result->message);
        $this->assertEquals(['code' => 'INVALID_ID'], $result->payload);
    }

    public function testExecuteWithStringId(): void
    {
        $this->storage->addMany(['Task 1']);
        
        // Строковый ID должен быть приведен к int
        $result = $this->completeTask->execute(['id' => '1']);
        
        $this->assertTrue($result->status);
        $this->assertEquals('Task completed', $result->message);
    }

    public function testExecuteWithFloatId(): void
    {
        $this->storage->addMany(['Task 1']);
        
        // Float ID должен быть приведен к int
        $result = $this->completeTask->execute(['id' => 1.5]);
        
        $this->assertTrue($result->status);
        $this->assertEquals('Task completed', $result->message);
    }

    public function testExecuteWithMissingId(): void
    {
        $result = $this->completeTask->execute([]);
        
        $this->assertFalse($result->status);
        $this->assertEquals('Invalid id', $result->message);
        $this->assertEquals(['code' => 'INVALID_ID'], $result->payload);
    }

    public function testExecuteWithNonNumericId(): void
    {
        $result = $this->completeTask->execute(['id' => 'abc']);
        
        $this->assertFalse($result->status);
        $this->assertEquals('Invalid id', $result->message);
        $this->assertEquals(['code' => 'INVALID_ID'], $result->payload);
    }

    public function testExecuteWithAlreadyCompletedTask(): void
    {
        $this->storage->addMany(['Task 1', 'Task 2']);
        $this->storage->complete(1); // Завершаем первую задачу
        
        $result = $this->completeTask->execute(['id' => 1]);
        
        $this->assertTrue($result->status);
        $this->assertEquals('Task completed', $result->message);
        
        $payload = $result->payload;
        $task = $payload['task'];
        
        // Задача должна остаться завершенной
        $this->assertTrue($task['done']);
    }

    public function testExecuteWithMultipleTasks(): void
    {
        $this->storage->addMany(['Task 1', 'Task 2', 'Task 3', 'Task 4']);
        
        // Завершаем задачи 2 и 4
        $result1 = $this->completeTask->execute(['id' => 2]);
        $result2 = $this->completeTask->execute(['id' => 4]);
        
        $this->assertTrue($result1->status);
        $this->assertTrue($result2->status);
        
        // Проверяем финальную статистику
        $stats = $result2->payload['stats'];
        $this->assertEquals(4, $stats['total']);
        $this->assertEquals(2, $stats['completed']);
        $this->assertEquals(2, $stats['remaining']);
    }

    public function testGetProps(): void
    {
        $props = $this->completeTask->getProps();
        
        $this->assertIsArray($props);
        $this->assertArrayHasKey('type', $props);
        $this->assertArrayHasKey('function', $props);
        
        $this->assertEquals('function', $props['type']);
        
        $function = $props['function'];
        $this->assertArrayHasKey('name', $function);
        $this->assertArrayHasKey('description', $function);
        $this->assertArrayHasKey('parameters', $function);
        
        $this->assertEquals('tasks-complete', $function['name']);
        $this->assertStringContainsString('Mark an internal task as completed', $function['description']);
        $this->assertStringContainsString('private memory', $function['description']);
        
        // Проверяем параметры
        $parameters = $function['parameters'];
        $this->assertArrayHasKey('type', $parameters);
        $this->assertArrayHasKey('properties', $parameters);
        $this->assertArrayHasKey('required', $parameters);
        $this->assertArrayHasKey('additionalProperties', $parameters);
        
        $this->assertEquals('object', $parameters['type']);
        $this->assertFalse($parameters['additionalProperties']);
        $this->assertEquals(['id'], $parameters['required']);
        
        $properties = $parameters['properties'];
        $this->assertArrayHasKey('id', $properties);
        
        $idProperty = $properties['id'];
        $this->assertEquals('integer', $idProperty['type']);
        $this->assertEquals(1, $idProperty['minimum']);
    }

    public function testExecuteWithExtraArguments(): void
    {
        $this->storage->addMany(['Task 1']);
        
        // Дополнительные аргументы должны игнорироваться
        $result = $this->completeTask->execute([
            'id' => 1,
            'extra' => 'ignored',
            'another' => 'also ignored'
        ]);
        
        $this->assertTrue($result->status);
        $this->assertEquals('Task completed', $result->message);
    }

    public function testExecuteWithEmptyStorage(): void
    {
        $result = $this->completeTask->execute(['id' => 1]);
        
        $this->assertFalse($result->status);
        $this->assertEquals('Task not found', $result->message);
        $this->assertEquals(['code' => 'TASK_NOT_FOUND'], $result->payload);
    }

    public function testExecuteWithLargeId(): void
    {
        $this->storage->addMany(['Task 1']);
        
        $result = $this->completeTask->execute(['id' => PHP_INT_MAX]);
        
        $this->assertFalse($result->status);
        $this->assertEquals('Task not found', $result->message);
        $this->assertEquals(['code' => 'TASK_NOT_FOUND'], $result->payload);
    }
}
