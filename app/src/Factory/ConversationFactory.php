<?php

namespace Anymodule\Agentmodule\Factory;

use Vasenin26\Conversation\Chat;
use Anymodule\Agentmodule\Interface\ConversationFactoryInterface;
use Anymodule\Agentmodule\Interface\MessageFactoryInterface;
use Anymodule\Agentmodule\Interface\MessageValidatorInterface;

class ConversationFactory implements ConversationFactoryInterface
{
    private MessageValidatorInterface $validator;
    private MessageFactoryInterface $messageFactory;
    
    public function __construct(
        ?MessageValidatorInterface $validator = null,
        ?MessageFactoryInterface $messageFactory = null
    ) {
        $this->validator = $validator ?? new \Anymodule\Agentmodule\Services\MessageValidator\CompositeMessageValidator(
            new MessageTypeValidatorFactory()
        );
        $this->messageFactory = $messageFactory ?? new MessageFactory();
    }
    
    public function fromMessages(array $messages): Chat
    {
        $chat = new Chat();
        
        foreach ($messages as $messageData) {
            if (!$this->validator->isValidMessage($messageData)) {
                // Пропускаем невалидные сообщения
                continue;
            }
            
            $message = $this->messageFactory->createMessage(
                $messageData['type'],
                $messageData['message']
            );
            $chat->addMessage($message);
        }
        
        return $chat;
    }
}
