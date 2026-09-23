<?php

namespace Anymodule\Agentmodule\Tests\Unit\Application\Tools\Editor;

use Anymodule\Agentmodule\Application\Tools\Editor\Insert;

class InsertTest extends EditorTestCase
{
    private Insert $tool;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tool = new Insert($this->repoProvider);
    }

    private function insert(string $path, int|string $line, string $text)
    {
        return $this->tool->execute([
            'url' => self::URL,
            'path' => $path,
            'insert_line' => $line,
            'text' => $text,
        ]);
    }

    public function testInsertAfterLine(): void
    {
        $this->putFile('a.txt', "1\n2\n3\n");

        $result = $this->insert('a.txt', 2, "2a\n2b\n");

        $this->assertTrue($result->status, $result->message);
        $this->assertSame("1\n2\n2a\n2b\n3\n", $this->readFile('a.txt'));
        $this->assertSame(3, $result->payload['start_line']);
        $this->assertSame(4, $result->payload['end_line']);
        $this->assertStringContainsString("     3\t2a", $result->message);
    }

    public function testInsertAtBeginning(): void
    {
        $this->putFile('a.txt', "1\n");

        $this->insert('a.txt', 0, 'first');

        $this->assertSame("first\n1\n", $this->readFile('a.txt'));
    }

    public function testAppendWithMinusOneKeepsTrailingNewline(): void
    {
        $this->putFile('a.txt', "1\n2\n");

        $result = $this->insert('a.txt', -1, 'end');

        $this->assertTrue($result->status);
        $this->assertSame("1\n2\nend\n", $this->readFile('a.txt'));
        $this->assertStringContainsString('at the end', $result->message);
    }

    public function testAppendToFileWithoutTrailingNewline(): void
    {
        $this->putFile('a.txt', "1\n2");

        $this->insert('a.txt', 2, 'end');

        $this->assertSame("1\n2\nend", $this->readFile('a.txt'));
    }

    public function testInsertIntoEmptyFile(): void
    {
        $this->putFile('a.txt', '');

        $this->insert('a.txt', 0, 'hello');

        $this->assertSame("hello\n", $this->readFile('a.txt'));
    }

    public function testKeepsCrlf(): void
    {
        $this->putFile('a.txt', "1\r\n2\r\n");

        $this->insert('a.txt', 1, "x\ny");

        $this->assertSame("1\r\nx\r\ny\r\n2\r\n", $this->readFile('a.txt'));
    }

    public function testAcceptsNumericString(): void
    {
        $this->putFile('a.txt', "1\n");

        $result = $this->insert('a.txt', '1', 'x');

        $this->assertTrue($result->status);
    }

    public function testOutOfRangeExplainsValidValues(): void
    {
        $this->putFile('a.txt', "1\n2\n");

        $result = $this->insert('a.txt', 5, 'x');

        $this->assertFalse($result->status);
        $this->assertSame('INVALID_LINE_NUMBER', $result->payload['code']);
        $this->assertStringContainsString('has 2 lines', $result->message);
        $this->assertSame("1\n2\n", $this->readFile('a.txt'));
    }
}
