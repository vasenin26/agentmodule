<?php

namespace Anymodule\Agentmodule\Tests\Unit\Application\Tools\Editor;

use Anymodule\Agentmodule\Application\Tools\Editor\StrReplace;
use Anymodule\Agentmodule\Interface\Tools\FileModifyingToolInterface;

class StrReplaceTest extends EditorTestCase
{
    private StrReplace $tool;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tool = new StrReplace($this->repoProvider);
    }

    private function replace(string $path, string $old, string $new, bool $replaceAll = false)
    {
        return $this->tool->execute([
            'url' => self::URL,
            'path' => $path,
            'old_string' => $old,
            'new_string' => $new,
            'replace_all' => $replaceAll,
        ]);
    }

    public function testSchemaIsValidFunctionDefinition(): void
    {
        $props = $this->tool->getProps();

        $this->assertInstanceOf(FileModifyingToolInterface::class, $this->tool);
        $this->assertSame('editor-str-replace', $props['function']['name']);
        $this->assertSame('object', $props['function']['parameters']['type']);
        $this->assertSame(['url', 'path', 'old_string', 'new_string'], $props['function']['parameters']['required']);
    }

    public function testReplacesUniqueFragmentAndReturnsNumberedSnippet(): void
    {
        $this->putFile('a.php', "<?php\n\nfunction foo()\n{\n    return 1;\n}\n");

        $result = $this->replace('a.php', '    return 1;', '    return 2;');

        $this->assertTrue($result->status, $result->message);
        $this->assertSame("<?php\n\nfunction foo()\n{\n    return 2;\n}\n", $this->readFile('a.php'));
        $this->assertSame(5, $result->payload['start_line']);
        $this->assertStringContainsString("     5\t    return 2;", $result->message);
        $this->assertNoTempFiles();
    }

    public function testFailsOnAmbiguousMatchAndReportsLines(): void
    {
        $this->putFile('a.txt', "x = 1\ny = 2\nx = 1\n");

        $result = $this->replace('a.txt', 'x = 1', 'x = 3');

        $this->assertFalse($result->status);
        $this->assertSame('MULTIPLE_MATCHES', $result->payload['code']);
        $this->assertSame([1, 3], $result->payload['match_lines']);
        $this->assertStringContainsString('replace_all', $result->message);
        $this->assertSame("x = 1\ny = 2\nx = 1\n", $this->readFile('a.txt'));
    }

    public function testReplaceAll(): void
    {
        $this->putFile('a.txt', "foo bar foo\nfoo\n");

        $result = $this->replace('a.txt', 'foo', 'baz', true);

        $this->assertTrue($result->status);
        $this->assertSame(3, $result->payload['replacements']);
        $this->assertSame("baz bar baz\nbaz\n", $this->readFile('a.txt'));
    }

    public function testEmptyNewStringDeletesFragment(): void
    {
        $this->putFile('a.txt', "keep\nremove me\nkeep too\n");

        $result = $this->replace('a.txt', "remove me\n", '');

        $this->assertTrue($result->status);
        $this->assertSame("keep\nkeep too\n", $this->readFile('a.txt'));
    }

    public function testMultilineReplacementKeepsCrlfLineEndings(): void
    {
        $this->putFile('win.txt', "one\r\ntwo\r\nthree\r\n");

        $result = $this->replace('win.txt', "one\ntwo", "one\n1.5\ntwo");

        $this->assertTrue($result->status, $result->message);
        $this->assertSame("one\r\n1.5\r\ntwo\r\nthree\r\n", $this->readFile('win.txt'));
    }

    public function testNotFoundSuggestsWhitespaceInsensitiveMatch(): void
    {
        $this->putFile('a.php', "class A\n{\n\tpublic function x()\n\t{\n\t}\n}\n");

        $result = $this->replace('a.php', "    public function x()\n    {", 'nope');

        $this->assertFalse($result->status);
        $this->assertSame('NOT_FOUND', $result->payload['code']);
        $this->assertSame([3, 4], $result->payload['similar_lines']);
        $this->assertStringContainsString("     3\t\tpublic function x()", $result->message);
    }

    public function testNotFoundDetectsLineNumberPrefixes(): void
    {
        $this->putFile('a.txt', "alpha\nbeta\n");

        $result = $this->replace('a.txt', "L1:alpha\nL2:beta", 'x');

        $this->assertFalse($result->status);
        $this->assertStringContainsString('line-number prefixes', $result->message);
    }

    public function testNotFoundShowsClosestLine(): void
    {
        $this->putFile('a.php', "<?php\n\$total = calculateTotal(\$items);\necho \$total;\n");

        $result = $this->replace('a.php', '$total = calculateTotals($items);', 'x');

        $this->assertFalse($result->status);
        $this->assertStringContainsString('near line 2', $result->message);
    }

    public function testIdenticalStringsAreRejected(): void
    {
        $this->putFile('a.txt', "a\n");

        $result = $this->replace('a.txt', 'a', 'a');

        $this->assertFalse($result->status);
        $this->assertSame('NO_CHANGE', $result->payload['code']);
    }

    public function testMissingFile(): void
    {
        $result = $this->replace('missing.txt', 'a', 'b');

        $this->assertFalse($result->status);
        $this->assertSame('FILE_NOT_FOUND', $result->payload['code']);
        $this->assertStringContainsString('editor-write-file', $result->message);
    }

    public function testRejectsPathOutsideRepository(): void
    {
        file_put_contents($this->tempDir . '/secret.txt', 'a');

        $result = $this->replace('../secret.txt', 'a', 'b');

        $this->assertFalse($result->status);
        $this->assertSame('PATH_OUTSIDE_REPO', $result->payload['code']);
        $this->assertSame('a', file_get_contents($this->tempDir . '/secret.txt'));
    }

    public function testRejectsGitDirectory(): void
    {
        $this->putFile('.git/config', 'a');

        $result = $this->replace('.git/config', 'a', 'b');

        $this->assertFalse($result->status);
        $this->assertSame('PATH_FORBIDDEN', $result->payload['code']);
    }

    public function testMissingArgumentsReturnReadableError(): void
    {
        $result = $this->tool->execute(['url' => self::URL, 'path' => 'a.txt']);

        $this->assertFalse($result->status);
        $this->assertSame('ARGUMENTS_INVALID', $result->payload['code']);
        $this->assertStringContainsString('old_string', $result->message);
    }

    public function testPreservesFilePermissions(): void
    {
        $this->putFile('run.sh', "echo 1\n");
        chmod($this->repoPath . '/run.sh', 0755);

        $this->replace('run.sh', 'echo 1', 'echo 2');

        clearstatcache();
        $this->assertSame(0755, fileperms($this->repoPath . '/run.sh') & 0777);
    }
}
