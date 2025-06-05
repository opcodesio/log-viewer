<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Opcodes\LogViewer\Facades\LogViewer;
use Opcodes\LogViewer\LogFileCollection;

class LogSummaryCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'log:summary';

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
        static::deleteSummaryFile();
        $this->comment('Deleted error log summary file');
        $this->comment('Generating error log summary file');
        static::generateSummaryFile();
        $this->comment('Done');
    }

    protected static function deleteSummaryFile(): void
    {
        Storage::disk('logs')
            ->delete('log-summary.log');
    }

    protected static function generateSummaryFile(): void
    {
        $summary = [];

        /** @var LogFileCollection $files */
        $files = LogViewer::getFiles();

        foreach ($files as $file) {
            if ($file->name === 'log-summary.log') {
                continue;
            }

            $paginator = $file
                ->logs()
                ->paginate(1000);

            foreach ($paginator->items() as $log) {
                $level = strtoupper($log->level);
                $message = $log->getOriginalText();
                $context = $log->context;
                $env = $log->extra['environment'];
                $ts = $log->datetime->format('Y-m-d H:i:s');

                if (! isset($summary[$message])) {
                    $summary[$message] = [
                        'first' => $ts,
                        'last'  => $ts,
                        'count' => 1,
                        'level' => $level,
                        'context' => $context,
                        'env' => $env,
                    ];
                } else {
                    $summary[$message]['count']++;
                    $summary[$message]['last'] = max(
                        $ts,
                        $summary[$message]['last']
                    );
                    $summary[$message]['first'] = min(
                        $ts,
                        $summary[$message]['first']
                    );
                }
            }
        }

        $lines = [];
        foreach ($summary as $message => $data) {
            $lines[] = trim(sprintf(
                '[%s] - [%s] %s.%s: %d | %s %s',
                $data['first'],
                $data['last'],
                $data['env'],
                $data['level'],
                $data['count'],
                $message,
                count($data['context']) > 0 ? Str::replace('\\n', "\n", '\n' . json_encode($data['context'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) : ''
            ));

        }

        Storage::disk('logs')
            ->put('log-summary.log', implode("\n", $lines));
    }
}
