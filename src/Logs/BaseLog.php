<?php

namespace Opcodes\LogViewer\Logs;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Opcodes\LogViewer\LogLevels\LaravelLogLevel;
use Opcodes\LogViewer\LogLevels\LevelInterface;

abstract class BaseLog
{
    public static string $regex = '/^(?P<datetime>[\d+\/ :]+) \[(?P<level>.+)\] (?P<message>.+)$/';

    public static array $columns = [
        ['label' => 'Datetime', 'data_path' => 'datetime'],
        ['label' => 'Severity', 'data_path' => 'level'],
        ['label' => 'Message', 'data_path' => 'message'],
    ];

    /** @var string|null The original full text of the log */
    protected ?string $text;

    public ?CarbonInterface $datetime;

    public ?string $level;

    public ?string $message;

    public array $context = [];

    public array $extra = [];

    public ?string $fileIdentifier;

    public ?int $filePosition;

    public ?int $index;

    public function __construct(string $text, string $fileIdentifier = null, int $filePosition = null, int $index = null)
    {
        $this->text = rtrim($text);
        $this->fileIdentifier = $fileIdentifier;
        $this->filePosition = $filePosition;
        $this->index = $index;

        $this->parseText();
    }

    public static function matches(string $text, int &$timestamp = null, string &$level = null): bool
    {
        return preg_match(static::$regex, $text) === 1;
    }

    public static function levelClass(): string
    {
        return static::$levelClass ?? LaravelLogLevel::class;
    }

    protected function parseText(): void
    {
        $matches = [];
        preg_match(static::$regex, $this->text, $matches);

        $this->datetime = Carbon::parse($matches['datetime'] ?? null);
        $this->level = $matches['level'] ?? null;
        $this->message = $matches['message'] ?? null;
        $this->context = [];

        unset($matches);
    }

    public function getTimestamp(): int
    {
        return $this->datetime?->getTimestamp() ?? 0;
    }

    public function getLevel(): LevelInterface
    {
        $levelClass = static::levelClass();

        /** @noinspection PhpUndefinedMethodInspection */
        return $levelClass::from($this->level);
    }

    public function getOriginalText(): ?string
    {
        return $this->text;
    }

    public function url(): string
    {
        return route('log-viewer.index', ['file' => $this->fileIdentifier, 'query' => 'log-index:'.$this->index]);
    }
}
