<?php

namespace Opcodes\LogViewer\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Opcodes\LogViewer\Facades\LogViewer;
use Opcodes\LogViewer\Services\AiExport\AiExportService;

class AiExportController
{
    protected AiExportService $aiExportService;

    public function __construct(AiExportService $aiExportService)
    {
        $this->aiExportService = $aiExportService;
    }

    public function providers(): JsonResponse
    {
        if (! $this->isFeatureEnabled()) {
            return response()->json(['error' => 'AI Export feature is disabled'], 403);
        }

        $providers = Cache::remember('ai-export:providers', 3600, function () {
            return $this->aiExportService->getProviders()->map(function ($provider, $key) {
                return [
                    'key' => $key,
                    'name' => $provider->getName(),
                    'icon' => $provider->getIcon(),
                    'enabled' => $provider->isEnabled(),
                ];
            })->values();
        });

        return response()->json([
            'providers' => $providers,
        ])->header('Cache-Control', 'public, max-age=3600'); // Add HTTP cache too
    }

    public function export(Request $request): JsonResponse
    {
        if (! $this->isFeatureEnabled()) {
            return response()->json(['error' => 'AI Export feature is disabled'], 403);
        }

        // Rate limiting to prevent abuse
        $key = 'ai-export:'.($request->user()->id ?? $request->ip());
        if (! RateLimiter::attempt($key, 10, function () {})) {
            return response()->json(['error' => 'Too many requests. Please try again later.'], 429);
        }

        $request->validate([
            'provider' => 'required|string',
            'log_index' => 'required|integer',
            'file_identifier' => 'nullable|string',
        ]);

        $providerKey = $request->input('provider');
        $logIndex = $request->input('log_index');
        $fileIdentifier = $request->input('file_identifier');

        $provider = $this->aiExportService->getProvider($providerKey);
        if (! $provider) {
            return response()->json(['error' => 'Invalid provider'], 400);
        }

        try {
            $log = $this->getLogByIndex($logIndex, $fileIdentifier);

            if (! $log) {
                return response()->json(['error' => 'Log not found'], 404);
            }

            $logFile = null;
            if ($fileIdentifier) {
                $logFile = LogViewer::getFile($fileIdentifier);
            }

            $url = $this->aiExportService->generateProviderUrl($providerKey, $log, $logFile);

            $cacheKey = "ai-export:{$providerKey}:{$logIndex}:{$fileIdentifier}";
            $markdown = Cache::remember($cacheKey, 300, function () use ($log, $logFile) {
                return $this->aiExportService->formatLogForAi($log, $logFile);
            });

            return response()->json([
                'url' => $url,
                'markdown' => $markdown,
                'provider' => [
                    'key' => $providerKey,
                    'name' => $provider->getName(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to export log: '.$e->getMessage(),
            ], 500);
        }
    }

    public function copyAsMarkdown(Request $request): JsonResponse
    {
        if (! $this->isFeatureEnabled()) {
            return response()->json(['error' => 'AI Export feature is disabled'], 403);
        }

        $request->validate([
            'log_index' => 'required|integer',
            'file_identifier' => 'nullable|string',
        ]);

        $logIndex = $request->input('log_index');
        $fileIdentifier = $request->input('file_identifier');

        try {
            $log = $this->getLogByIndex($logIndex, $fileIdentifier);

            if (! $log) {
                return response()->json(['error' => 'Log not found'], 404);
            }

            $logFile = null;
            if ($fileIdentifier) {
                $logFile = LogViewer::getFile($fileIdentifier);
            }

            $this->aiExportService->setCopyAsMarkdown(true);
            $markdown = $this->aiExportService->formatLogForAi($log, $logFile);

            return response()->json([
                'markdown' => $markdown,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to generate markdown: '.$e->getMessage(),
            ], 500);
        }
    }

    protected function getLogByIndex(int $index, ?string $fileIdentifier): ?array
    {
        if (! $fileIdentifier) {
            return null;
        }

        try {
            $file = LogViewer::getFile($fileIdentifier);
            if (! $file) {
                return null;
            }

            $logReader = $file->search('log-index:'.$index);

            $paginator = $logReader->paginate(1);

            $log = $paginator->getCollection()->first();

            if (! $log) {
                return null;
            }

            return $this->formatLogEntry($log);
        } catch (\Throwable $e) {
            // Log not found
        }

        return null;
    }

    private function formatLogEntry(object $log): array
    {
        return [
            'level_name' => $log->level ?? 'ERROR',
            'level_class' => $this->getLevelClass($log->level ?? 'ERROR'),
            'message' => $log->message ?? '',
            'datetime' => $log->datetime?->toDateTimeString() ?? date('Y-m-d H:i:s'),
            'full_text' => $log->text ?? '',
            'context' => $log->context ?? [],
            'extra' => $log->extra ?? [],
        ];
    }

    protected function getLevelClass(string $level): string
    {
        return match (strtoupper($level)) {
            'EMERGENCY', 'ALERT', 'CRITICAL', 'ERROR' => 'danger',
            'WARNING' => 'warning',
            'NOTICE', 'INFO' => 'info',
            'DEBUG' => 'secondary',
            default => 'secondary',
        };
    }

    protected function isFeatureEnabled(): bool
    {
        return config('log-viewer.ai_export.enabled', true);
    }
}
