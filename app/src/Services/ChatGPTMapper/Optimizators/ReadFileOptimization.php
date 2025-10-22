<?php

namespace Anymodule\Agentmodule\Services\ChatGPTMapper\Optimizators;

use Anymodule\Agentmodule\Application\Tools\Git\ReadFile;
use Anymodule\Agentmodule\Services\ChatGPTMapper\Interface\ToolResultOptimizationInterface;
use Vasenin26\Conversation\Messages\ToolMessage;

class ReadFileOptimization implements ToolResultOptimizationInterface
{

    public function supports(ToolMessage $message): bool
    {
        return $message->name === ReadFile::NAME;
    }

    public function optimize(ToolMessage $message): ToolMessage
    {
        $result = $message->result;
        $data = json_decode($result, true);
        $content = $data["content"];

        $explode = explode("\n", $content);
        $fullLength = count($explode);
        $slice = array_slice($explode, 0, 50);
        $length = count($slice);
        $cutContent = join("\n", $slice);

        return new ToolMessage(
            $message->success,
            $message->id,
            $message->name,
            $message->args,
            "This is cut content of file."
            . " These are the first 10 of 100 lines.\n"
            . "``` $cutContent ``` \n"
            . "read file again for getting full content"
        );
    }
}