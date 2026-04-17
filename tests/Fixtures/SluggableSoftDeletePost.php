<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use NunoMaduro\LaravelSluggable\Attributes\Sluggable;

#[Sluggable]
final class SluggableSoftDeletePost extends Model
{
    use SoftDeletes;

    #[\Override]
    protected $table = 'sluggable_soft_delete_posts';

    #[\Override]
    protected $guarded = [];
}
