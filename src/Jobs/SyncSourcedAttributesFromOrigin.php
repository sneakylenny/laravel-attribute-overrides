<?php

namespace SneakyLenny\SourcedAttributes\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use SneakyLenny\SourcedAttributes\SourcedAttributes;

class SyncSourcedAttributesFromOrigin implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public Model $origin) {}

    public function handle(SourcedAttributes $sourcedAttributes): void
    {
        $sourcedAttributes->syncFromOrigin($this->origin);
    }
}
