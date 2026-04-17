<?php

declare(strict_types=1);

namespace NunoMaduro\LaravelSluggable\Console;

use Illuminate\Console\Command;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Migrations\MigrationCreator;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

use function Illuminate\Filesystem\join_paths;

#[AsCommand(name: 'make:sluggable', description: 'Add the Sluggable attribute to a model and create a migration for the slug column')]
final class SluggableMakeCommand extends Command
{
    /**
     * @var string
     */
    #[\Override]
    protected $signature = 'make:sluggable {model : The model to make sluggable}
                                       {--from= : The source column to generate the slug from}
                                       {--to=slug : The target column to place the slug value}';

    public function __construct(private readonly Filesystem $files)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $modelClass = $this->qualifyModel($this->stringArgument('model'));
        $modelPath = $this->getModelPath($modelClass);

        if (! $this->files->exists($modelPath)) {
            $this->components->error(sprintf('Model [%s] does not exist.', $modelClass));

            return 1;
        }

        /** @var Model $model */
        $model = $this->laravel->make($modelClass);
        $table = $model->getTable();
        $column = $this->stringOption('to') ?? 'slug';
        $source = $this->stringOption('from') ?? $this->guessSourceColumn($model);

        $this->addAttributeToModel($modelClass, $modelPath, $source, $column);

        return $this->createMigration($table, $column);
    }

    private function stringArgument(string $key): string
    {
        $value = $this->argument($key);

        return is_string($value) ? $value : '';
    }

    private function stringOption(string $key): ?string
    {
        $value = $this->option($key);

        return is_string($value) ? $value : null;
    }

    private function addAttributeToModel(string $modelClass, string $modelPath, string $source, string $column): void
    {
        $contents = $this->files->get($modelPath);

        if (str_contains($contents, '#[Sluggable')) {
            $this->components->warn(sprintf('Model [%s] already has the Sluggable attribute.', $modelClass));

            return;
        }

        $attributeImport = 'use NunoMaduro\\LaravelSluggable\\Attributes\\Sluggable;';

        if (! str_contains($contents, $attributeImport)) {
            $contents = $this->insertImport($contents, $attributeImport);
        }

        $attribute = $column === 'slug'
            ? sprintf("#[Sluggable(from: '%s')]", $source)
            : sprintf("#[Sluggable(from: '%s', to: '%s')]", $source, $column);

        $contents = (string) preg_replace(
            '/(^)(class\s+\w+)/m',
            "$1{$attribute}\n$2",
            $contents,
            1,
        );

        $this->files->put($modelPath, $contents);

        $this->components->info(sprintf('Sluggable attribute added to [%s].', $modelClass));
    }

    private function insertImport(string $contents, string $import): string
    {
        return (string) preg_replace(
            '/^(namespace [^;]+;\s*)$/m',
            "$1\n".$import,
            $contents,
            1,
        );
    }

    private function guessSourceColumn(Model $model): string
    {
        /** @var DatabaseManager $db */
        $db = $this->laravel->make('db');

        $columns = $db->connection()
            ->getSchemaBuilder()
            ->getColumnListing($model->getTable());

        foreach (['name', 'title', 'headline', 'subject'] as $candidate) {
            if (in_array($candidate, $columns, true)) {
                return $candidate;
            }
        }

        return 'name';
    }

    private function createMigration(string $table, string $column): int
    {
        if ($this->hasSlugColumn($table, $column)) {
            $this->components->warn(sprintf('Table [%s] already has a [%s] column. Migration not created.', $table, $column));

            return 0;
        }

        if ($this->migrationExists($table, $column)) {
            $this->components->error('Migration already exists.');

            return 1;
        }

        /** @var MigrationCreator $creator */
        $creator = $this->laravel->make('migration.creator');

        $path = $creator->create(
            sprintf('add_%s_to_%s_table', $column, $table),
            $this->laravel->databasePath('/migrations'),
        );

        $stub = str_replace(
            ['{{table}}', '{{column}}'],
            [$table, $column],
            $this->files->get(__DIR__.'/stubs/add_slug_column.stub'),
        );

        $this->files->put($path, $stub);

        $this->components->warn('Migration created. Please review it before running — you may need to adjust it based on your existing data or slug configuration.');

        return 0;
    }

    private function hasSlugColumn(string $table, string $column): bool
    {
        /** @var DatabaseManager $db */
        $db = $this->laravel->make('db');

        return $db->connection()
            ->getSchemaBuilder()
            ->hasColumn($table, $column);
    }

    private function migrationExists(string $table, string $column): bool
    {
        return count($this->files->glob(
            join_paths($this->laravel->databasePath('migrations'), sprintf('*_*_*_*_add_%s_to_%s_table.php', $column, $table)),
        )) !== 0;
    }

    private function getModelPath(string $modelClass): string
    {
        $relativePath = str_replace('\\', '/', Str::replaceFirst($this->laravel->getNamespace(), '', $modelClass));

        return $this->laravel->basePath('app/'.$relativePath.'.php');
    }

    private function qualifyModel(string $model): string
    {
        $model = ltrim($model, '\\/');
        $model = str_replace('/', '\\', $model);

        $rootNamespace = $this->laravel->getNamespace();

        if (Str::startsWith($model, $rootNamespace)) {
            return $model;
        }

        return is_dir($this->laravel->basePath('app/Models'))
            ? $rootNamespace.'Models\\'.$model
            : $rootNamespace.$model;
    }
}
