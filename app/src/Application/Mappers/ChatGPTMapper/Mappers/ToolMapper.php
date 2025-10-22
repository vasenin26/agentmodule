<?php

namespace Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Mappers;

use Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Interface\MessageMapperInterface;
use Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Mappers\ToolResult\{SendResultToolMapper,
    Tasks\ListTasksToolMapper};
use Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Mappers\ToolResult\CatchContentToolMapper;
use Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Mappers\ToolResult\CurrentTimeToolMapper;
use Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Mappers\ToolResult\Editor\{ReplaceInFileToolMapper};
use Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Mappers\ToolResult\Editor\EditFileToolMapper;
use Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Mappers\ToolResult\Editor\InsertOrReplaceToolMapper;
use Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Mappers\ToolResult\Git\{AnalyzeStructureToolMapper,
    RepoManagement\ResetHardToolMapper};
use Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Mappers\ToolResult\Git\AnalyzeClassesToolMapper;
use Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Mappers\ToolResult\Git\FindConfigFilesToolMapper;
use Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Mappers\ToolResult\Git\GetDependenciesToolMapper;
use Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Mappers\ToolResult\Git\GrepFileToolMapper;
use Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Mappers\ToolResult\Git\ReadDirToolMapper;
use Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Mappers\ToolResult\Git\ReadFileLinesToolMapper;
use Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Mappers\ToolResult\Git\ReadFileToolMapper;
use Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Mappers\ToolResult\Git\RepoManagement\{
    GetCurrentBranchToolMapper};
use Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Mappers\ToolResult\Git\RepoManagement\AddFileToolMapper;
use Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Mappers\ToolResult\Git\RepoManagement\CommitToolMapper;
use Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Mappers\ToolResult\Git\RepoManagement\GetStatusToolMapper;
use Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Mappers\ToolResult\Git\RepoManagement\PullToolMapper;
use Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Mappers\ToolResult\Git\RepoManagement\PushToolMapper;
use Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Mappers\ToolResult\Git\RepoManagement\UnstageFileToolMapper;
use Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Mappers\ToolResult\Git\SearchFileByNameToolMapper;
use Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Mappers\ToolResult\Git\SearchPatternToolMapper;
use Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Mappers\ToolResult\Page\{FindRelatedPagesToolMapper};
use Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Mappers\ToolResult\Page\GetActualizationInfoToolMapper;
use Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Mappers\ToolResult\Page\GetAttachedFilesToolMapper;
use Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Mappers\ToolResult\Page\GetHierarchyTreeToolMapper;
use Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Mappers\ToolResult\Page\GetInfoToolMapper;
use Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Mappers\ToolResult\Page\GetProjectPagesToolMapper;
use Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Mappers\ToolResult\Page\GetTaskHistoryToolMapper;
use Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Mappers\ToolResult\Tasks\{CompleteTaskToolMapper};
use Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Mappers\ToolResult\Tasks\AddTasksToolMapper;
use Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Mappers\ToolResult\Utils\{AddFileToListToolMapper};
use Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Mappers\ToolResult\Utils\UpdateArticleToolMapper;
use Vasenin26\Conversation\Message;
use Vasenin26\Conversation\Messages\ToolMessage;

// Git mappers

// Git RepoManagement mappers

// Editor mappers

// Tasks mappers

// Page mappers

// Utils mappers

// Base mappers

class ToolMapper implements MessageMapperInterface
{
    private array $mappers;

    public function __construct()
    {
        $this->mappers = [
            new ReadDirToolMapper(),
            new ReadFileLinesToolMapper(),
            new ReadFileToolMapper(),
            new SearchFileByNameToolMapper(),
            new ReplaceInFileToolMapper(),
            new CurrentTimeToolMapper(),
            new CatchContentToolMapper(),
            new SendResultToolMapper(),
            new AddFileToListToolMapper(),
            new ListTasksToolMapper(),
            new AddTasksToolMapper(),
            new GetInfoToolMapper(),
            new GetProjectPagesToolMapper(),
            new GetHierarchyTreeToolMapper(),
            new AnalyzeStructureToolMapper(),
            new UpdateArticleToolMapper(),
            new GrepFileToolMapper(),
            new SearchPatternToolMapper(),
            new CompleteTaskToolMapper(),
            new ResetHardToolMapper(),
            new UnstageFileToolMapper(),
            new EditFileToolMapper(),
            new AnalyzeClassesToolMapper(),
            new GetAttachedFilesToolMapper(),
            new GetActualizationInfoToolMapper(),
            new FindRelatedPagesToolMapper(),
            new InsertOrReplaceToolMapper(),
            new GetDependenciesToolMapper(),
            new PullToolMapper(),
            new FindConfigFilesToolMapper(),
            new GetStatusToolMapper(),
            new GetCurrentBranchToolMapper(),
            new PushToolMapper(),
            new CommitToolMapper(),
            new AddFileToolMapper(),
            new GetTaskHistoryToolMapper(),
        ];
    }

    public function supports(Message $message): bool
    {
        if ($message instanceof ToolMessage) {
            return !empty($message->id);
        }

        return false;
    }

    public function map(Message $message): array
    {
        if($message instanceof ToolMessage) {
            $content = null;

            foreach ($this->mappers as $mapper) {
                if ($mapper->supports($message)) {
                    $content =  $mapper->map($message);
                }
            }

            return [
                'role' => 'tool',
                'tool_call_id' => $message->id,
                'content' => $content ?? $message->result,
            ];
        }

        throw new \Exception("Unsupported message type");
    }
}