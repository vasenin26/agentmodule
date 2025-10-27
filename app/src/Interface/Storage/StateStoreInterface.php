<?php

namespace Anymodule\Agentmodule\Interface\Storage;

/**
 * Интерфейс для работы с сервером состояния
 */
interface StateStoreInterface
{
    /**
     * Отправляет данные на сервер состояния
     * 
     * @param string $key Ключ для сохранения
     * @param mixed $value Значение для сохранения
     * @return void
     */
    public function push(string $key, mixed $value): void;

    /**
     * Получает все данные с сервера состояния
     * 
     * @return array Все сохраненные данные
     */
    public function pull(): array;

    /**
     * Останавливает сервер состояния
     * 
     * @return void
     */
    public function stop(): void;
}

