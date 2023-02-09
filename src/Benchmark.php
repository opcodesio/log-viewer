<?php

namespace Arukompas\BetterLogViewer;

class Benchmark
{
    /**
     * The list of various tests benchmarked
     *
     * @var array
     */
    public static array $tests = [];

    /**
     * Begin a test instance with a given name
     *
     * @param  string  $name
     * @return void
     */
    public static function time(string $name): void
    {
        if (! array_key_exists($name, static::$tests)) {
            static::$tests[$name] = [
                'current' => [
                    'start' => null,
                    'end' => null,
                ],
                'history' => [],
            ];
        }

        static::$tests[$name]['current'] = [
            'start' => microtime(true),
        ];
    }

    /**
     * An alias for 'time'
     *
     * @param  string  $name
     * @return void
     */
    public static function start(string $name): void
    {
        static::time($name);
    }

    /**
     * End a test instance for a given name and return the latest test duration.
     *
     * @param  string  $name
     * @return float
     */
    public static function endTime(string $name): float
    {
        static::$tests[$name]['current']['end'] = microtime(true);

        $current = static::$tests[$name]['current'];

        static::$tests[$name]['history'][] = array_merge($current, [
            'duration' => $current['end'] - $current['start'],
        ]);

        return $current['end'] - $current['start'];
    }

    /**
     * An alias for 'endTime'
     *
     * @param  string  $name
     * @return float
     */
    public static function end(string $name): float
    {
        return static::endTime($name);
    }

    /**
     * Get the total runtime for a given test category
     *
     * @param  string  $name
     * @return float
     */
    public static function getTotal(string $name): float
    {
        $history = static::$tests[$name]['history'];

        return array_reduce($history, function ($sum, $historyEntry) {
            return $sum + $historyEntry['duration'];
        }, 0);
    }

    /**
     * Get the average runtime for a given test category
     *
     * @param  string  $name
     * @return float
     */
    public static function getAverage(string $name): float
    {
        return static::getTotal($name) / count(static::$tests[$name]['history']);
    }

    /**
     * Dump the status about all tests
     */
    public static function dump(string $name = null): void
    {
        if ($name) {
            dump(self::results($name));

            return;
        }

        foreach (self::results() as $result) {
            dump($result);
        }
    }

    /**
     * Get the results of the benchmark.
     */
    public static function results(string $name = null): array
    {
        if ($name) {
            $testData = static::$tests[$name];

            return [
                'name' => $name,
                'number_of_runs' => count($testData['history']),
                'total' => number_format(static::getTotal($name), 6),
                'average' => number_format(static::getAverage($name), 6),
            ];
        }

        $results = [];

        foreach (static::$tests as $testName => $testData) {
            $results[] = [
                'name' => $testName,
                'number_of_runs' => count($testData['history']),
                'total' => number_format(static::getTotal($testName), 6),
                'average' => number_format(static::getAverage($testName), 6),
            ];
        }

        return $results;
    }
}
