<?php

namespace Opcodes\LogViewer\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Opcodes\LogViewer\Facades\LogViewer;
use Opcodes\LogViewer\LogFileCollection;

class SummaryLogCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'log-viewer:summary
        {--l|logs=1000 : Number of logs to summarize }';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates a summary of the logs';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $number = $this->option('logs');

        static::deleteSummaryFile();
        $this->comment('Deleted error log summary file');
        $this->comment('Generating error log summary file');
        static::generateSummaryFile($number);
        $this->comment('Done');
    }

    protected static function deleteSummaryFile(): void
    {
        Storage::disk('logs')
            ->delete('summary.log');
    }

    protected static function generateSummaryFile(int $number): void
    {
        $summary = [];

        /** @var LogFileCollection $files */
        $files = LogViewer::getFiles();

        foreach ($files as $file) {
            if (! Str::endsWith($file->name, 'laravel.log')) {
                continue;
            }

            $logs = collect($file->logs()->get())
                ->reverse()
                ->take($number);

            foreach ($logs as $log) {
                $level = $log->level;
                $message = $log->message;
                $context = $log->context;
                $env = $log->extra['environment'];
                $ts = $log->datetime->format('Y-m-d H:i:s');

                if (! isset($summary[trim($message)])) {
                    $summary[$message] = [
                        'first' => $ts,
                        'last' => $ts,
                        'count' => 1,
                        'level' => $level,
                        'env' => $env,
                        'message' => $message,
                        'context' => $context,
                    ];
                } else {
                    $summary[trim($message)]['count']++;
                    $summary[trim($message)]['last'] = max(
                        $ts,
                        $summary[trim($message)]['last']
                    );
                    $summary[trim($message)]['first'] = min(
                        $ts,
                        $summary[trim($message)]['first']
                    );
                }
            }
        }

        $lines = array_map(function (array $data) {
            return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }, array_values($summary));

        $uniqueLines = [];
        foreach ($lines as $json) {
            $uniqueLines[$json] = true;
        }

        Storage::disk('logs')
            ->put('summary.log', implode("\n", array_reverse(array_keys($uniqueLines))));
    }
}
