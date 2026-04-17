<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Schema;
use NunoMaduro\LaravelSluggable\SluggableServiceProvider;
use NunoMaduro\LaravelSluggable\SlugGenerator;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        SlugGenerator::flush();

        Schema::create('sluggable_posts', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->string('slug')->nullable();
            $table->timestamps();
        });

        Schema::create('sluggable_custom_posts', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('title')->nullable();
            $table->string('url_slug')->nullable();
            $table->timestamps();
        });

        Schema::create('sluggable_scoped_posts', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->string('slug')->nullable();
            $table->integer('team_id')->nullable();
            $table->timestamps();
        });

        Schema::create('sluggable_multi_scoped_posts', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->string('slug')->nullable();
            $table->integer('team_id')->nullable();
            $table->string('locale')->nullable();
            $table->timestamps();
        });

        Schema::create('sluggable_soft_delete_posts', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->string('slug')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('sluggable_multi_source_posts', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('slug')->nullable();
            $table->timestamps();
        });

        Schema::create('sluggable_validation_posts', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->string('slug')->nullable();
            $table->string('url_slug')->nullable();
            $table->timestamps();
        });

        Schema::create('sluggable_validation_multi_posts', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('slug')->nullable();
            $table->timestamps();
        });

        Schema::create('not_sluggable', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('sluggable_posts');
        Schema::dropIfExists('sluggable_custom_posts');
        Schema::dropIfExists('sluggable_scoped_posts');
        Schema::dropIfExists('sluggable_multi_scoped_posts');
        Schema::dropIfExists('sluggable_soft_delete_posts');
        Schema::dropIfExists('sluggable_multi_source_posts');
        Schema::dropIfExists('sluggable_validation_posts');
        Schema::dropIfExists('sluggable_validation_multi_posts');
        Schema::dropIfExists('not_sluggable');

        parent::tearDown();
    }

    /**
     * @param  Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [SluggableServiceProvider::class];
    }
}
