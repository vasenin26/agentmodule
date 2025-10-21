<?php

namespace Anymodule\Agentmodule\Tests\Unit\Application\Tools\Tasks;

use Anymodule\Agentmodule\Services\TaskStorage\TasksStorage;
use PHPUnit\Framework\TestCase;

class TasksStorageTest extends TestCase
{
    private string $tempStoragePath;
    private TasksStorage $storage;

    protected function setUp(): void
    {
        // Создаем временный файл для тестов
        $this->tempStoragePath = sys_get_temp_dir() . '/test_tasks_storage_' . uniqid() . '.json';
        $this->storage = new TasksStorage($this->tempStoragePath);
    }

    protected function tearDown(): void
    {
        // Очищаем временный файл после тестов
        if (file_exists($this->tempStoragePath)) {
            unlink($this->tempStoragePath);
        }
    }

    public function testInitialState(): void
    {
        $tasks = $this->storage->list();
        $stats = $this->storage->getStats();
        
        $this->assertIsArray($tasks);
        $this->assertEmpty($tasks);
        $this->assertEquals(0, $stats['total']);
        $this->assertEquals(0, $stats['completed']);
        $this->assertEquals(0, $stats['remaining']);
    }

    public function testAddSingleTask(): void
    {
        $created = $this->storage->addMany(['Test task']);
        
        $this->assertCount(1, $created);
        $this->assertEquals(1, $created[0]['id']);
        $this->assertEquals('Test task', $created[0]['title']);
        $this->assertFalse($created[0]['done']);
    }

    public function testAddMultipleTasks(): void
    {
        $titles = ['Task 1', 'Task 2', 'Task 3'];
        $created = $this->storage->addMany($titles);
        
        $this->assertCount(3, $created);
        
        // Проверяем ID
        $this->assertEquals(1, $created[0]['id']);
        $this->assertEquals(2, $created[1]['id']);
        $this->assertEquals(3, $created[2]['id']);
        
        // Проверяем названия
        $this->assertEquals('Task 1', $created[0]['title']);
        $this->assertEquals('Task 2', $created[1]['title']);
        $this->assertEquals('Task 3', $created[2]['title']);
        
        // Проверяем статус
        foreach ($created as $task) {
            $this->assertFalse($task['done']);
        }
    }

    public function testListTasks(): void
    {
        $this->storage->addMany(['Task 1', 'Task 2']);
        $tasks = $this->storage->list();
        
        $this->assertCount(2, $tasks);
        $this->assertEquals('Task 1', $tasks[0]['title']);
        $this->assertEquals('Task 2', $tasks[1]['title']);
    }

    public function testCompleteTask(): void
    {
        $this->storage->addMany(['Task 1', 'Task 2']);
        
        $completed = $this->storage->complete(1);
        
        $this->assertNotNull($completed);
        $this->assertEquals(1, $completed['id']);
        $this->assertEquals('Task 1', $completed['title']);
        $this->assertTrue($completed['done']);
    }

    public function testCompleteNonExistentTask(): void
    {
        $result = $this->storage->complete(999);
        $this->assertNull($result);
    }

    public function testGetStats(): void
    {
        // Добавляем задачи
        $this->storage->addMany(['Task 1', 'Task 2', 'Task 3']);
        
        $stats = $this->storage->getStats();
        $this->assertEquals(3, $stats['total']);
        $this->assertEquals(0, $stats['completed']);
        $this->assertEquals(3, $stats['remaining']);
        
        // Завершаем одну задачу
        $this->storage->complete(1);
        
        $stats = $this->storage->getStats();
        $this->assertEquals(3, $stats['total']);
        $this->assertEquals(1, $stats['completed']);
        $this->assertEquals(2, $stats['remaining']);
    }

    public function testClearTasks(): void
    {
        $this->storage->addMany(['Task 1', 'Task 2']);
        $this->storage->clear();
        
        $tasks = $this->storage->list();
        $stats = $this->storage->getStats();
        
        $this->assertEmpty($tasks);
        $this->assertEquals(0, $stats['total']);
    }

    public function testPersistence(): void
    {
        // Добавляем задачи
        $this->storage->addMany(['Task 1', 'Task 2']);
        $this->storage->complete(1);
        
        // Создаем новый экземпляр с тем же файлом
        $newStorage = new TasksStorage($this->tempStoragePath);
        
        $tasks = $newStorage->list();
        $stats = $newStorage->getStats();
        
        $this->assertCount(2, $tasks);
        $this->assertEquals(1, $stats['completed']);
        $this->assertEquals(1, $stats['remaining']);
        
        // Проверяем, что первая задача завершена
        $task1 = $tasks[0];
        $this->assertTrue($task1['done']);
    }

    public function testInvalidJsonHandling(): void
    {
        // Создаем файл с невалидным JSON
        file_put_contents($this->tempStoragePath, 'invalid json');
        
        $newStorage = new TasksStorage($this->tempStoragePath);
        $tasks = $newStorage->list();
        
        // Должен инициализироваться с пустым состоянием
        $this->assertEmpty($tasks);
    }

    public function testCorruptedDataHandling(): void
    {
        // Создаем файл с некорректной структурой данных
        file_put_contents($this->tempStoragePath, '{"invalid": "structure"}');
        
        $newStorage = new TasksStorage($this->tempStoragePath);
        $tasks = $newStorage->list();
        
        // Должен инициализироваться с пустым состоянием
        $this->assertEmpty($tasks);
    }

    public function testStoragePathGetter(): void
    {
        $this->assertEquals($this->tempStoragePath, $this->storage->getStoragePath());
    }

    public function testIdIncrement(): void
    {
        // Добавляем задачи и проверяем, что ID инкрементируются
        $this->storage->addMany(['Task 1']);
        $this->storage->addMany(['Task 2']);
        $this->storage->addMany(['Task 3']);
        
        $tasks = $this->storage->list();
        
        $this->assertEquals(1, $tasks[0]['id']);
        $this->assertEquals(2, $tasks[1]['id']);
        $this->assertEquals(3, $tasks[2]['id']);
    }
}
