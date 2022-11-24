<?php

namespace Opcodes\LogViewer\Concerns\LogIndex;

use ArrayIterator;
use Opcodes\LogViewer\Direction;

trait CanIterateIndex
{
    protected array $_cachedFlatIndex;

    protected ArrayIterator $_cachedFlatIndexIterator;

    protected string $direction = Direction::Forward;

    public function setDirection(string $direction): self
    {
        $this->direction = $direction === Direction::Backward
            ? Direction::Backward
            : Direction::Forward;

        return $this->reset();
    }

    public function isForward(): bool
    {
        return $this->direction === Direction::Forward;
    }

    public function isBackward(): bool
    {
        return $this->direction === Direction::Backward;
    }

    /** @alias backward */
    public function reverse(): self
    {
        return $this->backward();
    }

    public function backward(): self
    {
        return $this->setDirection(Direction::Backward);
    }

    public function forward(): self
    {
        return $this->setDirection(Direction::Forward);
    }

    public function next(): ?array
    {
        if (! isset($this->_cachedFlatIndex)) {
            $this->_cachedFlatIndex = $this->getFlatIndex();
        }

        if (! isset($this->_cachedFlatIndexIterator)) {
            $this->_cachedFlatIndexIterator = new ArrayIterator($this->_cachedFlatIndex);
        } else {
            $this->_cachedFlatIndexIterator->next();
        }

        if (! $this->_cachedFlatIndexIterator->valid()) {
            return null;
        }

        return [
            $this->_cachedFlatIndexIterator->key(),
            $this->_cachedFlatIndexIterator->current(),
        ];
    }

    public function reset(): self
    {
        unset($this->_cachedFlatIndexIterator);
        unset($this->_cachedFlatIndex);

        return $this;
    }
}
