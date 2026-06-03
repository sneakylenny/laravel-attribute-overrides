<?php

namespace SneakyLenny\SourcedAttributes\Tests\Support\Models;

use Illuminate\Database\Eloquent\Model;
use SneakyLenny\SourcedAttributes\Traits\HasSourcedAttributes;

class TestPersonOverridesDisabled extends Model
{
    use HasSourcedAttributes;

    protected bool $overridesDefault = false;

    protected $table = 'test_people';

    protected $guarded = [];

    protected $casts = [
        'data' => 'array',
    ];
}
