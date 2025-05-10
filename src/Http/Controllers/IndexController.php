<?php

namespace Opcodes\LogViewer\Http\Controllers;

use Opcodes\LogViewer\Facades\LogViewer;
use Opcodes\LogViewer\LogFolder;
use Opcodes\LogViewer\Utils\Utils;

class IndexController
{
    public function __invoke()
    {
        if (config('log-viewer.api_only')) {
            abort(404);
        }

        return view(LogViewer::getViewLayout(), [
            'logViewerScriptVariables' => [
                'headers' => (object) [],
                'assets_outdated' => ! LogViewer::assetsAreCurrent(),
                'version' => LogViewer::version(),
                'app_name' => config('app.name'),
                'path' => config('log-viewer.route_path'),
                'back_to_system_url' => config('log-viewer.back_to_system_url'),
                'back_to_system_label' => config('log-viewer.back_to_system_label'),
                'max_log_size_formatted' => Utils::bytesForHumans(LogViewer::maxLogSize()),
                'show_support_link' => config('log-viewer.show_support_link', true),

                'supports_hosts' => LogViewer::supportsHostsFeature(),
                'hosts' => LogViewer::getHosts(),
                'per_page_options' => config('log-viewer.per_page_options') ?? [10, 25, 50, 100, 250, 500],
                'defaults' => [
                    'use_local_storage' => config('log-viewer.defaults.use_local_storage'),
                    'log_sorting_order' => config('log-viewer.defaults.log_sorting_order'),
                    'per_page' => config('log-viewer.defaults.per_page'),
                    'theme' => config('log-viewer.defaults.theme'),
                    'shorter_stack_traces' => config('log-viewer.defaults.shorter_stack_traces'),
                ],
                'root_folder_prefix' => LogFolder::rootPrefix(),
            ],
        ]);
    }
}
