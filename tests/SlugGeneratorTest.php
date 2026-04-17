<?php

declare(strict_types=1);

use NunoMaduro\LaravelSluggable\Attributes\Sluggable;
use NunoMaduro\LaravelSluggable\SlugGenerator;
use Tests\Fixtures\ChildSluggablePost;
use Tests\Fixtures\NotSluggable;

it('resolves the attribute from a parent class', function (): void {
    $post = ChildSluggablePost::create(['name' => 'Inherited Title']);

    expect($post->slug)->toBe('inherited-title');
});

it('resolves the attribute instance for the class', function (): void {
    expect(SlugGenerator::resolve(ChildSluggablePost::class))->toBeInstanceOf(Sluggable::class);
});

it('returns null for a class without the attribute', function (): void {
    expect(SlugGenerator::resolve(NotSluggable::class))->toBeNull();
});
