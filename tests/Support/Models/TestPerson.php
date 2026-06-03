<?php

namespace SneakyLenny\SourcedAttributes\Tests\Support\Models;

use Illuminate\Database\Eloquent\Model;
use SneakyLenny\SourcedAttributes\Traits\HasSourcedAttributes;

class TestPerson extends Model
{
    use HasSourcedAttributes;

    protected $table = 'test_people';

    protected $guarded = [];

    protected $casts = [
        'data' => 'array',
    ];
}
