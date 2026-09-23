<?php

namespace Anymodule\Agentmodule\Tests\Unit\Application\Tools\Editor;

use Anymodule\Agentmodule\Application\Decorator\Tools\FileChangeTrackingDecorator;
use Anymodule\Agentmodule\Application\Tools\Editor\WriteFile;
use Anymodule\Agentmodule\Interface\Tools\FileChangeTrackerInterface;

class WriteFileTest extends EditorTestCase
{
    private WriteFile $tool;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tool = new WriteFile($this->repoProvider);
    }

    private function write(string $path, string $content)
    {
        return $this->tool->execute(['url' => self::URL, 'path' => $path, 'content' => $content]);
    }

    public function testCreatesFileWithParentDirectories(): void
    {
        $result = $this->write('/src/New/Foo.php', "<?php\n");

        $this->assertTrue($result->status, $result->message);
        $this->assertTrue($result->payload['created']);
        $this->assertSame("<?php\n", $this->readFile('src/New/Foo.php'));
        $this->assertSame(0644, fileperms($this->repoPath . '/src/New/Foo.php') & 0777);
        $this->assertNoTempFiles();
    }

    public function testOverwritesAndKeepsCrlf(): void
    {
        $this->putFile('a.txt', "old\r\n");

        $result = $this->write('a.txt', "new\nlines\n");

        $this->assertTrue($result->status);
        $this->assertFalse($result->payload['created']);
        $this->assertSame("new\r\nlines\r\n", $this->readFile('a.txt'));
    }

    public function testIdenticalContentIsNotTrackedAsChange(): void
    {
        $this->putFile('a.txt', "same\n");

        $tracker = $this->createMock(FileChangeTrackerInterface::class);
        $tracker->expects($this->never())->method('markChanged');

        $result = (new FileChangeTrackingDecorator($this->tool, $tracker))
            ->execute(['url' => self::URL, 'path' => 'a.txt', 'content' => "same\n"]);

        $this->assertTrue($result->status);
        $this->assertFalse($result->payload['changed']);
    }

    public function testRejectsDirectory(): void
    {
        mkdir($this->repoPath . '/dir');

        $result = $this->write('dir', 'x');

        $this->assertFalse($result->status);
        $this->assertSame('IS_DIRECTORY', $result->payload['code']);
    }
}
