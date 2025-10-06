<?php

namespace Tests\Unit\Services;

use Anymodule\Agentmodule\Interface\StateStoreInterface;
use Anymodule\Agentmodule\Services\StateStore;
use PHPUnit\Framework\TestCase;

/**
 * Тесты для StateStore
 * 
 * Примечание: Эти тесты требуют запущенного сервера состояния
 * Для unit-тестов без реального сервера следует использовать mock объекты
 */
class StateStoreTest extends TestCase
{
    private StateStoreInterface $store;

    protected function setUp(): void
    {
        parent::setUp();
        $this->store = new StateStore('localhost', 8080);
    }

    public function testImplementsInterface(): void
    {
        $this->assertInstanceOf(StateStoreInterface::class, $this->store);
    }

    /**
     * @group integration
     */
    public function testPushAndPull(): void
    {
        // Отправляем данные
        $this->store->push('test_key', 'test_value');
        
        // Получаем данные
        $data = $this->store->pull();
        
        // Проверяем, что наш ключ присутствует
        $this->assertArrayHasKey('test_key', $data);
        $this->assertEquals('test_value', $data['test_key']);
    }

    /**
     * @group integration
     */
    public function testPushDifferentTypes(): void
    {
        // Тестируем разные типы данных
        $this->store->push('string_value', 'hello');
        $this->store->push('int_value', 42);
        $this->store->push('float_value', 3.14);
        $this->store->push('bool_value', true);
        $this->store->push('array_value', ['a' => 1, 'b' => 2]);
        
        $data = $this->store->pull();
        
        $this->assertEquals('hello', $data['string_value']);
        $this->assertEquals(42, $data['int_value']);
        $this->assertEquals(3.14, $data['float_value']);
        $this->assertTrue($data['bool_value']);
        $this->assertIsArray($data['array_value']);
        $this->assertEquals(['a' => 1, 'b' => 2], $data['array_value']);
    }

    /**
     * @group integration
     */
    public function testPushUpdatesExistingValue(): void
    {
        // Отправляем первое значение
        $this->store->push('counter', 1);
        
        // Обновляем значение
        $this->store->push('counter', 2);
        
        $data = $this->store->pull();
        
        // Проверяем, что значение обновилось
        $this->assertEquals(2, $data['counter']);
    }

    /**
     * @group integration
     */
    public function testPullReturnsArray(): void
    {
        $data = $this->store->pull();
        
        $this->assertIsArray($data);
    }

    public function testPushThrowsExceptionOnServerError(): void
    {
        // Создаем клиент с неправильным портом
        $store = new StateStore('localhost', 9999);
        
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to push state to server');
        
        $store->push('test', 'value');
    }

    public function testPullThrowsExceptionOnServerError(): void
    {
        // Создаем клиент с неправильным портом
        $store = new StateStore('localhost', 9999);
        
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to pull state from server');
        
        $store->pull();
    }

    /**
     * @group integration
     */
    public function testStop(): void
    {
        // Этот тест требует запущенного сервера
        // После вызова stop() сервер должен остановиться
        $this->store->stop();
        
        // Проверяем что сервер больше не отвечает (ожидаем исключение)
        $this->expectException(\RuntimeException::class);
        $this->store->pull();
    }

    public function testStopThrowsExceptionOnServerError(): void
    {
        // Создаем клиент с неправильным портом
        $store = new StateStore('localhost', 9999);
        
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to stop server');
        
        $store->stop();
    }
}

