<?php

declare(strict_types=1);

namespace NunoMaduro\LaravelSluggable;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use NunoMaduro\LaravelSluggable\Console\SluggableMakeCommand;

final class SluggableServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            SluggableMakeCommand::class,
            static fn (Application $app): SluggableMakeCommand => new SluggableMakeCommand($app->make(Filesystem::class)),
        );
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/lang', 'sluggable');

        $this->publishes([
            __DIR__.'/lang' => $this->app->langPath('vendor/sluggable'),
        ], 'sluggable-lang');

        if ($this->app->runningInConsole()) {
            $this->commands([
                SluggableMakeCommand::class,
            ]);
        }

        $this->registerEventListeners();
    }

    private function registerEventListeners(): void
    {
        /** @var Dispatcher $events */
        $events = $this->app->make('events');

        $events->listen('eloquent.creating: *', static function (string $event, array $payload): void {
            $model = $payload[0] ?? null;

            if (! $model instanceof Model) {
                return;
            }

            if (SlugGenerator::resolve($model::class) === null) {
                return;
            }

            (new SlugGenerator($model))->handleCreating();
        });

        $events->listen('eloquent.updating: *', static function (string $event, array $payload): void {
            $model = $payload[0] ?? null;

            if (! $model instanceof Model) {
                return;
            }

            if (SlugGenerator::resolve($model::class) === null) {
                return;
            }

            (new SlugGenerator($model))->handleUpdating();
        });
    }
}
