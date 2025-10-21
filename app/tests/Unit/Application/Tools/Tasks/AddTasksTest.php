<?php

namespace Anymodule\Agentmodule\Tests\Unit\Application\Tools\Tasks;

use Anymodule\Agentmodule\Application\Tools\Tasks\AddTasks;
use Anymodule\Agentmodule\Entity\ToolResult;
use Anymodule\Agentmodule\Services\TaskStorage\TasksStorage;
use PHPUnit\Framework\TestCase;

class AddTasksTest extends TestCase
{
    private TasksStorage $storage;
    private AddTasks $addTasks;

    protected function setUp(): void
    {
        $this->storage = new TasksStorage(sys_get_temp_dir() . '/test_add_tasks_' . uniqid() . '.json');
        $this->addTasks = new AddTasks($this->storage);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->storage->getStoragePath())) {
            unlink($this->storage->getStoragePath());
        }
    }

    public function testGetName(): void
    {
        $this->assertEquals('tasks-add', $this->addTasks->getName());
    }

    public function testExecuteWithSingleTitle(): void
    {
        $result = $this->addTasks->execute(['title' => 'Test Task']);
        
        $this->assertInstanceOf(ToolResult::class, $result);
        $this->assertTrue($result->status);
        $this->assertEquals('Tasks added: 1', $result->message);
        
        $payload = $result->payload;
        $this->assertIsArray($payload);
        $this->assertArrayHasKey('tasks', $payload);
        $this->assertArrayHasKey('stats', $payload);
        
        // Проверяем созданную задачу
        $tasks = $payload['tasks'];
        $this->assertCount(1, $tasks);
        $this->assertEquals(1, $tasks[0]['id']);
        $this->assertEquals('Test Task', $tasks[0]['title']);
        $this->assertFalse($tasks[0]['done']);
        
        // Проверяем статистику
        $stats = $payload['stats'];
        $this->assertEquals(1, $stats['total']);
        $this->assertEquals(0, $stats['completed']);
        $this->assertEquals(1, $stats['remaining']);
    }

    public function testExecuteWithMultipleTitles(): void
    {
        $result = $this->addTasks->execute(['titles' => ['Task 1', 'Task 2', 'Task 3']]);
        
        $this->assertInstanceOf(ToolResult::class, $result);
        $this->assertTrue($result->status);
        $this->assertEquals('Tasks added: 3', $result->message);
        
        $payload = $result->payload;
        $tasks = $payload['tasks'];
        $stats = $payload['stats'];
        
        // Проверяем созданные задачи
        $this->assertCount(3, $tasks);
        $this->assertEquals(1, $tasks[0]['id']);
        $this->assertEquals('Task 1', $tasks[0]['title']);
        $this->assertEquals(2, $tasks[1]['id']);
        $this->assertEquals('Task 2', $tasks[1]['title']);
        $this->assertEquals(3, $tasks[2]['id']);
        $this->assertEquals('Task 3', $tasks[2]['title']);
        
        // Проверяем статистику
        $this->assertEquals(3, $stats['total']);
        $this->assertEquals(0, $stats['completed']);
        $this->assertEquals(3, $stats['remaining']);
    }

    public function testExecuteWithBothTitleAndTitles(): void
    {
        $result = $this->addTasks->execute([
            'title' => 'Single Task',
            'titles' => ['Bulk Task 1', 'Bulk Task 2']
        ]);
        
        $this->assertTrue($result->status);
        $this->assertEquals('Tasks added: 3', $result->message);
        
        $payload = $result->payload;
        $tasks = $payload['tasks'];
        
        $this->assertCount(3, $tasks);
        
        // Проверяем, что все задачи добавлены
        $titles = array_column($tasks, 'title');
        $this->assertContains('Single Task', $titles);
        $this->assertContains('Bulk Task 1', $titles);
        $this->assertContains('Bulk Task 2', $titles);
    }

    public function testExecuteWithNoTasks(): void
    {
        $result = $this->addTasks->execute([]);
        
        $this->assertInstanceOf(ToolResult::class, $result);
        $this->assertFalse($result->status);
        $this->assertEquals('No tasks provided', $result->message);
        $this->assertEquals(['code' => 'NO_TASKS'], $result->payload);
    }

    public function testExecuteWithEmptyTitles(): void
    {
        $result = $this->addTasks->execute(['titles' => []]);
        
        $this->assertFalse($result->status);
        $this->assertEquals('No tasks provided', $result->message);
    }

    public function testExecuteWithNonStringTitles(): void
    {
        $result = $this->addTasks->execute([
            'titles' => ['Valid Task', 123, null, 'Another Valid Task']
        ]);
        
        $this->assertTrue($result->status);
        $this->assertEquals('Tasks added: 2', $result->message);
        
        $payload = $result->payload;
        $tasks = $payload['tasks'];
        
        // Должны быть добавлены только строковые задачи
        $this->assertCount(2, $tasks);
        $this->assertEquals('Valid Task', $tasks[0]['title']);
        $this->assertEquals('Another Valid Task', $tasks[1]['title']);
    }

    public function testExecuteWithEmptyStringTitles(): void
    {
        $result = $this->addTasks->execute([
            'titles' => ['Valid Task', '', 'Another Valid Task']
        ]);
        
        $this->assertTrue($result->status);
        $this->assertEquals('Tasks added: 3', $result->message);
        
        $payload = $result->payload;
        $tasks = $payload['tasks'];
        
        // Пустые строки тоже добавляются (текущее поведение)
        $this->assertCount(3, $tasks);
        $this->assertEquals('Valid Task', $tasks[0]['title']);
        $this->assertEquals('', $tasks[1]['title']);
        $this->assertEquals('Another Valid Task', $tasks[2]['title']);
    }

    public function testGetProps(): void
    {
        $props = $this->addTasks->getProps();
        
        $this->assertIsArray($props);
        $this->assertArrayHasKey('type', $props);
        $this->assertArrayHasKey('function', $props);
        
        $this->assertEquals('function', $props['type']);
        
        $function = $props['function'];
        $this->assertArrayHasKey('name', $function);
        $this->assertArrayHasKey('description', $function);
        $this->assertArrayHasKey('parameters', $function);
        
        $this->assertEquals('tasks-add', $function['name']);
        $this->assertStringContainsString('Add one or multiple tasks', $function['description']);
        $this->assertStringContainsString('private memory', $function['description']);
        
        // Проверяем параметры
        $parameters = $function['parameters'];
        $this->assertArrayHasKey('type', $parameters);
        $this->assertArrayHasKey('properties', $parameters);
        $this->assertArrayHasKey('additionalProperties', $parameters);
        
        $this->assertEquals('object', $parameters['type']);
        $this->assertFalse($parameters['additionalProperties']);
        
        $properties = $parameters['properties'];
        $this->assertArrayHasKey('title', $properties);
        $this->assertArrayHasKey('titles', $properties);
    }

    public function testExecuteWithExistingTasks(): void
    {
        // Добавляем начальные задачи
        $this->storage->addMany(['Existing Task 1', 'Existing Task 2']);
        
        // Добавляем новые задачи
        $result = $this->addTasks->execute(['title' => 'New Task']);
        
        $this->assertTrue($result->status);
        
        $payload = $result->payload;
        $tasks = $payload['tasks'];
        $stats = $payload['stats'];
        
        // Проверяем, что добавлена только новая задача
        $this->assertCount(1, $tasks);
        $this->assertEquals('New Task', $tasks[0]['title']);
        $this->assertEquals(3, $tasks[0]['id']); // ID должен быть 3 (следующий после существующих)
        
        // Проверяем общую статистику
        $this->assertEquals(3, $stats['total']);
        $this->assertEquals(0, $stats['completed']);
        $this->assertEquals(3, $stats['remaining']);
    }

    public function testExecuteWithSpecialCharacters(): void
    {
        $specialTitles = [
            'Task with émojis 🚀',
            'Task with "quotes"',
            'Task with \'single quotes\'',
            'Task with unicode: 中文',
            'Task with numbers: 123',
            'Task with symbols: @#$%^&*()'
        ];
        
        $result = $this->addTasks->execute(['titles' => $specialTitles]);
        
        $this->assertTrue($result->status);
        $this->assertEquals('Tasks added: 6', $result->message);
        
        $payload = $result->payload;
        $tasks = $payload['tasks'];
        
        $this->assertCount(6, $tasks);
        
        // Проверяем, что все специальные символы сохранены корректно
        foreach ($tasks as $task) {
            $this->assertContains($task['title'], $specialTitles);
        }
    }
}
