<?php

namespace Opcodes\LogViewer\Logs;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Opcodes\LogViewer\Facades\LogViewer;
use Opcodes\LogViewer\LogLevels\LaravelLogLevel;
use Opcodes\LogViewer\LogLevels\LevelInterface;

class Log
{
    /** @var string The name of the log type, visible to humans in the UI. */
    public static string $name = 'Log';

    /** @var string|LevelInterface The class which defines the severities found on these logs. Should implement the \Opcodes\LogViewer\LogLevels\LevelInterface interface */
    public static string $levelClass = LaravelLogLevel::class;

    /** @var string The regular expression used to extract various data points of the log */
    public static string $regex = '/^(?P<datetime>[\d\-+ :]+) \[(?P<level>.+)\] (?P<message>.+)$/';

    /** @var string The regular expression group key, which contains the datetime */
    public static string $regexDatetimeKey = 'datetime';

    /** @var string The regular expression group key, which contains the level/severity of the log */
    public static string $regexLevelKey = 'level';

    /** @var string The regular expression group key, which contains the message */
    public static string $regexMessageKey = 'message';

    /** @var array|\string[][] The columns displayed on the frontend, and which data they should display */
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

    public function __construct(string $text, ?string $fileIdentifier = null, ?int $filePosition = null, ?int $index = null)
    {
        $this->text = rtrim($text);
        $this->fileIdentifier = $fileIdentifier;
        $this->filePosition = $filePosition;
        $this->index = $index;

        $matches = [];
        $this->parseText($matches);
        $this->fillMatches($matches);

        unset($matches);
    }

    public static function matches(string $text, ?int &$timestamp = null, ?string &$level = null): bool
    {
        $matches = [];
        $result = preg_match(static::$regex, $text, $matches) === 1;

        if ($result) {
            try {
                $datetime = static::parseDateTime($matches[static::$regexDatetimeKey] ?? null);
                $timestamp = $datetime?->timestamp;

                $level = $matches[static::$regexLevelKey] ?? '';
            } catch (\Exception $exception) {
                // not a valid datetime, so we can't match this log. Perhaps it's a different but similar log type.
                return false;
            }
        }

        return $result;
    }

    public static function parseDatetime(?string $datetime): ?CarbonInterface
    {
        return $datetime ? Carbon::parse($datetime) : null;
    }

    /**
     * @return string|LevelInterface
     */
    public static function levelClass(): string
    {
        $class = static::$levelClass ?? LaravelLogLevel::class;

        if (! is_subclass_of($class, LevelInterface::class)) {
            throw new \LogicException(sprintf('The class %s must implement the %s interface', $class, LevelInterface::class));
        }

        return $class;
    }

    protected function parseText(array &$matches = []): void
    {
        preg_match(static::$regex, $this->text, $matches);
    }

    protected function fillMatches(array $matches = []): void
    {
        $datetime = static::parseDateTime($matches[static::$regexDatetimeKey] ?? null);
        $this->datetime = $datetime?->setTimezone(LogViewer::timezone());

        $this->level = $matches[static::$regexLevelKey] ?? null;
        $this->message = trim($matches[static::$regexMessageKey] ?? '');
        $this->context = [];
    }

    public function getTimestamp(): int
    {
        return $this->datetime?->getTimestamp() ?? 0;
    }

    public function getLevel(): LevelInterface
    {
        $levelClass = static::levelClass();

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
