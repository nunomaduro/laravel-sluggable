<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Schema;
use NunoMaduro\LaravelSluggable\Console\SluggableMakeCommand;

beforeEach(function (): void {
    $this->files = $this->app[Filesystem::class];

    $this->modelPath = $this->app->basePath('app/Models/Foo.php');
    $this->migrationsPath = $this->app->databasePath('migrations');

    $this->files->ensureDirectoryExists(dirname($this->modelPath));
    $this->files->ensureDirectoryExists($this->migrationsPath);

    $this->files->put($this->modelPath, <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Foo extends Model
{
}
PHP);

    require_once $this->modelPath;
});

afterEach(function (): void {
    $this->files->deleteDirectory($this->app->basePath('app'));

    foreach ($this->files->glob($this->migrationsPath.'/*_add_*_to_foos_table.php') as $file) {
        $this->files->delete($file);
    }
});

function readModel(): string
{
    return test()->files->get(test()->modelPath);
}

function readLatestMigration(string $match): string
{
    $files = test()->files->glob(test()->migrationsPath.'/*'.$match);

    return test()->files->get($files[0]);
}

it('creates migration and adds attribute to model', function (): void {
    $this->artisan(SluggableMakeCommand::class, ['model' => 'Foo'])
        ->expectsOutputToContain('Sluggable attribute added to [App\Models\Foo]')
        ->expectsOutputToContain('Migration created. Please review it')
        ->assertExitCode(0);

    $migration = readLatestMigration('_add_slug_to_foos_table.php');

    expect($migration)
        ->toContain('use Illuminate\Database\Migrations\Migration;')
        ->toContain('return new class extends Migration')
        ->toContain("Schema::table('foos', function (Blueprint \$table) {")
        ->toContain("->string('slug')")
        ->toContain('->unique()')
        ->toContain("\$table->dropColumn('slug');");

    $model = readModel();

    expect($model)
        ->toContain('use NunoMaduro\LaravelSluggable\Attributes\Sluggable;')
        ->toContain("#[Sluggable(from: 'name')]");
});

it('accepts fully qualified class name', function (): void {
    $this->artisan(SluggableMakeCommand::class, ['model' => 'App\Models\Foo'])
        ->expectsOutputToContain('Sluggable attribute added to [App\Models\Foo]')
        ->assertExitCode(0);

    expect(readModel())->toContain("#[Sluggable(from: 'name')]");
});

it('warns when attribute already exists', function (): void {
    $this->files->put($this->modelPath, <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use NunoMaduro\LaravelSluggable\Attributes\Sluggable;

#[Sluggable]
class Foo extends Model
{
}
PHP);

    $this->artisan(SluggableMakeCommand::class, ['model' => 'Foo'])
        ->expectsOutputToContain('already has the Sluggable attribute')
        ->assertExitCode(0);
});

it('warns when table already has slug column', function (): void {
    Schema::create('foos', function (Blueprint $table): void {
        $table->id();
        $table->string('slug');
    });

    $this->artisan(SluggableMakeCommand::class, ['model' => 'Foo'])
        ->expectsOutputToContain('already has a [slug] column')
        ->assertExitCode(0);

    Schema::drop('foos');
});

it('guesses title column from table', function (): void {
    Schema::create('foos', function (Blueprint $table): void {
        $table->id();
        $table->string('title');
    });

    $this->artisan(SluggableMakeCommand::class, ['model' => 'Foo'])
        ->assertExitCode(0);

    expect(readModel())->toContain("#[Sluggable(from: 'title')]");

    Schema::drop('foos');
});

it('errors when migration already exists', function (): void {
    $this->artisan(SluggableMakeCommand::class, ['model' => 'Foo'])
        ->assertExitCode(0);

    $this->artisan(SluggableMakeCommand::class, ['model' => 'Foo'])
        ->expectsOutputToContain('Migration already exists')
        ->assertExitCode(1);
});

it('uses custom from option', function (): void {
    $this->artisan(SluggableMakeCommand::class, ['model' => 'Foo', '--from' => 'headline'])
        ->expectsOutputToContain('Sluggable attribute added to [App\Models\Foo]')
        ->assertExitCode(0);

    expect(readModel())->toContain("#[Sluggable(from: 'headline')]");
});

it('uses custom column name', function (): void {
    $this->artisan(SluggableMakeCommand::class, ['model' => 'Foo', '--to' => 'url_slug'])
        ->expectsOutputToContain('Sluggable attribute added to [App\Models\Foo]')
        ->expectsOutputToContain('Migration created.')
        ->assertExitCode(0);

    $migration = readLatestMigration('_add_url_slug_to_foos_table.php');

    expect($migration)
        ->toContain("->string('url_slug')")
        ->toContain('->unique()')
        ->toContain("\$table->dropColumn('url_slug');");

    expect(readModel())->toContain("#[Sluggable(from: 'name', to: 'url_slug')]");
});

it('errors when model does not exist', function (): void {
    $this->artisan(SluggableMakeCommand::class, ['model' => 'NonExistentModel'])
        ->expectsOutputToContain('does not exist')
        ->assertExitCode(1);
});
