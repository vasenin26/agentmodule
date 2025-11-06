<?php

namespace Anymodule\Agentmodule\Application\Tools;

use Anymodule\Agentmodule\Entity\ToolResult;
use Anymodule\Agentmodule\Interface\Tools\ToolInterface;

class PatchContent implements ToolInterface
{
    const NAME = 'patch-content';

    private string $title = '';
    private string $content = '';

    public function __construct(
        private string $name,
        private string $description,
        private string $message,
    )
    {
    }

    public function execute(array $args): ?ToolResult
    {
        try {
            if (!array_key_exists('content', $args) || !array_key_exists('title', $args)) {
                return null;
            }

            $content = $args['content'] ?? '';
            $err = $this->validateDiffFormat($content);

            if($err !== null){
                return new ToolResult(false, $err);
            }

            $this->content = $content;
            $this->title = $args['title'] ?? '';

            return new ToolResult(true, $this->message, []);
        } catch (\Throwable $e) {
            return new ToolResult(false, $e->getMessage(), ['exception' => get_class($e)]);
        }
    }

    public function getProps(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $this->getName(),
                'description' => $this->description,
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'title' => [
                            'type' => 'string',
                            'description' => 'Короткое название предлагаемых изменения (50 символов)',
                        ],
                        'content' => [
                            'type' => 'string',
                            'description' => <<<DESC
Patch declaration (format: Unified diff / git-style).

IMPORTANT: The patch must strictly follow Unified diff / git-style format.  
Generate only the diff between the current file version and the new one, do not rewrite the entire file.  

Example patch declaration:

diff --git a/docs/project-management.md b/docs/project-management.md
index 2a3b4c5..6d7e8f9 100644
--- a/docs/project-management.md
+++ b/docs/project-management.md
@@ -1,3 +1,7 @@
 # Project Management

 The system allows creating and editing projects.
+
+## Project Branch Updates
+
+The `updateProjectBranches` function synchronizes a project's branches with its remote repository.

LLM Hint: Think of this as a smart assistant task — detect new, modified, or removed lines and produce a concise patch that updates the file correctly, without rewriting unchanged parts.
Line numbers between @@ is important!
DESC
                        ]
                    ],
                    'required' => ['content'],
                ]
            ]
        ];
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPatch(): string
    {
        return json_encode([
            'title' => $this->title,
            'content' => $this->content,
        ]);
    }

    public function hasContent(): bool
    {
        return !empty($this->content) && !empty($this->title);
    }

    public function clear(): void
    {
        $this->content = '';
        $this->title = '';
    }


    private function validateDiffFormat(string $diff): ?string
    {
        $lines = preg_split("/\r\n|\n|\r/", trim($diff));

        if (empty($lines) || count($lines) < 5) {
            return "Diff is too short or empty.";
        }

        // 1. Проверяем первую строку
        if (!preg_match('/^diff\s+--git\s+a\/\S+\s+b\/\S+$/', $lines[0])) {
            return "Missing or invalid 'diff --git a/... b/...' header.";
        }

        // 2. Ищем index строку
        $indexLine = null;
        foreach ($lines as $line) {
            if (preg_match('/^index\s+[0-9a-fA-F]{7,}\.\.[0-9a-fA-F]{7,}(\s+\d+)?$/', $line)) {
                $indexLine = $line;
                break;
            }
        }
        if (!$indexLine) {
            return "Missing 'index' line (expected: index <sha1>..<sha2> <mode>).";
        }

        // 3. Проверяем наличие --- и +++
        $hasFrom = false;
        $hasTo = false;
        foreach ($lines as $line) {
            if (preg_match('/^---\s+(a\/\S+|\/dev\/null)$/', $line)) $hasFrom = true;
            if (preg_match('/^\+\+\+\s+(b\/\S+|\/dev\/null)$/', $line)) $hasTo = true;
        }

        if (!$hasFrom) {
            return "Missing '--- a/...' or '--- /dev/null' line.";
        }
        if (!$hasTo) {
            return "Missing '+++ b/...' or '+++ /dev/null' line.";
        }

        // 4. Проверяем наличие хотя бы одного хунка @@
        $hasChunk = false;
        foreach ($lines as $line) {
            if (preg_match('/^@@\s+-(\d+),?(\d+)?\s+\+(\d+),?(\d+)?\s@@/', $line)) {
                $hasChunk = true;
                break;
            }
        }

        if (!$hasChunk) {
            return "Missing chunk header ('@@ -x,y +a,b @@').";
        }

        // Если все проверки пройдены
        return null;
    }

}