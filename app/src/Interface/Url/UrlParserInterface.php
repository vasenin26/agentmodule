<?php

namespace Anymodule\Agentmodule\Interface\Url;

interface UrlParserInterface
{
    /**
     * Извлекает URL репозитория из ссылки на файл
     */
    public function extractRepoUrl(string $url): string;

    /**
     * Извлекает путь к файлу из ссылки
     */
    public function extractFilePath(string $url): string;

    /**
     * Извлекает владельца и название репозитория из URL
     */
    public function extractOwnerAndRepo(string $url): ?array;

    /**
     * Извлекает ветку из URL ссылки на файл
     */
    public function extractBranch(string $url): ?string;

    /**
     * Преобразует HTTPS ссылку в SSH формат
     */
    public function convertHttpsToSsh(string $url): string;
}
