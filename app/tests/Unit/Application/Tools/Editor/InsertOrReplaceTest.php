<?php

namespace Anymodule\Agentmodule\Tests\Unit\Application\Tools\Editor;

use Anymodule\Agentmodule\Application\Tools\Editor\InsertOrReplace;
use Anymodule\Agentmodule\Entity\ToolResult;
use Anymodule\Agentmodule\Interface\Git\GitRepoProviderInterface;
use CzProject\GitPhp\GitRepository;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class InsertOrReplaceTest extends TestCase
{
    private InsertOrReplace $insertOrReplace;
    private MockObject|GitRepoProviderInterface $repoProvider;
    private MockObject|GitRepository $repo;
    private string $tempDir;
    private string $testRepoPath;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/insert_or_replace_test_' . uniqid();
        $this->testRepoPath = $this->tempDir . '/test_repo';
        
        // Создаем тестовую директорию репозитория
        mkdir($this->testRepoPath, 0755, true);
        
        $this->repo = $this->createMock(GitRepository::class);
        $this->repo->method('getRepositoryPath')->willReturn($this->testRepoPath);
        
        $this->repoProvider = $this->createMock(GitRepoProviderInterface::class);
        $this->repoProvider->method('getRepo')->willReturn($this->repo);
        
        $this->insertOrReplace = new InsertOrReplace($this->repoProvider);
    }

    protected function tearDown(): void
    {
        // Очищаем тестовые файлы
        if (is_dir($this->tempDir)) {
            $this->removeDirectory($this->tempDir);
        }
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }

    public function testGetName(): void
    {
        $this->assertEquals('editor-insert-or-replace', $this->insertOrReplace->getName());
    }

    public function testPrependMode(): void
    {
        $testFile = 'test_prepend.txt';
        $originalContent = "Line 1\nLine 2\nLine 3";
        $newContent = "New Line\n";
        $expectedContent = $newContent . $originalContent;
        
        file_put_contents($this->testRepoPath . '/' . $testFile, $originalContent);
        
        $result = $this->insertOrReplace->execute([
            'url' => 'https://github.com/test/repo.git',
            'path' => $testFile,
            'content' => $newContent,
            'mode' => 'prepend'
        ]);
        
        $this->assertInstanceOf(ToolResult::class, $result);
        $this->assertTrue($result->status);
        $this->assertEquals('File updated successfully', $result->message);
        
        $actualContent = file_get_contents($this->testRepoPath . '/' . $testFile);
        $this->assertEquals($expectedContent, $actualContent);
    }

    public function testAppendMode(): void
    {
        $testFile = 'test_append.txt';
        $originalContent = "Line 1\nLine 2\nLine 3";
        $newContent = "\nNew Line";
        $expectedContent = $originalContent . $newContent;
        
        file_put_contents($this->testRepoPath . '/' . $testFile, $originalContent);
        
        $result = $this->insertOrReplace->execute([
            'url' => 'https://github.com/test/repo.git',
            'path' => $testFile,
            'content' => $newContent,
            'mode' => 'append'
        ]);
        
        $this->assertInstanceOf(ToolResult::class, $result);
        $this->assertTrue($result->status);
        $this->assertEquals('File updated successfully', $result->message);
        
        $actualContent = file_get_contents($this->testRepoPath . '/' . $testFile);
        $this->assertEquals($expectedContent, $actualContent);
    }

    public function testReplaceAllMode(): void
    {
        $testFile = 'test_replace_all.txt';
        $originalContent = "Line 1\nLine 2\nLine 3";
        $newContent = "Completely new content";
        
        file_put_contents($this->testRepoPath . '/' . $testFile, $originalContent);
        
        $result = $this->insertOrReplace->execute([
            'url' => 'https://github.com/test/repo.git',
            'path' => $testFile,
            'content' => $newContent,
            'mode' => 'replace_all'
        ]);
        
        $this->assertInstanceOf(ToolResult::class, $result);
        $this->assertTrue($result->status);
        $this->assertEquals('File updated successfully', $result->message);
        
        $actualContent = file_get_contents($this->testRepoPath . '/' . $testFile);
        $this->assertEquals($newContent, $actualContent);
    }

    public function testReplaceStartMode(): void
    {
        $testFile = 'test_replace_start.txt';
        $originalContent = "Line 1\nLine 2\nLine 3\nLine 4";
        $newContent = "New Line 1\nNew Line 2";
        $expectedContent = "New Line 1\nNew Line 2\nLine 3\nLine 4";
        
        file_put_contents($this->testRepoPath . '/' . $testFile, $originalContent);
        
        $result = $this->insertOrReplace->execute([
            'url' => 'https://github.com/test/repo.git',
            'path' => $testFile,
            'content' => $newContent,
            'mode' => 'replace_start'
        ]);
        
        $this->assertInstanceOf(ToolResult::class, $result);
        $this->assertTrue($result->status);
        
        $actualContent = file_get_contents($this->testRepoPath . '/' . $testFile);
        $this->assertEquals($expectedContent, $actualContent);
    }

    public function testReplaceEndMode(): void
    {
        $testFile = 'test_replace_end.txt';
        $originalContent = "Line 1\nLine 2\nLine 3\nLine 4";
        $newContent = "New Line 3\nNew Line 4";
        $expectedContent = "Line 1\nLine 2\nNew Line 3\nNew Line 4";
        
        file_put_contents($this->testRepoPath . '/' . $testFile, $originalContent);
        
        $result = $this->insertOrReplace->execute([
            'url' => 'https://github.com/test/repo.git',
            'path' => $testFile,
            'content' => $newContent,
            'mode' => 'replace_end'
        ]);
        
        $this->assertInstanceOf(ToolResult::class, $result);
        $this->assertTrue($result->status);
        
        $actualContent = file_get_contents($this->testRepoPath . '/' . $testFile);
        $this->assertEquals($expectedContent, $actualContent);
    }

    public function testInsertAtLineMode(): void
    {
        $testFile = 'test_insert_at_line.txt';
        $originalContent = "Line 1\nLine 2\nLine 3\nLine 4";
        $newContent = "LINE:2\nInserted Line";
        $expectedContent = "Line 1\nInserted Line\nLine 2\nLine 3\nLine 4";
        
        file_put_contents($this->testRepoPath . '/' . $testFile, $originalContent);
        
        $result = $this->insertOrReplace->execute([
            'url' => 'https://github.com/test/repo.git',
            'path' => $testFile,
            'content' => $newContent,
            'mode' => 'insert_at_line'
        ]);
        
        $this->assertInstanceOf(ToolResult::class, $result);
        $this->assertTrue($result->status);
        
        $actualContent = file_get_contents($this->testRepoPath . '/' . $testFile);
        $this->assertEquals($expectedContent, $actualContent);
    }

    public function testCreateFileIfNotExists(): void
    {
        $testFile = 'new_file.txt';
        $newContent = "New file content";
        
        $result = $this->insertOrReplace->execute([
            'url' => 'https://github.com/test/repo.git',
            'path' => $testFile,
            'content' => $newContent,
            'mode' => 'replace_all',
            'create_if_not_exists' => true
        ]);
        
        $this->assertInstanceOf(ToolResult::class, $result);
        $this->assertTrue($result->status);
        $this->assertEquals('File created successfully', $result->message);
        
        $this->assertTrue(file_exists($this->testRepoPath . '/' . $testFile));
        $actualContent = file_get_contents($this->testRepoPath . '/' . $testFile);
        $this->assertEquals($newContent, $actualContent);
    }

    public function testCreateFileWithDirectories(): void
    {
        $testFile = 'subdir/nested/new_file.txt';
        $newContent = "New file content";
        
        $result = $this->insertOrReplace->execute([
            'url' => 'https://github.com/test/repo.git',
            'path' => $testFile,
            'content' => $newContent,
            'mode' => 'replace_all',
            'create_if_not_exists' => true
        ]);
        
        $this->assertInstanceOf(ToolResult::class, $result);
        $this->assertTrue($result->status);
        $this->assertEquals('File created successfully', $result->message);
        
        $fullPath = $this->testRepoPath . '/' . $testFile;
        $this->assertTrue(file_exists($fullPath));
        $actualContent = file_get_contents($fullPath);
        $this->assertEquals($newContent, $actualContent);
    }

    public function testFileNotFoundError(): void
    {
        $result = $this->insertOrReplace->execute([
            'url' => 'https://github.com/test/repo.git',
            'path' => 'nonexistent.txt',
            'content' => 'content',
            'mode' => 'replace_all'
        ]);
        
        $this->assertInstanceOf(ToolResult::class, $result);
        $this->assertFalse($result->status);
        $this->assertEquals('File not found: nonexistent.txt', $result->message);
        $this->assertEquals('FILE_NOT_FOUND', $result->payload['code']);
    }

    public function testRepositoryNotFoundError(): void
    {
        $nonExistentRepo = $this->createMock(GitRepository::class);
        $nonExistentRepo->method('getRepositoryPath')->willReturn('/nonexistent/path');
        
        $this->repoProvider->method('getRepo')->willReturn($nonExistentRepo);
        
        $result = $this->insertOrReplace->execute([
            'url' => 'https://github.com/test/repo.git',
            'path' => 'test.txt',
            'content' => 'content',
            'mode' => 'replace_all'
        ]);
        
        $this->assertInstanceOf(ToolResult::class, $result);
        $this->assertFalse($result->status);
        $this->assertEquals('File not found: test.txt', $result->message);
        $this->assertEquals('FILE_NOT_FOUND', $result->payload['code']);
    }

    public function testNoChangesMade(): void
    {
        $testFile = 'test_no_changes.txt';
        $originalContent = "Line 1\nLine 2";
        
        file_put_contents($this->testRepoPath . '/' . $testFile, $originalContent);
        
        $result = $this->insertOrReplace->execute([
            'url' => 'https://github.com/test/repo.git',
            'path' => $testFile,
            'content' => $originalContent,
            'mode' => 'replace_all'
        ]);
        
        $this->assertInstanceOf(ToolResult::class, $result);
        $this->assertTrue($result->status);
        $this->assertEquals('No changes made to file', $result->message);
        $this->assertFalse($result->payload['changes_made']);
    }

    public function testInsertAtLineWithInvalidFormat(): void
    {
        $testFile = 'test_invalid_line.txt';
        $originalContent = "Line 1\nLine 2";
        
        file_put_contents($this->testRepoPath . '/' . $testFile, $originalContent);
        
        $result = $this->insertOrReplace->execute([
            'url' => 'https://github.com/test/repo.git',
            'path' => $testFile,
            'content' => 'Invalid format without LINE: prefix',
            'mode' => 'insert_at_line'
        ]);
        
        $this->assertInstanceOf(ToolResult::class, $result);
        $this->assertTrue($result->status);
        
        // Файл должен остаться неизменным
        $actualContent = file_get_contents($this->testRepoPath . '/' . $testFile);
        $this->assertEquals($originalContent, $actualContent);
    }

    public function testDefaultModeReturnsOriginalContent(): void
    {
        $testFile = 'test_default_mode.txt';
        $originalContent = "Line 1\nLine 2";
        
        file_put_contents($this->testRepoPath . '/' . $testFile, $originalContent);
        
        $result = $this->insertOrReplace->execute([
            'url' => 'https://github.com/test/repo.git',
            'path' => $testFile,
            'content' => 'New content',
            'mode' => 'unknown_mode'
        ]);
        
        $this->assertInstanceOf(ToolResult::class, $result);
        $this->assertTrue($result->status);
        
        // Файл должен остаться неизменным
        $actualContent = file_get_contents($this->testRepoPath . '/' . $testFile);
        $this->assertEquals($originalContent, $actualContent);
    }

    public function testPayloadContainsCorrectInformation(): void
    {
        $testFile = 'test_payload.txt';
        $originalContent = "Original";
        $newContent = "New content";
        
        file_put_contents($this->testRepoPath . '/' . $testFile, $originalContent);
        
        $result = $this->insertOrReplace->execute([
            'url' => 'https://github.com/test/repo.git',
            'path' => $testFile,
            'content' => $newContent,
            'mode' => 'replace_all'
        ]);
        
        $this->assertTrue($result->status);
        $payload = $result->payload;
        
        $this->assertEquals($testFile, $payload['file_path']);
        $this->assertEquals('replace_all', $payload['mode']);
        $this->assertTrue($payload['changes_made']);
        $this->assertFalse($payload['file_created']);
        $this->assertIsInt($payload['bytes_written']);
        $this->assertEquals(strlen($originalContent), $payload['original_length']);
        $this->assertEquals(strlen($newContent), $payload['new_length']);
    }

    public function testExceptionHandling(): void
    {
        // Мокаем repoProvider чтобы он выбросил исключение
        $this->repoProvider->method('getRepo')->willThrowException(new \Exception('Test exception'));
        
        $result = $this->insertOrReplace->execute([
            'url' => 'https://github.com/test/repo.git',
            'path' => 'test.txt',
            'content' => 'content',
            'mode' => 'replace_all'
        ]);
        
        $this->assertInstanceOf(ToolResult::class, $result);
        $this->assertFalse($result->status);
        $this->assertStringContainsString('Failed to insert or replace in file', $result->message);
        $this->assertEquals('INSERT_REPLACE_ERROR', $result->payload['code']);
        $this->assertEquals('Exception', $result->payload['exception']);
    }
}
