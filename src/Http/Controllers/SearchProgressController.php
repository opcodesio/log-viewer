<?php

namespace Opcodes\LogViewer\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Opcodes\LogViewer\Facades\LogViewer;
use Opcodes\LogViewer\MultipleLogReader;

class SearchProgressController
{
    public function __invoke(Request $request): JsonResponse
    {
        $query = $request->query('query', '');
        $logQuery = null;
        $requiresScan = false;
        $percentScanned = 100;

        if (! empty($query)) {
            $logQuery = new MultipleLogReader(LogViewer::getFiles());
            $logQuery->search($query);

            $logQuery->scan(LogViewer::lazyScanChunkSize());

            $requiresScan = $logQuery->requiresScan();
            $percentScanned = $logQuery->percentScanned();
        }

        return response()->json([
            'hasMoreResults' => $requiresScan,
            'percentScanned' => $percentScanned,
        ]);
    }
}
