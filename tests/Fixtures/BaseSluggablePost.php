<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use NunoMaduro\LaravelSluggable\Attributes\Sluggable;

#[Sluggable(from: 'name')]
class BaseSluggablePost extends Model
{
    protected $table = 'sluggable_posts';

    protected $guarded = [];
}
