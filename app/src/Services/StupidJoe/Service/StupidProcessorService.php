<?php

namespace Anymodule\Agentmodule\Services\StupidJoe\Service;

use Anymodule\Agentmodule\Entity\ModelMeta;
use Anymodule\Agentmodule\Services\OpenAIChat\Exception\ContextOverloadException;
use Anymodule\Agentmodule\Services\StupidJoe\DTO\StupidResult;
use Anymodule\Agentmodule\Interface\Tools\ToolsProviderInterface;
use Anymodule\Agentmodule\Utils\Log;
use mysql_xdevapi\Result;
use Vasenin26\Conversation\Chat;
use Vasenin26\Conversation\Interface\Conversation;
use Vasenin26\Conversation\Messages\SystemMessage;

class StupidProcessorService
{
    public function generateResponse(?ToolsProviderInterface $tools, ModelMeta $modelMeta, Conversation $contextData = null): StupidResult
    {
        sleep(1);

        $context = 'general';
        // Генерируем случайную белеберду
        $nonsense = $this->generateNonsense($context);

        // С вероятностью 98% вызываем случайный инструмент
        $toolCalls = [];
        if ($tools && rand(1, 100) <= 98) {
            $toolCalls = $this->generateRandomToolCall($tools, $context);
        }

        // Вычисляем реальное количество токенов на основе контекста
        $sent = $this->calculateSentTokens($contextData);
        $received = $this->calculateReceivedTokens($nonsense, $toolCalls);
        $total = $sent + $received;

        if ($total > $modelMeta->contextSize) {
            throw new ContextOverloadException();
        }

        return new StupidResult($nonsense, $sent, $received, $total, $toolCalls);
    }

    private function generateNonsense(string $context): string
    {
        $nonsensePhrases = $this->getNonsensePhrases();
        return $nonsensePhrases[array_rand($nonsensePhrases)];
    }

    private function getNonsensePhrases(): array
    {
        return [
            "Бла-бла-бла, я тупой процессор!",
            "Ква-ква-ква, лягушка говорит!",
            "Мяу-мяу, котик устал!",
            "Гав-гав, собачка хочет играть!",
            "Пи-пи-пи, птичка поет!",
            "Бу-бу-бу, сова не спит!",
            "Чик-чирик, воробей чирикает!",
            "Ку-ку, кукушка считает!",
            "Хрю-хрю, поросенок веселится!",
            "Му-му, корова мычит!",
            "Бе-бе, овечка блеет!",
            "И-го-го, лошадка скачет!",
            "Кря-кря, уточка плавает!",
            "Гу-гу, голубь воркует!",
            "Кукареку, петух кричит!",
            "У-у-у, волк воет!",
            "Р-р-р, тигр рычит!",
            "А-а-а, обезьяна кричит!",
            "Тук-тук, дятел стучит!",
            "Ж-ж-ж, пчела жужжит!"
        ];
    }

    private function generateRandomToolCall(ToolsProviderInterface $tools, string $context): array
    {
        $availableTools = $tools->getMeta();

        if (empty($availableTools)) {
            return [];
        }

        // Выбираем случайный инструмент
        $randomTool = $availableTools[array_rand($availableTools)];
        $toolName = $randomTool['function']['name'];

        // Генерируем случайные параметры для инструмента
        $randomArgs = $this->generateRandomToolArgs($randomTool, $context);

        $prefix = $context === 'chat' ? 'stupid_chat_' : 'stupid_';

        return [
            [
                'id' => $prefix . uniqid(),
                'name' => $toolName,
                'arguments' => json_encode($randomArgs)
            ]
        ];
    }

    private function generateRandomToolArgs(array $toolMeta, string $context): array
    {
        $args = [];

        if (!isset($toolMeta['function']['parameters']['properties'])) {
            return $args;
        }

        $properties = $toolMeta['function']['parameters']['properties'];
        $required = $toolMeta['function']['parameters']['required'] ?? [];

        // Генерируем случайные значения для всех обязательных параметров
        foreach ($required as $paramName) {
            if (isset($properties[$paramName])) {
                $args[$paramName] = $this->generateRandomValue($properties[$paramName], $context);
            }
        }

        foreach ($properties as $paramName => $paramMeta) {
            if (!in_array($paramName, $required) && rand(1, 100) <= 98) {
                $args[$paramName] = $this->generateRandomValue($paramMeta, $context);
            }
        }

        return $args;
    }

    private function generateRandomValue(array $paramMeta, string $context): mixed
    {
        $type = $paramMeta['type'] ?? 'string';

        switch ($type) {
            case 'string':
                $randomStrings = $this->getRandomStrings();
                return $randomStrings[array_rand($randomStrings)];

            case 'integer':
                return rand(1, 1000);

            case 'boolean':
                return rand(0, 1) === 1;

            case 'array':
                return ['тупой', 'массив', 'данных'];

            default:
                return 'неизвестный тип';
        }
    }

    private function getRandomStrings(): array
    {
        return [
            'тупая строка',
            'случайный текст',
            'белеберда',
            'непонятно что',
            'что-то странное',
            'случайные слова',
            'неразборчиво',
            'какая-то фигня'
        ];
    }

    private function calculateSentTokens(Conversation $conversation): int
    {
        $chat = new Chat();

        foreach ($conversation->getMessages() as $message) {
            if ($message instanceof SystemMessage) {
                continue;
            }
            $chat->addMessage($message);
        }

        $charCount = mb_strlen(json_encode($chat->serialize()));
        $result = max(1, intval($charCount / 4));

        Log::info("Context size: $result");

        return $result;
    }

    private function calculateReceivedTokens(string $nonsense, array $toolCalls): int
    {
        // Подсчитываем токены в ответе
        $responseText = $nonsense;

        // Добавляем токены от вызовов инструментов
        foreach ($toolCalls as $toolCall) {
            $responseText .= ' ' . $toolCall['name'] . ' ' . $toolCall['arguments'];
        }

        $charCount = strlen($responseText);

        // Примерное соотношение: 1 токен ≈ 4 символа для русского текста
        return max(1, intval($charCount / 4));
    }
}
