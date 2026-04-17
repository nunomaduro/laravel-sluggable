<?php

declare(strict_types=1);

use stdClass;
use Tests\Fixtures\NotSluggable;

it('ignores saves of models without the Sluggable attribute', function (): void {
    $model = NotSluggable::create(['name' => 'Hello World']);

    expect($model->exists)->toBeTrue();

    $model->name = 'Updated';
    $model->save();

    expect($model->name)->toBe('Updated');
});

it('ignores wildcard eloquent events whose payload is not a model', function (): void {
    app('events')->dispatch('eloquent.creating: FakeModel', [new stdClass]);
    app('events')->dispatch('eloquent.updating: FakeModel', [new stdClass]);

    expect(true)->toBeTrue();
});
