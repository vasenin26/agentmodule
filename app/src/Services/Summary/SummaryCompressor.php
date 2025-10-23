<?php

namespace Anymodule\Agentmodule\Services\Summary;

use Anymodule\Agentmodule\Application\Conversation\ConversationSlice;
use Anymodule\Agentmodule\Interface\ChatSummaryGeneratorInterface;
use Anymodule\Agentmodule\Interface\ConversationCompressorInterface;
use Anymodule\Agentmodule\Utils\Log;
use Vasenin26\Conversation\Interface\Conversation;
use Vasenin26\Conversation\Messages\AssistantMessage;
use Vasenin26\Conversation\Messages\DisappearingMessage;
use Vasenin26\Conversation\Messages\ServiceMessage;
use Vasenin26\Conversation\Messages\SliceMessage;
use Vasenin26\Conversation\Messages\SystemMessage;
use Vasenin26\Conversation\Messages\UserTaskMessage;

final readonly class SummaryCompressor implements ConversationCompressorInterface
{

    public function __construct(
        private ChatSummaryGeneratorInterface $summaryGenerator,
    )
    {
    }

    public function compress(Conversation $conversation): Conversation
    {
        Log::info("Start compressing conversation");

        $compressed = new ConversationSlice($conversation);

        foreach ($conversation->getMessages() as $message) {
            if (
                $message instanceof SystemMessage or
                $message instanceof ServiceMessage or
                $message instanceof UserTaskMessage
            ) {
                $compressed->push($message);
            }
        }

        $sliceMarker = new SliceMessage(uniqid('slice-'), 'Compress conversation', ['summary' => '']);
        $markerLink = $compressed->addServiceMessage($sliceMarker);

        foreach ($this->summaryGenerator->generate($conversation) as $processResult) {
            $markerLink->setPayload([
                'context_fill' => $processResult->contextFill,
            ]);

            if ($processResult->completed) {
                Log::info("Compressing summary");
                Log::info($processResult->answer);

                $markerLink->complete();

                if (!empty($processResult->answer)) {
                    $compressed->addMessage(new AssistantMessage($processResult->answer, []));
                }
            }
        }

        $compressed->push(new DisappearingMessage("Check task list before state. Then continue work"));

        return $compressed;
    }
}