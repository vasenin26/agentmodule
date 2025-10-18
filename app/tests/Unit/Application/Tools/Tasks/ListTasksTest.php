<?php

namespace Anymodule\Agentmodule\Tests\Unit\Application\Tools\Tasks;

use Anymodule\Agentmodule\Application\Tools\Tasks\ListTasks;
use Anymodule\Agentmodule\Application\Tools\Tasks\TasksStorage;
use Anymodule\Agentmodule\Entity\ToolResult;
use PHPUnit\Framework\TestCase;

class ListTasksTest extends TestCase
{
    private TasksStorage $storage;
    private ListTasks $listTasks;

    protected function setUp(): void
    {
        $this->storage = new TasksStorage(sys_get_temp_dir() . '/test_list_tasks_' . uniqid() . '.json');
        $this->listTasks = new ListTasks($this->storage);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->storage->getStoragePath())) {
            unlink($this->storage->getStoragePath());
        }
    }

    public function testGetName(): void
    {
        $this->assertEquals('get-task-list', $this->listTasks->getName());
    }

    public function testExecuteWithEmptyTasks(): void
    {
        $result = $this->listTasks->execute([]);
        
        $this->assertInstanceOf(ToolResult::class, $result);
        $this->assertTrue($result->status);
        $this->assertEquals('Tasks listed', $result->message);
        
        $payload = $result->payload;
        $this->assertIsArray($payload);
        $this->assertArrayHasKey('tasks', $payload);
        $this->assertArrayHasKey('stats', $payload);
        
        $this->assertEmpty($payload['tasks']);
        $this->assertEquals(0, $payload['stats']['total']);
        $this->assertEquals(0, $payload['stats']['completed']);
        $this->assertEquals(0, $payload['stats']['remaining']);
    }

    public function testExecuteWithTasks(): void
    {
        // Добавляем задачи
        $this->storage->addMany(['Task 1', 'Task 2', 'Task 3']);
        $this->storage->complete(1); // Завершаем первую задачу
        
        $result = $this->listTasks->execute([]);
        
        $this->assertInstanceOf(ToolResult::class, $result);
        $this->assertTrue($result->status);
        $this->assertEquals('Tasks listed', $result->message);
        
        $payload = $result->payload;
        $this->assertIsArray($payload);
        $this->assertArrayHasKey('tasks', $payload);
        $this->assertArrayHasKey('stats', $payload);
        
        // Проверяем задачи
        $tasks = $payload['tasks'];
        $this->assertCount(3, $tasks);
        
        // Проверяем первую задачу (завершенная)
        $this->assertEquals(1, $tasks[0]['id']);
        $this->assertEquals('Task 1', $tasks[0]['title']);
        $this->assertTrue($tasks[0]['done']);
        
        // Проверяем вторую задачу (не завершенная)
        $this->assertEquals(2, $tasks[1]['id']);
        $this->assertEquals('Task 2', $tasks[1]['title']);
        $this->assertFalse($tasks[1]['done']);
        
        // Проверяем третью задачу (не завершенная)
        $this->assertEquals(3, $tasks[2]['id']);
        $this->assertEquals('Task 3', $tasks[2]['title']);
        $this->assertFalse($tasks[2]['done']);
        
        // Проверяем статистику
        $stats = $payload['stats'];
        $this->assertEquals(3, $stats['total']);
        $this->assertEquals(1, $stats['completed']);
        $this->assertEquals(2, $stats['remaining']);
    }

    public function testGetProps(): void
    {
        $props = $this->listTasks->getProps();
        
        $this->assertIsArray($props);
        $this->assertArrayHasKey('type', $props);
        $this->assertArrayHasKey('function', $props);
        
        $this->assertEquals('function', $props['type']);
        
        $function = $props['function'];
        $this->assertArrayHasKey('name', $function);
        $this->assertArrayHasKey('description', $function);
        
        $this->assertEquals('get-task-list', $function['name']);
        $this->assertStringContainsString('Return the full list of internal tasks', $function['description']);
        $this->assertStringContainsString('private memory', $function['description']);
    }

    public function testExecuteWithMixedTaskStates(): void
    {
        // Добавляем 5 задач
        $this->storage->addMany(['Task 1', 'Task 2', 'Task 3', 'Task 4', 'Task 5']);
        
        // Завершаем задачи 1, 3, 5
        $this->storage->complete(1);
        $this->storage->complete(3);
        $this->storage->complete(5);
        
        $result = $this->listTasks->execute([]);
        
        $this->assertTrue($result->status);
        
        $payload = $result->payload;
        $tasks = $payload['tasks'];
        $stats = $payload['stats'];
        
        // Проверяем количество задач
        $this->assertCount(5, $tasks);
        
        // Проверяем статистику
        $this->assertEquals(5, $stats['total']);
        $this->assertEquals(3, $stats['completed']);
        $this->assertEquals(2, $stats['remaining']);
        
        // Проверяем конкретные задачи
        $completedTasks = array_filter($tasks, fn($task) => $task['done']);
        $pendingTasks = array_filter($tasks, fn($task) => !$task['done']);
        
        $this->assertCount(3, $completedTasks);
        $this->assertCount(2, $pendingTasks);
    }

    public function testExecuteIgnoresArguments(): void
    {
        // Тест проверяет, что метод execute игнорирует переданные аргументы
        $this->storage->addMany(['Test Task']);
        
        $result1 = $this->listTasks->execute([]);
        $result2 = $this->listTasks->execute(['some' => 'argument']);
        $result3 = $this->listTasks->execute(['id' => 123, 'title' => 'ignored']);
        
        // Все результаты должны быть одинаковыми
        $this->assertEquals($result1->payload, $result2->payload);
        $this->assertEquals($result1->payload, $result3->payload);
    }
}
