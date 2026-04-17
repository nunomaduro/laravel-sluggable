<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use NunoMaduro\LaravelSluggable\Attributes\Sluggable;

#[Sluggable(unique: false)]
final class SluggableNonUniquePost extends Model
{
    #[\Override]
    protected $table = 'sluggable_posts';

    #[\Override]
    protected $guarded = [];
}
