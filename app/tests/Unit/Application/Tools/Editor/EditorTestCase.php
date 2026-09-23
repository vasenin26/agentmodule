<?php

namespace Anymodule\Agentmodule\Tests\Unit\Application\Tools\Editor;

use Anymodule\Agentmodule\Interface\Git\GitRepoProviderInterface;
use CzProject\GitPhp\GitRepository;
use PHPUnit\Framework\TestCase;

abstract class EditorTestCase extends TestCase
{
    protected const URL = 'git@github.com:vasenin26/docmodule.git';

    protected string $tempDir;
    protected string $repoPath;
    protected GitRepoProviderInterface $repoProvider;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/editor_test_' . uniqid();
        $this->repoPath = $this->tempDir . '/repo';
        mkdir($this->repoPath, 0755, true);

        $repo = $this->createStub(GitRepository::class);
        $repo->method('getRepositoryPath')->willReturn($this->repoPath);

        $this->repoProvider = $this->createStub(GitRepoProviderInterface::class);
        $this->repoProvider->method('getRepo')->willReturn($repo);
    }

    protected function tearDown(): void
    {
        exec('rm -rf ' . escapeshellarg($this->tempDir));
    }

    protected function putFile(string $path, string $content): void
    {
        $full = $this->repoPath . '/' . $path;
        @mkdir(dirname($full), 0755, true);
        file_put_contents($full, $content);
    }

    protected function readFile(string $path): string
    {
        return file_get_contents($this->repoPath . '/' . $path);
    }

    protected function assertNoTempFiles(): void
    {
        $leftovers = glob($this->repoPath . '/{,*/}.editor-*', GLOB_BRACE);
        $this->assertSame([], $leftovers);
    }
}
