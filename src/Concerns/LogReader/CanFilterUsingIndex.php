<?php

namespace Opcodes\LogViewer\Concerns\LogReader;

use Opcodes\LogViewer\Utils\Utils;

trait CanFilterUsingIndex
{
    protected ?string $query = null;
    protected ?int $onlyShowIndex = null;

    /**
     * Load only the provided log levels
     *
     * @alias setLevels
     *
     * @param  string|array|null  $levels
     */
    public function only($levels = null): static
    {
        return $this->setLevels($levels);
    }

    /**
     * Load only the provided log levels
     *
     * @param  string|array|null  $levels
     */
    public function setLevels($levels = null): static
    {
        $this->index()->forLevels($levels);

        return $this;
    }

    public function allLevels(): static
    {
        return $this->setLevels(null);
    }

    /**
     * Load all log levels except the provided ones.
     *
     * @alias exceptLevels
     *
     * @param  string|array|null  $levels
     */
    public function except($levels = null): static
    {
        return $this->exceptLevels($levels);
    }

    /**
     * Load all log levels except the provided ones.
     *
     * @param  string|array|null  $levels
     */
    public function exceptLevels($levels = null): static
    {
        $this->index()->exceptLevels($levels);

        return $this;
    }

    public function skip(int $number): static
    {
        $this->index()->skip($number);

        return $this;
    }

    public function limit(int $number): static
    {
        $this->index()->limit($number);

        return $this;
    }

    public function search(?string $query = null): static
    {
        return $this->setQuery($query);
    }

    protected function setQuery(?string $query = null): static
    {
        $this->closeFile();

        if (! empty($query) && str_starts_with($query, 'log-index:')) {
            $this->query = null;
            $this->only(null);
            $this->onlyShowIndex = intval(explode(':', $query)[1]);
        } elseif (! empty($query)) {
            $query = '~'.$query.'~iu';

            Utils::validateRegex($query);

            $this->query = $query;
        } else {
            $this->query = null;
        }

        return $this;
    }
}
