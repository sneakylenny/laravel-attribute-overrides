<?php

namespace SneakyLenny\SourcedAttributes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use InvalidArgumentException;

class SourcedAttributes
{
    public function table(): string
    {
        return (string) config('sourced-attributes.table', 'sourced_attributes');
    }

    public function modelClass(): string
    {
        return (string) config('sourced-attributes.model', Models\SourcedAttribute::class);
    }

    public function defaultPriority(): int
    {
        return (int) config('sourced-attributes.default_priority', 0);
    }

    public function ensurePersisted(Model $model): void
    {
        if (! $model->exists) {
            throw new InvalidArgumentException('The target model must be persisted before sourcing attributes.');
        }
    }

    public function ensureAttributeName(string $attribute): void
    {
        if (! Str::of($attribute)->match('/^[A-Za-z0-9_]+$/')->isNotEmpty()) {
            throw new InvalidArgumentException("Invalid sourced attribute [{$attribute}].");
        }
    }

    /**
     * @return array<int, string>
     */
    public function allowedOperators(): array
    {
        return ['=', '!=', '<>', '>', '>=', '<', '<=', 'like', 'not like'];
    }
}
