<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use NunoMaduro\LaravelSluggable\Attributes\Sluggable;

#[Sluggable(scope: 'team_id')]
final class SluggableScopedPost extends Model
{
    #[\Override]
    protected $table = 'sluggable_scoped_posts';

    #[\Override]
    protected $guarded = [];
}
