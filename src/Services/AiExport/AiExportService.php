<?php

namespace Opcodes\LogViewer\Services\AiExport;

use Illuminate\Support\Collection;
use Opcodes\LogViewer\Facades\LogViewer;
use Opcodes\LogViewer\LogFile;
use Opcodes\LogViewer\Services\AiExport\Contracts\AiProviderInterface;
use Opcodes\LogViewer\Services\AiExport\Providers\ChatGptProvider;
use Opcodes\LogViewer\Services\AiExport\Providers\ClaudeProvider;

class AiExportService
{
    protected Collection $providers;
    protected bool $isCopyAsMarkdown = false;
    protected array $sanitizePatterns;

    public function __construct()
    {
        $this->providers = collect();
        $this->sanitizePatterns = config('log-viewer.ai_export.sanitize_patterns', []);

        $this->registerDefaultProviders();
    }

    protected function registerDefaultProviders(): void
    {
        $this->registerProvider('chatgpt', new ChatGptProvider);
        $this->registerProvider('claude', new ClaudeProvider);
    }

    public function setCopyAsMarkdown(bool $isCopyAsMarkdown): void
    {
        $this->isCopyAsMarkdown = $isCopyAsMarkdown;
    }

    public function registerProvider(string $key, AiProviderInterface $provider): void
    {
        if ($provider->isEnabled()) {
            $this->providers->put($key, $provider);
        }
    }

    public function getProviders(): Collection
    {
        return $this->providers;
    }

    public function getProvider(string $key): ?AiProviderInterface
    {
        return $this->providers->get($key);
    }

    public function formatLogForAi(array $log, ?LogFile $logFile = null): string
    {
        $markdown = $this->generateMarkdownHeader($log, $logFile);
        $markdown .= $this->generateErrorDetails($log);
        $markdown .= $this->generateStackTrace($log);
        $markdown .= $this->generateContext($log);
        $markdown .= $this->generateAdditionalInfo();
        $markdown .= $this->generateAiPrompt();

        return $this->sanitizeContent($markdown);
    }

    protected function generateMarkdownHeader(array $log, ?LogFile $logFile): string
    {
        $appName = config('app.name', 'Laravel Application');
        $environment = config('app.env', 'production');
        $filePath = $logFile ? $logFile->path : 'Unknown';
        $timestamp = $log['datetime'] ?? date('Y-m-d H:i:s');

        return <<<MARKDOWN
## Log Error Analysis Request

**Application**: {$appName}
**Environment**: {$environment}
**Log File**: {$filePath}
**Timestamp**: {$timestamp}

---

MARKDOWN;
    }

    protected function generateErrorDetails(array $log): string
    {
        $level = strtoupper($log['level_name'] ?? 'ERROR');
        $message = $log['message'] ?? 'No message available';

        $exceptionClass = $this->extractExceptionClass($log['full_text'] ?? '');

        $markdown = <<<MARKDOWN
### Error Details

**Level**: {$level}
**Message**: {$message}

MARKDOWN;

        if ($exceptionClass) {
            $markdown .= "**Exception Class**: {$exceptionClass}\n";
        }

        $markdown .= "\n";

        return $markdown;
    }

    protected function generateStackTrace(array $log): string
    {
        $fullText = $log['full_text'] ?? '';

        if (empty($fullText)) {
            return '';
        }

        $stackTrace = $this->isCopyAsMarkdown
            ? $fullText
            : $this->truncateStackTrace($fullText);

        return <<<MARKDOWN
### Stack Trace

```
{$stackTrace}
```

MARKDOWN;
    }

    private function truncateStackTrace(string $text): string
    {
        $maxLines = (int) config('log-viewer.ai_export.max_context_lines', 30);
        $lines = explode("\n", rtrim($text, "\n"));

        if (count($lines) > $maxLines) {
            $lines = array_slice($lines, 0, $maxLines);
            $lines[] = '... [Stack trace truncated]';
        }

        return implode("\n", $lines);
    }

    protected function generateContext(array $log): string
    {
        $context = $log['context'] ?? [];

        if (empty($context)) {
            return '';
        }

        // Sanitize context before including
        $sanitizedContext = $this->sanitizeContext($context);
        $jsonContext = json_encode($sanitizedContext, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        return <<<MARKDOWN
### Context

```json
{$jsonContext}
```

MARKDOWN;
    }

    protected function generateAdditionalInfo(): string
    {
        $phpVersion = PHP_VERSION;
        $laravelVersion = app()->version();
        $logViewerVersion = LogViewer::version();

        return <<<MARKDOWN
### Additional Information

- **PHP Version**: {$phpVersion}
- **Laravel Version**: {$laravelVersion}
- **Log Viewer Version**: {$logViewerVersion}

MARKDOWN;
    }

    /**
     * Gerar prompt para IA
     */
    protected function generateAiPrompt(): string
    {
        return <<<'MARKDOWN'
---

### Request

Please analyze this Laravel error and provide:

1. **Root Cause Analysis**: What is causing this error?
2. **Immediate Fix**: Step-by-step solution to resolve the error
3. **Code Examples**: Provide corrected code snippets if applicable
4. **Best Practices**: How to prevent this error in the future
5. **Additional Considerations**: Any security, performance, or architectural concerns

Please format your response with clear sections and include code examples where relevant.
MARKDOWN;
    }

    protected function extractExceptionClass(string $text): ?string
    {
        if (preg_match('/^(\w+\\\\)*\w+Exception/m', $text, $matches)) {
            return $matches[0];
        }

        return null;
    }

    protected function sanitizeContent(string $content): string
    {
        foreach ($this->sanitizePatterns as $pattern => $replacement) {
            $content = preg_replace($pattern, $replacement, $content);
        }

        // Standard safety standards
        $defaultPatterns = [
            // Remove tokens JWT
            '/Bearer\s+[A-Za-z0-9\-_]+\.[A-Za-z0-9\-_]+\.[A-Za-z0-9\-_]+/i' => 'Bearer [REDACTED_JWT]',
            // Remove API keys genéricas
            '/api[_\-]?key[\s]*[=:]\s*["\']?[\w\-]+["\']?/i' => 'api_key=[REDACTED]',
            // Remove passwords
            '/password[\s]*[=:]\s*["\']?[^"\'\s]+["\']?/i' => 'password=[REDACTED]',
            // Remove generic tokens
            '/token[\s]*[=:]\s*["\']?[\w\-]+["\']?/i' => 'token=[REDACTED]',
            // Remove secrets
            '/secret[\s]*[=:]\s*["\']?[\w\-]+["\']?/i' => 'secret=[REDACTED]',
            // Remove credit cards
            '/\b\d{4}[\s\-]?\d{4}[\s\-]?\d{4}[\s\-]?\d{4}\b/' => '[REDACTED_CARD]',
            // Remove Document
            '/\b\d{3}\.\d{3}\.\d{3}-\d{2}\b/' => '[REDACTED_DOCUMENT]',
        ];

        foreach ($defaultPatterns as $pattern => $replacement) {
            $content = preg_replace($pattern, $replacement, $content);
        }

        return $content;
    }

    protected function sanitizeContext(array $context): array
    {
        $sensitiveKeys = [
            'password', 'pwd', 'pass', 'secret', 'token', 'api_key',
            'apikey', 'access_token', 'refresh_token', 'private_key',
            'credit_card', 'card_number', 'cvv', 'cpf', 'ssn',
        ];

        array_walk_recursive($context, function (&$value, $key) use ($sensitiveKeys) {
            foreach ($sensitiveKeys as $sensitiveKey) {
                if (stripos($key, $sensitiveKey) !== false) {
                    $value = '[REDACTED]';
                    break;
                }
            }
        });

        return $context;
    }

    public function generateProviderUrl(string $providerKey, array $log, ?LogFile $logFile = null): ?string
    {
        $provider = $this->getProvider($providerKey);

        if (! $provider) {
            return null;
        }

        $markdown = $this->formatLogForAi($log, $logFile);

        $provider->setCopyAsMarkdown(true);

        return $provider->generateUrl($markdown);
    }
}
