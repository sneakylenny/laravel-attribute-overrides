<?php

namespace SneakyLenny\SourcedAttributes;

use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Database\Eloquent\CastsInboundAttributes;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use InvalidArgumentException;

class SourcedAttributes
{
    /**
     * @var array<class-string<Model>, bool>
     */
    protected array $originClassHasAutoSync = [];

    /**
     * @var array<int, string>
     */
    protected array $builtInCastTypes = [
        'array',
        'bool',
        'boolean',
        'collection',
        'custom_datetime',
        'date',
        'datetime',
        'decimal',
        'double',
        'encrypted',
        'encrypted:array',
        'encrypted:collection',
        'encrypted:json',
        'encrypted:object',
        'float',
        'hashed',
        'immutable_date',
        'immutable_datetime',
        'immutable_custom_datetime',
        'int',
        'integer',
        'json',
        'object',
        'real',
        'string',
        'timestamp',
    ];

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

    public function autoSyncEnabled(): bool
    {
        return (bool) config('sourced-attributes.auto_sync.enabled', true);
    }

    public function defaultAutoSync(): bool
    {
        return (bool) config('sourced-attributes.auto_sync.default', false);
    }

    public function autoSyncQueued(): bool
    {
        return (bool) config('sourced-attributes.auto_sync.queued', false);
    }

    public function shouldSyncOriginClass(string $originClass): bool
    {
        if (! $this->autoSyncEnabled()) {
            return false;
        }

        if (array_key_exists($originClass, $this->originClassHasAutoSync)) {
            return $this->originClassHasAutoSync[$originClass];
        }

        /** @var class-string<Model> $modelClass */
        $modelClass = $this->modelClass();

        $hasAny = $modelClass::query()
            ->where('origin_type', $originClass)
            ->where('auto_sync', true)
            ->exists();

        $this->originClassHasAutoSync[$originClass] = $hasAny;

        return $hasAny;
    }

    public function markOriginClassHasAutoSync(string $originClass): void
    {
        $this->originClassHasAutoSync[$originClass] = true;
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

    public function normalizeCast(mixed $cast): ?string
    {
        if ($cast === null) {
            return null;
        }

        if (! is_string($cast)) {
            throw new InvalidArgumentException('Sourced attribute cast must be a string or null.');
        }

        $cast = trim($cast);

        if ($cast === '') {
            return null;
        }

        $this->ensureValidCast($cast);

        return $cast;
    }

    public function normalizeMeta(mixed $meta): ?array
    {
        if ($meta === null) {
            return null;
        }

        if (! is_array($meta)) {
            throw new InvalidArgumentException('Sourced attribute meta must be an array or null.');
        }

        return $meta;
    }

    public function ensureValidCast(string $cast): void
    {
        $normalized = strtolower($cast);

        if (in_array($normalized, $this->builtInCastTypes, true)) {
            return;
        }

        $baseCast = explode(':', $cast, 2)[0];

        if (! class_exists($baseCast)) {
            throw new InvalidArgumentException("Invalid sourced attribute cast [{$cast}].");
        }

        if (
            is_subclass_of($baseCast, CastsAttributes::class)
            || is_subclass_of($baseCast, CastsInboundAttributes::class)
            || is_subclass_of($baseCast, Castable::class)
        ) {
            return;
        }

        throw new InvalidArgumentException("Sourced attribute cast class [{$baseCast}] is not a valid Laravel cast.");
    }

    public function applyCast(Model $model, string $attribute, mixed $value, ?string $cast): mixed
    {
        if ($cast === null || $cast === '') {
            return $value;
        }

        $probe = new class extends Model
        {
            protected $guarded = [];

            public $timestamps = false;
        };

        $probe->mergeCasts([$attribute => $cast]);
        $probe->setRawAttributes([$attribute => $value], true);

        return $probe->getAttribute($attribute);
    }

    public function syncFromOrigin(Model $origin): int
    {
        if (! $origin->exists || ! $this->autoSyncEnabled() || ! $this->shouldSyncOriginClass($origin::class)) {
            return 0;
        }

        /** @var class-string<Model> $modelClass */
        $modelClass = $this->modelClass();

        /** @var Collection<int, Model> $records */
        $records = $modelClass::query()
            ->where('origin_type', $origin::class)
            ->where('origin_id', $origin->getKey())
            ->where('auto_sync', true)
            ->get();

        $updated = 0;

        foreach ($records as $record) {
            $freshValue = data_get($origin, $record->origin_attribute);

            if ($freshValue !== $record->value) {
                $record->update(['value' => $freshValue]);
                $updated++;
            }
        }

        return $updated;
    }

    /**
     * @return array<int, string>
     */
    public function allowedOperators(): array
    {
        return ['=', '!=', '<>', '>', '>=', '<', '<=', 'like', 'not like'];
    }
}
