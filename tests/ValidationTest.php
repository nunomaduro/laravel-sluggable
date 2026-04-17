<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use NunoMaduro\LaravelSluggable\Exceptions\CouldNotGenerateSlugException;
use Tests\Fixtures\SluggableValidationCustomColumnPost;
use Tests\Fixtures\SluggableValidationCustomErrorMaxAttemptsPost;
use Tests\Fixtures\SluggableValidationCustomErrorPost;
use Tests\Fixtures\SluggableValidationMaxAttemptsPost;
use Tests\Fixtures\SluggableValidationMultiSourcePost;
use Tests\Fixtures\SluggableValidationPost;

it('generates slug on create', function (): void {
    $post = SluggableValidationPost::create(['name' => 'Hello World']);

    expect($post->slug)->toBe('hello-world');
});

it('empty slug returns 422 with default error', function (): void {
    Route::post('/posts', fn () => SluggableValidationPost::create(['name' => '!!!']));

    $this->postJson('/posts')
        ->assertStatus(422)
        ->assertJson([
            'errors' => [
                'name' => ['The name cannot be converted into a valid slug.'],
            ],
        ]);
});

it('empty slug returns 422 with custom error key', function (): void {
    Route::post('/posts', fn () => SluggableValidationCustomErrorPost::create(['name' => '!!!']));

    $this->postJson('/posts')
        ->assertStatus(422)
        ->assertJsonValidationErrors(['custom_field']);
});

it('throws when source produces empty slug', function (): void {
    SluggableValidationPost::create(['name' => '!!!']);
})->throws(CouldNotGenerateSlugException::class, 'No slug could be generated for model [Tests\Fixtures\SluggableValidationPost] using column(s) [name] with value [');

it('throws when emoji-only source produces empty slug', function (): void {
    SluggableValidationPost::create(['name' => '🚀🎯🔥']);
})->throws(CouldNotGenerateSlugException::class, 'No slug could be generated for model [Tests\Fixtures\SluggableValidationPost] using column(s) [name] with value [');

it('throws when source column is null', function (): void {
    SluggableValidationPost::create([]);
})->throws(CouldNotGenerateSlugException::class, 'No slug could be generated for model [Tests\Fixtures\SluggableValidationPost] using column(s) [name] with value [');

it('throws after maximum attempts exceeded', function (): void {
    SluggableValidationMaxAttemptsPost::create(['name' => 'Hello']);
    SluggableValidationMaxAttemptsPost::create(['name' => 'Hello']);
    SluggableValidationMaxAttemptsPost::create(['name' => 'Hello']);
})->throws(CouldNotGenerateSlugException::class, 'No unique slug could be generated for model [Tests\Fixtures\SluggableValidationMaxAttemptsPost] using column(s) [name] with value [hello] after 2 attempts.');

it('json request returns 422 with validation errors', function (): void {
    Route::post('/posts', function (): void {
        SluggableValidationPost::create(['name' => '!!!']);
    });

    $this->postJson('/posts')
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});

it('web request redirects back with errors', function (): void {
    Route::post('/posts', function (): void {
        SluggableValidationPost::create(['name' => '!!!']);
    });

    $this->from('/create')
        ->post('/posts')
        ->assertRedirect('/create')
        ->assertSessionHasErrors(['name']);
});

it('supports custom translation for slug_required', function (): void {
    $this->app['translator']->addLines([
        'validation.slug_required' => 'The :attribute field cannot produce a valid :slug.',
    ], 'en');

    Route::post('/posts', fn () => SluggableValidationPost::create(['name' => '!!!']));

    $this->postJson('/posts')
        ->assertStatus(422)
        ->assertJson([
            'errors' => [
                'name' => ['The name field cannot produce a valid slug.'],
            ],
        ]);
});

it('supports custom translation for slug_unique', function (): void {
    $this->app['translator']->addLines([
        'validation.slug_unique' => 'A unique :slug could not be generated for :attribute.',
    ], 'en');

    SluggableValidationMaxAttemptsPost::create(['name' => 'Hello']);
    SluggableValidationMaxAttemptsPost::create(['name' => 'Hello']);

    Route::post('/posts', fn () => SluggableValidationMaxAttemptsPost::create(['name' => 'Hello']));

    $this->postJson('/posts')
        ->assertStatus(422)
        ->assertJson([
            'errors' => [
                'name' => ['A unique slug could not be generated for name.'],
            ],
        ]);
});

it('multi-source error message lists all columns', function (): void {
    Route::post('/posts', fn () => SluggableValidationMultiSourcePost::create(['first_name' => '!!!', 'last_name' => '!!!']));

    $this->postJson('/posts')
        ->assertStatus(422)
        ->assertJson([
            'errors' => [
                'first_name' => ['The first name and last name cannot be converted into a valid slug.'],
            ],
        ]);
});

it('custom column appears in error message', function (): void {
    Route::post('/posts', fn () => SluggableValidationCustomColumnPost::create(['name' => '!!!']));

    $this->postJson('/posts')
        ->assertStatus(422)
        ->assertJson([
            'errors' => [
                'name' => ['The name cannot be converted into a valid url slug.'],
            ],
        ]);
});

it('uniqueness failure returns 422 via json', function (): void {
    Route::post('/posts', function (): void {
        SluggableValidationMaxAttemptsPost::create(['name' => 'Hello']);
        SluggableValidationMaxAttemptsPost::create(['name' => 'Hello']);
        SluggableValidationMaxAttemptsPost::create(['name' => 'Hello']);
    });

    $this->postJson('/posts')
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name'])
        ->assertJson([
            'errors' => [
                'name' => ['Too many slug entries exist for the given name. Please try a different value.'],
            ],
        ]);
});

it('uniqueness failure uses custom error key', function (): void {
    Route::post('/posts', function (): void {
        SluggableValidationCustomErrorMaxAttemptsPost::create(['name' => 'Hello']);
        SluggableValidationCustomErrorMaxAttemptsPost::create(['name' => 'Hello']);
        SluggableValidationCustomErrorMaxAttemptsPost::create(['name' => 'Hello']);
    });

    $this->postJson('/posts')
        ->assertStatus(422)
        ->assertJsonValidationErrors(['custom_field']);
});
