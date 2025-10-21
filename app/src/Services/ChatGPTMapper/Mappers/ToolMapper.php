<?php

namespace Anymodule\Agentmodule\Services\ChatGPTMapper\Mappers;

use Anymodule\Agentmodule\Services\ChatGPTMapper\Interface\MessageMapperInterface;

// Git mappers
use Anymodule\Agentmodule\Services\ChatGPTMapper\Mappers\ToolResult\Git\{
    ReadDirToolMapper,
    ReadFileLinesToolMapper,
    ReadFileToolMapper,
    SearchFileByNameToolMapper,
    AnalyzeStructureToolMapper,
    GrepFileToolMapper,
    SearchPatternToolMapper,
    AnalyzeClassesToolMapper,
    GetDependenciesToolMapper,
    FindConfigFilesToolMapper
};

// Git RepoManagement mappers
use Anymodule\Agentmodule\Services\ChatGPTMapper\Mappers\ToolResult\Git\RepoManagement\{
    ResetHardToolMapper,
    UnstageFileToolMapper,
    PullToolMapper,
    GetStatusToolMapper,
    GetCurrentBranchToolMapper,
    PushToolMapper,
    CommitToolMapper,
    AddFileToolMapper
};

// Editor mappers
use Anymodule\Agentmodule\Services\ChatGPTMapper\Mappers\ToolResult\Editor\{
    ReplaceInFileToolMapper,
    EditFileToolMapper,
    InsertOrReplaceToolMapper
};

// Tasks mappers
use Anymodule\Agentmodule\Services\ChatGPTMapper\Mappers\ToolResult\Tasks\{
    ListTasksToolMapper,
    AddTasksToolMapper,
    CompleteTaskToolMapper
};

// Page mappers
use Anymodule\Agentmodule\Services\ChatGPTMapper\Mappers\ToolResult\Page\{
    GetInfoToolMapper,
    GetProjectPagesToolMapper,
    GetHierarchyTreeToolMapper,
    GetAttachedFilesToolMapper,
    GetActualizationInfoToolMapper,
    FindRelatedPagesToolMapper,
    GetTaskHistoryToolMapper
};

// Utils mappers
use Anymodule\Agentmodule\Services\ChatGPTMapper\Mappers\ToolResult\Utils\{
    AddFileToListToolMapper,
    UpdateArticleToolMapper
};

// Base mappers
use Anymodule\Agentmodule\Services\ChatGPTMapper\Mappers\ToolResult\{
    CurrentTimeToolMapper,
    CatchContentToolMapper,
    SendResultToolMapper
};
use Vasenin26\Conversation\Message;
use Vasenin26\Conversation\Messages\ToolMessage;

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