<?php

namespace Opcodes\LogViewer;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface LogReaderInterface
{
    public static function instance(LogFile $file): static;
    public static function clearInstance(LogFile $file): void;
    public static function clearInstances(): void;

    // Search/querying
    public function search(string $query = null): static;
    public function skip(int $number): static;
    public function limit(int $number): static;

    // Direction
    public function reverse(): static;
    public function forward(): static;
    public function setDirection(string $direction = null): static;

    // Controlling which severity levels are shown
    public function supportsLevels(): bool;
    public function getLevelCounts(): array;
    public function only($levels = null): static;
    public function setLevels($levels = null): static;
    public function allLevels(): static;
    public function except($levels = null): static;
    public function exceptLevels($levels = null): static;
    public static function getDefaultLevels(): array;

    // Retrieving actual logs
    public function get(int $limit = null): array;
    public function next(): Log|HttpLog|null;   // TODO: create a base log!!

    /** @return LengthAwarePaginator<Log|HttpLog> */
    public function paginate(int $perPage = 25, int $page = null);
    public function total(): int;
    public function count(): int;

    // Functional
    public function reset(): static;

    // We should decouple scanning from the LogReader
    public function scan(int $maxBytesToScan = null, bool $force = false): static;
    public function numberOfNewBytes(): int;
    public function requiresScan(): bool;
    public function percentScanned(): int;
}
