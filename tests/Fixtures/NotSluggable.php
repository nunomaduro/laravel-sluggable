<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;

final class NotSluggable extends Model
{
    #[\Override]
    protected $table = 'not_sluggable';

    #[\Override]
    protected $guarded = [];
}
