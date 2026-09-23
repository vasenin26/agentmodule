<?php

namespace Anymodule\Agentmodule\Application\Tools\Editor\Support;

use Anymodule\Agentmodule\Entity\ToolResult;
use Anymodule\Agentmodule\Interface\Git\GitRepoProviderInterface;
use Anymodule\Agentmodule\Interface\Tools\FileModifyingToolInterface;

abstract class AbstractEditorTool implements FileModifyingToolInterface
{
    protected Workspace $workspace;

    public function __construct(GitRepoProviderInterface $repoProvider)
    {
        $this->workspace = new Workspace($repoProvider);
    }

    abstract protected function handle(array $args): ToolResult;

    public function execute(array $args): ?ToolResult
    {
        try {
            return $this->handle($args);
        } catch (EditorException $e) {
            return new ToolResult(false, $e->getMessage(), ['code' => $e->errorCode] + $e->details);
        } catch (\Throwable $e) {
            return new ToolResult(false, 'Editor error: ' . $e->getMessage(), ['code' => 'EDIT_ERROR', 'exception' => get_class($e)]);
        }
    }

    public function getName(): string
    {
        return static::NAME;
    }

    protected function stringArg(array $args, string $name, bool $allowEmpty = false): string
    {
        $value = $args[$name] ?? null;

        if (!is_string($value)) {
            throw new EditorException("Missing required string parameter '$name'.", 'ARGUMENTS_INVALID');
        }

        if (!$allowEmpty && $value === '') {
            throw new EditorException("Parameter '$name' must not be empty.", 'ARGUMENTS_INVALID');
        }

        return $value;
    }

    protected function intArg(array $args, string $name): int
    {
        $value = $args[$name] ?? null;

        if (is_string($value) && preg_match('/^-?\d+$/', trim($value))) {
            $value = (int)trim($value);
        }

        if (is_float($value) && floor($value) === $value) {
            $value = (int)$value;
        }

        if (!is_int($value)) {
            throw new EditorException("Missing required integer parameter '$name'.", 'ARGUMENTS_INVALID');
        }

        return $value;
    }

    protected function boolArg(array $args, string $name, bool $default = false): bool
    {
        $value = $args[$name] ?? $default;

        return is_string($value) ? filter_var($value, FILTER_VALIDATE_BOOL) : (bool)$value;
    }

    protected static function repoParams(): array
    {
        return [
            'url' => [
                'type' => 'string',
                'description' => 'Git repository url.',
            ],
            'path' => [
                'type' => 'string',
                'description' => 'File path relative to the repository root, e.g. "src/Service/Foo.php".',
            ],
        ];
    }
}
