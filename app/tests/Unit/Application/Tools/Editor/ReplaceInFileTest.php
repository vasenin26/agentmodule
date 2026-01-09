<?php

namespace Anymodule\Agentmodule\Tests\Unit\Application\Tools\Editor;

use Anymodule\Agentmodule\Application\Tools\Editor\ReplaceInFile;
use Anymodule\Agentmodule\Entity\ToolResult;
use Anymodule\Agentmodule\Interface\Git\GitRepoProviderInterface;
use CzProject\GitPhp\GitRepository;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class ReplaceInFileTest extends TestCase
{
    private ReplaceInFile $replaceInFile;
    private MockObject|GitRepoProviderInterface $repoProvider;
    private MockObject|GitRepository $repo;
    private string $tempDir;
    private string $testRepoPath;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/replace_in_file_test_' . uniqid();
        $this->testRepoPath = $this->tempDir . '/test_repo';
        
        // Создаем тестовую директорию репозитория
        mkdir($this->testRepoPath, 0755, true);
        
        $this->repo = $this->createMock(GitRepository::class);
        $this->repo->method('getRepositoryPath')->willReturn($this->testRepoPath);
        
        $this->repoProvider = $this->createMock(GitRepoProviderInterface::class);
        $this->repoProvider->method('getRepo')->willReturn($this->repo);
        
        $this->replaceInFile = new ReplaceInFile($this->repoProvider);
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
        $this->assertEquals('editor-replace-in-file', $this->replaceInFile->getName());
    }

    public function testReplaceWithSimplePattern(): void
    {
        $testFile = 'test_simple_replace.txt';
        $originalContent = "я хочу какать\nЭто тест\nя хочу спать";
        $expectedContent = "я не хочу какать\nЭто тест\nя не хочу спать";
        
        file_put_contents($this->testRepoPath . '/' . $testFile, $originalContent);
        
        $result = $this->replaceInFile->execute([
            'url' => 'git@github.com:vasenin26/docmodule.git',
            'path' => $testFile,
            'pattern' => 'хочу',
            'replacement' => 'не хочу'
        ]);
        
        $this->assertInstanceOf(ToolResult::class, $result);
        $this->assertTrue($result->status);
        $this->assertStringContainsString('File updated successfully', $result->message);
        
        $actualContent = file_get_contents($this->testRepoPath . '/' . $testFile);
        $this->assertEquals($expectedContent, $actualContent);
        
        $payload = $result->payload;
        $this->assertEquals(2, $payload['replacements_made']);
    }

    public function testReplaceWithComplexPatternLikeExample(): void
    {
        $testFile = 'app/Services/PageContext/PageContextService.php';
        $originalContent = "    return Page::with(['creator', 'project']);";
        $expectedContent = "    Page::with(['creator', 'project'])\n            ->withCount(['children']);";
        
        // Создаем директорию если нужно
        $dir = dirname($this->testRepoPath . '/' . $testFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        file_put_contents($this->testRepoPath . '/' . $testFile, $originalContent);
        
        $result = $this->replaceInFile->execute([
            'url' => 'git@github.com:vasenin26/docmodule.git',
            'path' => $testFile,
            'pattern' => "return Page::with(['creator', 'project'])",
            'replacement' => "Page::with(['creator', 'project'])\n            ->withCount(['children'])"
        ]);
        
        $this->assertInstanceOf(ToolResult::class, $result);
        $this->assertTrue($result->status);
        
        $actualContent = file_get_contents($this->testRepoPath . '/' . $testFile);
        $this->assertEquals($expectedContent, $actualContent);
        
        $payload = $result->payload;
        $this->assertEquals(1, $payload['replacements_made']);
    }

    public function testReplaceWithExactString(): void
    {
        $testFile = 'test_exact_replace.txt';
        $originalContent = "Line 1: Hello World\nLine 2: Hello Universe\nLine 3: Hi World";
        $expectedContent = "Line 1: Hi World\nLine 2: Hi Universe\nLine 3: Hi World";
        
        file_put_contents($this->testRepoPath . '/' . $testFile, $originalContent);
        
        $result = $this->replaceInFile->execute([
            'url' => 'git@github.com:vasenin26/docmodule.git',
            'path' => $testFile,
            'pattern' => 'Hello',
            'replacement' => 'Hi'
        ]);
        
        $this->assertInstanceOf(ToolResult::class, $result);
        $this->assertTrue($result->status);
        
        $actualContent = file_get_contents($this->testRepoPath . '/' . $testFile);
        $this->assertEquals($expectedContent, $actualContent);
        
        $payload = $result->payload;
        $this->assertEquals(2, $payload['replacements_made']);
    }

    public function testReplaceWithMethodChaining(): void
    {
        $testFile = 'test_method_chaining.txt';
        $originalContent = "    return Page::with(['creator', 'project']);";
        $expectedContent = "    Page::with(['creator', 'project'])\n    ->withCount(['children']);";
        
        file_put_contents($this->testRepoPath . '/' . $testFile, $originalContent);
        
        // Тест с method chaining - должен сохранять отступы
        $result = $this->replaceInFile->execute([
            'url' => 'git@github.com:vasenin26/docmodule.git',
            'path' => $testFile,
            'pattern' => "return Page::with(['creator', 'project'])",
            'replacement' => "Page::with(['creator', 'project'])\n    ->withCount(['children'])"
        ]);
        
        $this->assertInstanceOf(ToolResult::class, $result);
        $this->assertTrue($result->status);
        
        $actualContent = file_get_contents($this->testRepoPath . '/' . $testFile);
        $this->assertEquals($expectedContent, $actualContent);
        
        $payload = $result->payload;
        $this->assertEquals(1, $payload['replacements_made']);
    }

    public function testReplaceWithMethodChainingPattern(): void
    {
        $testFile = 'test_method_chaining_pattern.txt';
        $originalContent = "->with(['creator', 'project'])\n->find(\$id);";
        $expectedContent = "->with(['creator', 'project'])\n->withCount(['children'])\n->find(\$id);";
        
        file_put_contents($this->testRepoPath . '/' . $testFile, $originalContent);
        
        // Method chaining паттерн - должен сохранять отступы
        $result = $this->replaceInFile->execute([
            'url' => 'git@github.com:vasenin26/docmodule.git',
            'path' => $testFile,
            'pattern' => "->with(['creator', 'project'])",
            'replacement' => "->with(['creator', 'project'])\n->withCount(['children'])"
        ]);
        
        $this->assertInstanceOf(ToolResult::class, $result);
        $this->assertTrue($result->status);
        
        $actualContent = file_get_contents($this->testRepoPath . '/' . $testFile);
        $this->assertEquals($expectedContent, $actualContent);
    }

    public function testReplaceWithMultilinePattern(): void
    {
        $testFile = 'test_multiline.txt';
        $originalContent = "    return Page::with(['creator', 'project'])\n    ->find(\$id);";
        $expectedContent = "    Page::with(['creator', 'project'])\n    ->withCount(['children'])\n    ->find(\$id);";
        
        file_put_contents($this->testRepoPath . '/' . $testFile, $originalContent);
        
        $result = $this->replaceInFile->execute([
            'url' => 'git@github.com:vasenin26/docmodule.git',
            'path' => $testFile,
            'pattern' => "return Page::with(['creator', 'project'])",
            'replacement' => "Page::with(['creator', 'project'])\n    ->withCount(['children'])"
        ]);
        
        $this->assertInstanceOf(ToolResult::class, $result);
        $this->assertTrue($result->status);
        
        $actualContent = file_get_contents($this->testRepoPath . '/' . $testFile);
        $this->assertEquals($expectedContent, $actualContent);
    }

    public function testCreateFileIfNotExists(): void
    {
        $testFile = 'new_file.txt';
        
        // ReplaceInFile не поддерживает create_if_not_exists, поэтому файл должен существовать
        // Создаем файл сначала
        file_put_contents($this->testRepoPath . '/' . $testFile, 'Initial content');
        
        $result = $this->replaceInFile->execute([
            'url' => 'git@github.com:vasenin26/docmodule.git',
            'path' => $testFile,
            'pattern' => 'Initial',
            'replacement' => 'New'
        ]);
        
        $this->assertInstanceOf(ToolResult::class, $result);
        $this->assertTrue($result->status);
        $this->assertEquals('File updated successfully', $result->message);
        
        $actualContent = file_get_contents($this->testRepoPath . '/' . $testFile);
        $this->assertEquals('New content', $actualContent);
    }

    public function testNoReplacementsMade(): void
    {
        $testFile = 'test_no_replacements.txt';
        $originalContent = "Hello World\nThis is a test";
        
        file_put_contents($this->testRepoPath . '/' . $testFile, $originalContent);
        
        $result = $this->replaceInFile->execute([
            'url' => 'git@github.com:vasenin26/docmodule.git',
            'path' => $testFile,
            'pattern' => 'NotFound',
            'replacement' => 'Replaced'
        ]);
        
        $this->assertInstanceOf(ToolResult::class, $result);
        $this->assertTrue($result->status);
        $this->assertEquals('No matches found for pattern', $result->message);
        
        $payload = $result->payload;
        $this->assertEquals(0, $payload['replacements_made']);
        
        // Файл должен остаться неизменным
        $actualContent = file_get_contents($this->testRepoPath . '/' . $testFile);
        $this->assertEquals($originalContent, $actualContent);
    }

    public function testFileNotFoundError(): void
    {
        $result = $this->replaceInFile->execute([
            'url' => 'git@github.com:vasenin26/docmodule.git',
            'path' => 'nonexistent.txt',
            'pattern' => 'test',
            'replacement' => 'replaced'
        ]);
        
        $this->assertInstanceOf(ToolResult::class, $result);
        $this->assertFalse($result->status);
        $this->assertEquals('File not found: nonexistent.txt', $result->message);
        $this->assertEquals('FILE_NOT_FOUND', $result->payload['code']);
    }

    public function testInvalidArguments(): void
    {
        $result = $this->replaceInFile->execute([
            'url' => '',
            'path' => 'test.txt',
            'pattern' => 'test',
            'replacement' => 'replaced'
        ]);
        
        $this->assertInstanceOf(ToolResult::class, $result);
        $this->assertFalse($result->status);
        $this->assertEquals('Invalid arguments: url and path must be non-empty strings', $result->message);
        $this->assertEquals('ARGUMENTS_INVALID', $result->payload['code']);
    }

    public function testInvalidPattern(): void
    {
        $result = $this->replaceInFile->execute([
            'url' => 'git@github.com:vasenin26/docmodule.git',
            'path' => 'test.txt',
            'pattern' => '',
            'replacement' => 'replaced'
        ]);
        
        $this->assertInstanceOf(ToolResult::class, $result);
        $this->assertFalse($result->status);
        $this->assertEquals('Invalid arguments: pattern must be a non-empty string', $result->message);
        $this->assertEquals('PATTERN_INVALID', $result->payload['code']);
    }

    public function testReplaceWithSpecialCharacters(): void
    {
        $testFile = 'test_special_chars.txt';
        $originalContent = "Test content with special chars: [test]";
        
        file_put_contents($this->testRepoPath . '/' . $testFile, $originalContent);
        
        $result = $this->replaceInFile->execute([
            'url' => 'git@github.com:vasenin26/docmodule.git',
            'path' => $testFile,
            'pattern' => '[test]',
            'replacement' => '[replaced]'
        ]);
        
        $this->assertInstanceOf(ToolResult::class, $result);
        $this->assertTrue($result->status);
        
        $actualContent = file_get_contents($this->testRepoPath . '/' . $testFile);
        $this->assertEquals("Test content with special chars: [replaced]", $actualContent);
    }

    public function testRepositoryNotFoundError(): void
    {
        $nonExistentRepo = $this->createMock(GitRepository::class);
        $nonExistentRepo->method('getRepositoryPath')->willReturn('/nonexistent/path');
        
        $this->repoProvider->method('getRepo')->willReturn($nonExistentRepo);
        
        $result = $this->replaceInFile->execute([
            'url' => 'git@github.com:vasenin26/docmodule.git',
            'path' => 'test.txt',
            'pattern' => 'test',
            'replacement' => 'replaced'
        ]);
        
        $this->assertInstanceOf(ToolResult::class, $result);
        $this->assertFalse($result->status);
        $this->assertEquals('File not found: test.txt', $result->message);
        $this->assertEquals('FILE_NOT_FOUND', $result->payload['code']);
    }

    public function testPayloadContainsCorrectInformation(): void
    {
        $testFile = 'test_payload.txt';
        $originalContent = "Hello World";
        $expectedContent = "Hi World";
        
        file_put_contents($this->testRepoPath . '/' . $testFile, $originalContent);
        
        $result = $this->replaceInFile->execute([
            'url' => 'git@github.com:vasenin26/docmodule.git',
            'path' => $testFile,
            'pattern' => 'Hello',
            'replacement' => 'Hi'
        ]);
        
        $this->assertTrue($result->status);
        $payload = $result->payload;
        
        $this->assertEquals($testFile, $payload['file_path']);
        $this->assertStringContainsString('Hello', $payload['pattern']);
        $this->assertEquals('Hi', $payload['replacement']);
        $this->assertEquals(1, $payload['replacements_made']);
        $this->assertIsInt($payload['bytes_written']);
        $this->assertEquals(strlen($expectedContent), $payload['content_length']);
    }

    public function testExceptionHandling(): void
    {
        // Мокаем repoProvider чтобы он выбросил исключение
        $this->repoProvider->method('getRepo')->willThrowException(new \Exception('Test exception'));
        
        $result = $this->replaceInFile->execute([
            'url' => 'git@github.com:vasenin26/docmodule.git',
            'path' => 'test.txt',
            'pattern' => 'test',
            'replacement' => 'replaced'
        ]);
        
        $this->assertInstanceOf(ToolResult::class, $result);
        $this->assertFalse($result->status);
        $this->assertStringContainsString('Failed to replace in file', $result->message);
        $this->assertEquals('REPLACE_ERROR', $result->payload['code']);
        $this->assertEquals('Exception', $result->payload['exception']);
    }

    public function testReplaceWithLongMethodChain(): void
    {
        $testFile = 'test_long_method_chain.txt';
        $originalContent = "->with(['creator', 'project', 'parent', 'children', 'currentVersion', 'actualizations'])";
        $expectedContent = "->with(['creator', 'project', 'parent', 'children', 'currentVersion', 'actualizations'])\n            ->withCount(['children'])";
        
        file_put_contents($this->testRepoPath . '/' . $testFile, $originalContent);
        
        $result = $this->replaceInFile->execute([
            'url' => 'git@github.com:vasenin26/docmodule.git',
            'path' => $testFile,
            'pattern' => "->with(['creator', 'project', 'parent', 'children', 'currentVersion', 'actualizations'])",
            'replacement' => "->with(['creator', 'project', 'parent', 'children', 'currentVersion', 'actualizations'])\n            ->withCount(['children'])"
        ]);
        
        $this->assertInstanceOf(ToolResult::class, $result);
        $this->assertTrue($result->status);
        
        $actualContent = file_get_contents($this->testRepoPath . '/' . $testFile);
        $this->assertEquals($expectedContent, $actualContent);
    }

    public function testReplaceWithWhitespacePreservation(): void
    {
        $testFile = 'test_whitespace.txt';
        $originalContent = "    ->with(['creator', 'project'])\n    ->find(\$id);";
        $expectedContent = "    ->with(['creator', 'project'])\n    ->withCount(['children'])\n    ->find(\$id);";
        
        file_put_contents($this->testRepoPath . '/' . $testFile, $originalContent);
        
        // Тест для method chaining паттернов - должен сохранять отступы
        $result = $this->replaceInFile->execute([
            'url' => 'git@github.com:vasenin26/docmodule.git',
            'path' => $testFile,
            'pattern' => "->with(['creator', 'project'])",
            'replacement' => "->with(['creator', 'project'])\n    ->withCount(['children'])"
        ]);
        
        $this->assertInstanceOf(ToolResult::class, $result);
        $this->assertTrue($result->status);
        
        $actualContent = file_get_contents($this->testRepoPath . '/' . $testFile);
        $this->assertEquals($expectedContent, $actualContent);
    }

    public function testReplaceOrderByScenario(): void
    {
        $testFile = 'app/app/Services/PageContext/PageContextService.php';
        $originalContent = "            ->with(['creator', 'project', 'parent', 'children', 'currentVersion', 'actualizations'])\n            ->orderBy('created_at', 'desc')\n            ->get();";
        $expectedContent = "            ->with(['creator', 'project', 'parent', 'children', 'currentVersion', 'actualizations'])\n            ->withCount(['children'])\n            ->orderBy('created_at', 'desc')\n            ->get();";
        
        // Создаем директорию если нужно
        $dir = dirname($this->testRepoPath . '/' . $testFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        file_put_contents($this->testRepoPath . '/' . $testFile, $originalContent);
        
        // Точный сценарий из реального использования
        $result = $this->replaceInFile->execute([
            'url' => 'git@github.com:vasenin26/docmodule.git',
            'path' => $testFile,
            'pattern' => "->orderBy('created_at', 'desc')",
            'replacement' => "->withCount(['children'])\n            ->orderBy('created_at', 'desc')"
        ]);
        
        $this->assertInstanceOf(ToolResult::class, $result);
        $this->assertTrue($result->status);
        
        $actualContent = file_get_contents($this->testRepoPath . '/' . $testFile);
        $this->assertEquals($expectedContent, $actualContent);
        
        $payload = $result->payload;
        $this->assertEquals(1, $payload['replacements_made']);
    }

    public function testUserExampleScenario(): void
    {
        $testFile = 'test_user_example.txt';
        $originalContent = "я хочу какать";
        $expectedContent = "я не хочу какать";
        
        file_put_contents($this->testRepoPath . '/' . $testFile, $originalContent);
        
        // Пример из запроса пользователя
        $result = $this->replaceInFile->execute([
            'url' => 'git@github.com:vasenin26/docmodule.git',
            'path' => $testFile,
            'pattern' => 'хочу',
            'replacement' => 'не хочу'
        ]);
        
        $this->assertInstanceOf(ToolResult::class, $result);
        $this->assertTrue($result->status);
        $this->assertStringContainsString('File updated successfully', $result->message);
        
        $actualContent = file_get_contents($this->testRepoPath . '/' . $testFile);
        $this->assertEquals($expectedContent, $actualContent);
        
        $payload = $result->payload;
        $this->assertEquals(1, $payload['replacements_made']);
    }
}
