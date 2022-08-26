<?php

namespace Opcodes\LogViewer\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class GenerateDummyLogsCommand extends Command
{
    protected $signature = 'log-viewer:generate-dummy-logs {amount} {--channel=single}';

    protected $description = 'Generate dummy log entries to preview in the Log Viewer';

    protected array $severities = [
        // 'notice',
        'info',
        // 'alert',
        'debug',
        'warning',
        'error',
        // 'critical',
        // 'emergency',
    ];

    public function handle()
    {
        if (app()->environment('production')) {
            $this->error('You should not be generating dummy logs in production. Exiting...');

            return;
        }

        $amount = $this->argument('amount');
        $channel = $this->option('channel');

        $this->info('Generating '.$amount.' logs on the "'.$channel.'" channel.');

        while ($amount > 0) {
            $level = Arr::random($this->severities);

            if ($level === 'error') {
                Log::channel($channel)->error(new \Exception('Example exception being logged'));
            } else {
                Log::channel($channel)->log($level, 'Example log entry for the level '.$level, [
                    'one' => 1,
                    'two' => 'two',
                    'three' => [1, 2, 3],
                ]);
            }

            $amount--;
        }

        $this->info('Done!');
    }
}
