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

            $this->content = $args['content'] ?? '';
            $this->title = $args['title'] ?? '';

            if(!str_starts_with($this->content, 'diff')) {
                return new ToolResult(false, 'Patch is not in diff format');
            }

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
}