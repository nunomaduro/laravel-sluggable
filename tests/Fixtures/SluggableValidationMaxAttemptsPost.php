<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use NunoMaduro\LaravelSluggable\Attributes\Sluggable;

#[Sluggable(maxAttempts: 2)]
final class SluggableValidationMaxAttemptsPost extends Model
{
    #[\Override]
    protected $table = 'sluggable_validation_posts';

    #[\Override]
    protected $guarded = [];
}
