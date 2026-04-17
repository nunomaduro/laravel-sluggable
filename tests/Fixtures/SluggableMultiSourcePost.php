<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use NunoMaduro\LaravelSluggable\Attributes\Sluggable;

#[Sluggable(from: ['first_name', 'last_name'])]
final class SluggableMultiSourcePost extends Model
{
    #[\Override]
    protected $table = 'sluggable_multi_source_posts';

    #[\Override]
    protected $guarded = [];
}
