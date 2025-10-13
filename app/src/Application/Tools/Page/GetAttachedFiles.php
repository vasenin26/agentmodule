<?php

namespace Anymodule\Agentmodule\Application\Tools\Page;


use Anymodule\Agentmodule\Entity\ToolResult;
use Anymodule\Agentmodule\Interface\Git\GitRepoProviderInterface;
use Anymodule\Agentmodule\Interface\Page\PageContextServiceInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolInterface;

class GetAttachedFiles implements ToolInterface
{
    const NAME = 'page-get-attached-files';

    public function __construct(
        private PageContextServiceInterface $pageContextService,
        private GitRepoProviderInterface $gitRepoProvider
    ) {
    }

    public function execute(array $args): ?ToolResult
    {
        try {
            ['page_id' => $pageId] = $args;
            $loadContent = $args['load_content'] ?? false;

            if (!is_numeric($pageId) || $pageId <= 0) {
                return new ToolResult(false, 'Invalid page ID provided', ['code' => 'INVALID_PAGE_ID']);
            }

            if (!$this->pageContextService->validatePageAccess((int)$pageId)) {
                return new ToolResult(false, 'Page not found or not accessible in current project context', ['code' => 'PAGE_ACCESS_DENIED']);
            }

            $files = $this->pageContextService->getPageFiles((int)$pageId);

            if (empty($files)) {
                return new ToolResult(true, 'No files attached to this page', [
                    'page_id' => (int)$pageId,
                    'files_count' => 0,
                    'files' => [],
                ]);
            }

            $filesData = [];
            foreach ($files as $fileUrl) {
                $fileInfo = [
                    'url' => $fileUrl,
                    'is_accessible' => $this->checkFileAccessibility($fileUrl),
                    'file_type' => $this->detectFileType($fileUrl)
                ];

                if ($loadContent && $fileInfo['is_accessible']) {
                    $fileInfo['content'] = $this->loadFileContent($fileUrl);
                    $fileInfo['size'] = strlen($fileInfo['content'] ?? '');
                } else {
                    $fileInfo['content'] = null;
                    $fileInfo['size'] = null;
                }

                $filesData[] = $fileInfo;
            }

            return new ToolResult(true, 'Page files retrieved successfully', [
                'page_id' => (int)$pageId,
                'files_count' => count($filesData),
                'files' => $filesData,
                'content_loaded' => $loadContent,
            ]);

        } catch (\Throwable $e) {
            return new ToolResult(false, 'Failed to retrieve page files: ' . $e->getMessage(), ['code' => 'GET_PAGE_FILES_ERROR', 'exception' => get_class($e)]);
        }
    }

    private function checkFileAccessibility(string $fileUrl): bool
    {
        try {
            $parsedUrl = parse_url($fileUrl);
            
            // Проверяем, что это git URL
            if (!isset($parsedUrl['host']) || !in_array($parsedUrl['host'], ['github.com', 'gitlab.com', 'bitbucket.org'])) {
                return false;
            }

            // Пробуем получить репозиторий из URL
            $this->gitRepoProvider->getRepo($fileUrl);
            return true;
            
        } catch (\Exception $e) {
            return false;
        }
    }

    private function detectFileType(string $fileUrl): string
    {
        $extension = pathinfo(parse_url($fileUrl, PHP_URL_PATH), PATHINFO_EXTENSION);
        
        $typeMap = [
            'php' => 'PHP',
            'js' => 'JavaScript',
            'ts' => 'TypeScript',
            'vue' => 'Vue Component',
            'blade.php' => 'Blade Template',
            'html' => 'HTML',
            'css' => 'CSS',
            'scss' => 'SCSS',
            'less' => 'LESS',
            'json' => 'JSON',
            'xml' => 'XML',
            'yml' => 'YAML',
            'yaml' => 'YAML',
            'md' => 'Markdown',
            'txt' => 'Text',
            'sql' => 'SQL',
            'py' => 'Python',
            'go' => 'Go',
            'java' => 'Java',
            'c' => 'C',
            'cpp' => 'C++',
            'h' => 'Header'
        ];

        return $typeMap[$extension] ?? 'Unknown';
    }

    private function loadFileContent(string $fileUrl): ?string
    {
        try {
            // Извлекаем репозиторий и путь к файлу из URL
            $parsedUrl = parse_url($fileUrl);
            $pathParts = explode('/', trim($parsedUrl['path'], '/'));
            
            // Простая логика для GitHub-подобных URL
            if (count($pathParts) >= 5 && ($pathParts[2] === 'blob' || $pathParts[2] === 'raw')) {
                $repoUrl = 'https://' . $parsedUrl['host'] . '/' . $pathParts[0] . '/' . $pathParts[1];
                $filePath = implode('/', array_slice($pathParts, 4));
                
                $repo = $this->gitRepoProvider->getRepo($repoUrl);
                $fullPath = $repo->getRepositoryPath() . '/' . $filePath;
                
                if (file_exists($fullPath)) {
                    $content = file_get_contents($fullPath);
                    return $content !== false ? $content : null;
                }
            }
            
            return null;
            
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getProps(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $this->getName(),
                'description' => 'Get list of files attached to a page with optional content loading (only works with current page versions in project context)',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'page_id' => [
                            'type' => 'integer',
                            'description' => 'ID of the page to get attached files for',
                        ],
                        'load_content' => [
                            'type' => 'boolean',
                            'description' => 'Whether to load the actual content of the files (default: false)',
                        ]
                    ],
                    'required' => ['page_id'],
                ]
            ]
        ];
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
