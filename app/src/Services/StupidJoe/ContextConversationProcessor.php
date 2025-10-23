<?php

namespace Anymodule\Agentmodule\Services\StupidJoe;

use Anymodule\Agentmodule\Application\ChatAgent\Interface\ChatResultInterface;
use Anymodule\Agentmodule\Application\ChatAgent\Interface\ContextConversationProcessorInterface;
use Anymodule\Agentmodule\Entity\ContextConversation;
use Anymodule\Agentmodule\Entity\ModelMeta;
use Anymodule\Agentmodule\Interface\Tools\ToolsProviderInterface;
use Anymodule\Agentmodule\Services\StupidJoe\DTO\StupidResult;

class ContextConversationProcessor implements ContextConversationProcessorInterface
{

    public function __construct(
        private ModelMeta $modelMeta,
    )
    {
    }

    public function contextSize(): int
    {
        return $this->getModelMeta()->contextSize;
    }

    public function getModelMeta(): ModelMeta
    {
        return $this->modelMeta;
    }

    public function process(ContextConversation $contextConversation, ?ToolsProviderInterface $tools): ChatResultInterface
    {
        // Генерируем случайную белеберду
        $nonsense = $this->generateNonsense();
        
        // Возвращаем результат с случайным количеством токенов
        $sent = rand(10, 100);
        $received = rand(5, 50);
        $total = $sent + $received;
        
        // С вероятностью 70% вызываем случайный инструмент
        $toolCalls = [];
        if ($tools && rand(1, 100) <= 70) {
            $toolCalls = $this->generateRandomToolCall($tools);
        }
        
        return new StupidResult($nonsense, $sent, $received, $total, $toolCalls);
    }
    
    private function generateNonsense(): string
    {
        $nonsensePhrases = [
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
        
        return $nonsensePhrases[array_rand($nonsensePhrases)];
    }
    
    private function generateRandomToolCall(ToolsProviderInterface $tools): array
    {
        $availableTools = $tools->getMeta();
        
        if (empty($availableTools)) {
            return [];
        }
        
        // Выбираем случайный инструмент
        $randomTool = $availableTools[array_rand($availableTools)];
        $toolName = $randomTool['function']['name'];
        
        // Генерируем случайные параметры для инструмента
        $randomArgs = $this->generateRandomToolArgs($randomTool);
        
        return [
            [
                'id' => 'stupid_' . uniqid(),
                'name' => $toolName,
                'arguments' => json_encode($randomArgs)
            ]
        ];
    }
    
    private function generateRandomToolArgs(array $toolMeta): array
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
                $args[$paramName] = $this->generateRandomValue($properties[$paramName]);
            }
        }
        
        // С вероятностью 50% добавляем необязательные параметры
        foreach ($properties as $paramName => $paramMeta) {
            if (!in_array($paramName, $required) && rand(1, 100) <= 50) {
                $args[$paramName] = $this->generateRandomValue($paramMeta);
            }
        }
        
        return $args;
    }
    
    private function generateRandomValue(array $paramMeta): mixed
    {
        $type = $paramMeta['type'] ?? 'string';
        
        switch ($type) {
            case 'string':
                $randomStrings = [
                    'тупая строка',
                    'случайный текст',
                    'белеберда',
                    'непонятно что',
                    'что-то странное',
                    'случайные слова',
                    'неразборчиво',
                    'какая-то фигня'
                ];
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
}