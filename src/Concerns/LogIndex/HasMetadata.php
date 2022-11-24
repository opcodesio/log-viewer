<?php

namespace Opcodes\LogViewer\Concerns\LogIndex;

trait HasMetadata
{
    public function getMetadata(): array
    {
        return [
            'query' => $this->getQuery(),
            'identifier' => $this->identifier,
            'last_scanned_file_position' => $this->lastScannedFilePosition,
            'last_scanned_index' => $this->lastScannedIndex,
            'next_log_index_to_create' => $this->nextLogIndexToCreate,
            'max_chunk_size' => $this->maxChunkSize,
            'current_chunk_index' => $this->getCurrentChunk()->index,
            'chunk_definitions' => $this->chunkDefinitions,
            'current_chunk_definition' => $this->getCurrentChunk()->toArray(),
        ];
    }

    protected function saveMetadata(): void
    {
        $this->saveMetadataToCache();
    }

    protected function loadMetadata(): void
    {
        $data = $this->getMetadataFromCache();

        $this->lastScannedFilePosition = $data['last_scanned_file_position'] ?? 0;
        $this->lastScannedIndex = $data['last_scanned_index'] ?? 0;
        $this->nextLogIndexToCreate = $data['next_log_index_to_create'] ?? 0;
        $this->maxChunkSize = $data['max_chunk_size'] ?? self::DEFAULT_CHUNK_SIZE;
        $this->chunkDefinitions = $data['chunk_definitions'] ?? [];
        $this->currentChunkDefinition = $data['current_chunk_definition'] ?? [];
    }
}
