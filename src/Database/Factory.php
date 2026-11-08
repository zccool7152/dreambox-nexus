<?php
declare(strict_types=1);

namespace Nexus\Database;

/**
 * Model Data Factory Base
 */
abstract class Factory
{
    protected int $count = 1;

    abstract public function definition(): array;

    public function count(int $count): static
    {
        $clone = clone $this;
        $clone->count = max(1, $count);
        return $clone;
    }

    public function make(array $attributes = []): array
    {
        $records = [];
        for ($i = 0; $i < $this->count; $i++) {
            $records[] = array_merge($this->definition(), $attributes);
        }

        return $this->count === 1 ? $records[0] : $records;
    }
}

