<?php

namespace SneakyLenny\SourcedAttributes;

use Illuminate\Database\Eloquent\Model;
use SneakyLenny\SourcedAttributes\Models\SourcedAttribute;

class PendingSourcedAttribute
{
    protected ?int $priorityOverride = null;

    public function __construct(
        protected Model $target,
        protected string $attribute,
    ) {
        app(SourcedAttributes::class)->ensurePersisted($this->target);
        app(SourcedAttributes::class)->ensureAttributeName($this->attribute);
    }

    public function priority(int $priority): static
    {
        $this->priorityOverride = $priority;

        return $this;
    }

    public function from(Model $origin, ?string $originAttribute = null, array $options = []): SourcedAttribute
    {
        app(SourcedAttributes::class)->ensurePersisted($origin);
        $service = app(SourcedAttributes::class);
        $originAttribute ??= $this->attribute;
        $cast = $service->normalizeCast($options['cast'] ?? null);
        $meta = $service->normalizeMeta($options['meta'] ?? null);
        $autoSync = (bool) ($options['auto_sync'] ?? $service->defaultAutoSync());

        if ($autoSync) {
            $service->markOriginClassHasAutoSync($origin::class);
        }

        $record = $this->target->sourcedAttributes()->updateOrCreate(
            [
                'sourceable_attribute' => $this->attribute,
                'origin_type' => $origin::class,
                'origin_id' => $origin->getKey(),
                'origin_attribute' => $originAttribute,
            ],
            [
                'value' => data_get($origin, $originAttribute),
                'cast' => $cast,
                'meta' => $meta,
                'auto_sync' => $autoSync,
                'priority' => $this->resolvePriority($options, $service),
            ]
        );

        return $record;
    }

    public function as(mixed $value, array $options = []): SourcedAttribute
    {
        $service = app(SourcedAttributes::class);
        $cast = $service->normalizeCast($options['cast'] ?? null);
        $meta = $service->normalizeMeta($options['meta'] ?? null);

        return $this->target->sourcedAttributes()->updateOrCreate(
            [
                'sourceable_attribute' => $this->attribute,
                'origin_type' => null,
                'origin_id' => null,
                'origin_attribute' => null,
            ],
            [
                'value' => $value,
                'cast' => $cast,
                'meta' => $meta,
                'priority' => $this->resolvePriority($options, $service),
            ]
        );
    }

    protected function resolvePriority(array $options, SourcedAttributes $service): int
    {
        if (array_key_exists('priority', $options)) {
            return (int) $options['priority'];
        }

        if ($this->priorityOverride !== null) {
            return $this->priorityOverride;
        }

        return $service->defaultPriority();
    }
}
