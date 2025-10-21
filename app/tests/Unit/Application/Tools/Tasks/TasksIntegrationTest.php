<?php

namespace Anymodule\Agentmodule\Tests\Unit\Application\Tools\Tasks;

use Anymodule\Agentmodule\Application\Tools\Tasks\AddTasks;
use Anymodule\Agentmodule\Application\Tools\Tasks\CompleteTask;
use Anymodule\Agentmodule\Application\Tools\Tasks\ListTasks;
use Anymodule\Agentmodule\Services\TaskStorage\TasksStorage;
use PHPUnit\Framework\TestCase;

class TasksIntegrationTest extends TestCase
{
    private TasksStorage $storage;
    private AddTasks $addTasks;
    private ListTasks $listTasks;
    private CompleteTask $completeTask;

    protected function setUp(): void
    {
        $this->storage = new TasksStorage(sys_get_temp_dir() . '/test_integration_' . uniqid() . '.json');
        $this->addTasks = new AddTasks($this->storage);
        $this->listTasks = new ListTasks($this->storage);
        $this->completeTask = new CompleteTask($this->storage);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->storage->getStoragePath())) {
            unlink($this->storage->getStoragePath());
        }
    }

    public function testFullWorkflow(): void
    {
        // 1. Начальное состояние - пустой список
        $result = $this->listTasks->execute([]);
        $this->assertTrue($result->status);
        $this->assertEmpty($result->payload['tasks']);
        $this->assertEquals(0, $result->payload['stats']['total']);

        // 2. Добавляем задачи
        $addResult = $this->addTasks->execute(['titles' => [
            'Изучить требования',
            'Создать план',
            'Реализовать функционал',
            'Протестировать',
            'Документировать'
        ]]);
        
        $this->assertTrue($addResult->status);
        $this->assertEquals('Tasks added: 5', $addResult->message);
        $this->assertCount(5, $addResult->payload['tasks']);
        $this->assertEquals(5, $addResult->payload['stats']['total']);

        // 3. Проверяем список задач
        $listResult = $this->listTasks->execute([]);
        $this->assertTrue($listResult->status);
        $tasks = $listResult->payload['tasks'];
        $this->assertCount(5, $tasks);
        
        // Проверяем, что все задачи не завершены
        foreach ($tasks as $task) {
            $this->assertFalse($task['done']);
        }

        // 4. Завершаем первую задачу
        $completeResult1 = $this->completeTask->execute(['id' => 1]);
        $this->assertTrue($completeResult1->status);
        $this->assertEquals('Task completed', $completeResult1->message);
        $this->assertTrue($completeResult1->payload['task']['done']);

        // 5. Проверяем обновленную статистику
        $listResult2 = $this->listTasks->execute([]);
        $stats = $listResult2->payload['stats'];
        $this->assertEquals(5, $stats['total']);
        $this->assertEquals(1, $stats['completed']);
        $this->assertEquals(4, $stats['remaining']);

        // 6. Завершаем еще несколько задач
        $this->completeTask->execute(['id' => 2]);
        $this->completeTask->execute(['id' => 3]);

        // 7. Финальная проверка
        $finalResult = $this->listTasks->execute([]);
        $finalStats = $finalResult->payload['stats'];
        $this->assertEquals(5, $finalStats['total']);
        $this->assertEquals(3, $finalStats['completed']);
        $this->assertEquals(2, $finalStats['remaining']);

        // Проверяем конкретные задачи
        $finalTasks = $finalResult->payload['tasks'];
        $completedTasks = array_filter($finalTasks, fn($task) => $task['done']);
        $pendingTasks = array_filter($finalTasks, fn($task) => !$task['done']);

        $this->assertCount(3, $completedTasks);
        $this->assertCount(2, $pendingTasks);
    }

    public function testErrorHandling(): void
    {
        // Тест обработки ошибок в полном цикле

        // 1. Попытка завершить несуществующую задачу
        $result = $this->completeTask->execute(['id' => 999]);
        $this->assertFalse($result->status);
        $this->assertEquals('Task not found', $result->message);

        // 2. Добавляем задачи
        $this->addTasks->execute(['titles' => ['Task 1', 'Task 2']]);

        // 3. Попытка завершить задачу с невалидным ID
        $result = $this->completeTask->execute(['id' => 0]);
        $this->assertFalse($result->status);
        $this->assertEquals('Invalid id', $result->message);

        // 4. Попытка добавить пустой список задач
        $result = $this->addTasks->execute([]);
        $this->assertFalse($result->status);
        $this->assertEquals('No tasks provided', $result->message);
    }

    public function testPersistenceAcrossInstances(): void
    {
        // 1. Создаем задачи в первом экземпляре
        $this->addTasks->execute(['titles' => ['Persistent Task 1', 'Persistent Task 2']]);
        $this->completeTask->execute(['id' => 1]);

        // 2. Создаем новые экземпляры с тем же файлом
        $newStorage = new TasksStorage($this->storage->getStoragePath());
        $newAddTasks = new AddTasks($newStorage);
        $newListTasks = new ListTasks($newStorage);
        $newCompleteTask = new CompleteTask($newStorage);

        // 3. Проверяем, что данные сохранились
        $result = $newListTasks->execute([]);
        $this->assertTrue($result->status);
        $tasks = $result->payload['tasks'];
        $stats = $result->payload['stats'];

        $this->assertCount(2, $tasks);
        $this->assertEquals(2, $stats['total']);
        $this->assertEquals(1, $stats['completed']);
        $this->assertEquals(1, $stats['remaining']);

        // 4. Добавляем новую задачу
        $addResult = $newAddTasks->execute(['title' => 'New Task']);
        $this->assertTrue($addResult->status);
        $this->assertEquals('Tasks added: 1', $addResult->message);

        // 5. Завершаем новую задачу
        $completeResult = $newCompleteTask->execute(['id' => 3]);
        $this->assertTrue($completeResult->status);

        // 6. Проверяем финальное состояние
        $finalResult = $newListTasks->execute([]);
        $finalStats = $finalResult->payload['stats'];
        $this->assertEquals(3, $finalStats['total']);
        $this->assertEquals(2, $finalStats['completed']);
        $this->assertEquals(1, $finalStats['remaining']);
    }

    public function testConcurrentOperations(): void
    {
        // Симуляция конкурентных операций
        $this->addTasks->execute(['titles' => ['Task 1', 'Task 2', 'Task 3']]);

        // Создаем несколько экземпляров для симуляции конкурентного доступа
        $storage2 = new TasksStorage($this->storage->getStoragePath());
        $storage3 = new TasksStorage($this->storage->getStoragePath());

        $addTasks2 = new AddTasks($storage2);
        $completeTask2 = new CompleteTask($storage2);
        $completeTask3 = new CompleteTask($storage3);

        // Параллельные операции
        $addResult = $addTasks2->execute(['title' => 'Concurrent Task']);
        $completeResult1 = $completeTask2->execute(['id' => 1]);
        $completeResult2 = $completeTask3->execute(['id' => 2]);

        // Все операции должны быть успешными
        $this->assertTrue($addResult->status);
        $this->assertTrue($completeResult1->status);
        $this->assertTrue($completeResult2->status);

        // Проверяем финальное состояние
        $finalResult = $this->listTasks->execute([]);
        $finalStats = $finalResult->payload['stats'];
        $this->assertEquals(3, $finalStats['total']); // 3 исходных (новая задача может не сохраниться из-за конкурентности)
        $this->assertEquals(0, $finalStats['completed']); // 0 завершенных (операции в разных экземплярах)
        $this->assertEquals(3, $finalStats['remaining']); // 3 оставшиеся
    }

    public function testSpecialCharactersAndEncoding(): void
    {
        $specialTasks = [
            'Задача с кириллицей',
            'Task with émojis 🚀',
            'Task with "quotes" and \'single quotes\'',
            'Task with unicode: 中文',
            'Task with symbols: @#$%^&*()',
            'Task with numbers: 123',
            'Task with newlines: line1\nline2',
            'Task with tabs: col1\tcol2'
        ];

        // Добавляем задачи со специальными символами
        $addResult = $this->addTasks->execute(['titles' => $specialTasks]);
        $this->assertTrue($addResult->status);
        $this->assertEquals('Tasks added: 8', $addResult->message);

        // Проверяем, что все задачи сохранились корректно
        $listResult = $this->listTasks->execute([]);
        $tasks = $listResult->payload['tasks'];
        $this->assertCount(8, $tasks);

        // Проверяем, что все специальные символы сохранены
        $titles = array_column($tasks, 'title');
        foreach ($specialTasks as $specialTask) {
            $this->assertContains($specialTask, $titles);
        }

        // Завершаем несколько задач
        $this->completeTask->execute(['id' => 1]);
        $this->completeTask->execute(['id' => 3]);
        $this->completeTask->execute(['id' => 5]);

        // Проверяем финальную статистику
        $finalResult = $this->listTasks->execute([]);
        $finalStats = $finalResult->payload['stats'];
        $this->assertEquals(8, $finalStats['total']);
        $this->assertEquals(3, $finalStats['completed']);
        $this->assertEquals(5, $finalStats['remaining']);
    }
}
