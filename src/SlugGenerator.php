<?php

declare(strict_types=1);

namespace NunoMaduro\LaravelSluggable;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Translation\Translator;
use NunoMaduro\LaravelSluggable\Attributes\Sluggable;
use NunoMaduro\LaravelSluggable\Exceptions\CouldNotGenerateSlugException;
use ReflectionClass;
use RuntimeException;

final class SlugGenerator
{
    /**
     * @var array<class-string, Sluggable|null>
     */
    private static array $cache = [];

    public function __construct(
        private readonly Model $model,
    ) {}

    public function handleCreating(): void
    {
        $options = $this->options();

        if (is_null($this->model->{$options->to})) {
            $this->model->{$options->to} = $this->generate();
        }
    }

    public function handleUpdating(): void
    {
        $options = $this->options();

        if (! $options->onUpdating) {
            return;
        }

        if ($this->hasCustomSlugBeenUsed() || ! $this->sourceHasChanged()) {
            return;
        }

        $this->model->{$options->to} = $this->generate();
    }

    /**
     * @throws CouldNotGenerateSlugException
     */
    public function generate(): string
    {
        $sourceValue = $this->resolveSourceValue();
        $slug = $this->slugify($sourceValue);

        if ($slug === '') {
            $this->throwEmptySlugException($sourceValue);
        }

        return $this->ensureUnique($slug);
    }

    /**
     * @throws CouldNotGenerateSlugException
     */
    private function throwEmptySlugException(string $sourceValue): never
    {
        $options = $this->options();
        $from = Arr::wrap($options->from);
        $errorKey = $options->errorKey ?? $from[0];
        $columns = implode(', ', $from);

        $exception = CouldNotGenerateSlugException::withMessages([
            $errorKey => $this->resolveErrorMessage($options, 'slug_required'),
        ]);

        $message = 'No slug could be generated for model ['.$this->model::class.sprintf('] using column(s) [%s] with value [%s].', $columns, $sourceValue);

        (fn (): string => $this->message = $message)->call($exception);

        throw $exception;
    }

    private function resolveErrorMessage(Sluggable $options, string $key): string
    {
        $from = array_map(
            static fn (string $name): string => str_replace('_', ' ', Str::snake($name)),
            Arr::wrap($options->from),
        );

        $attribute = count($from) === 1
            ? $from[0]
            : implode(' and ', [implode(', ', array_slice($from, 0, -1)), $from[count($from) - 1]]);

        $replace = [
            'attribute' => $attribute,
            'slug' => str_replace('_', ' ', Str::snake($options->to)),
        ];

        /** @var Translator $translator */
        $translator = app('translator');

        $line = $translator->has('validation.'.$key)
            ? $translator->get('validation.'.$key, $replace)
            : $translator->get('sluggable::validation.'.$key, $replace);

        return is_string($line) ? $line : '';
    }

    private function sourceHasChanged(): bool
    {
        return collect(Arr::wrap($this->options()->from))
            ->contains(fn (string $column): bool => $this->model->isDirty($column));
    }

    private function hasCustomSlugBeenUsed(): bool
    {
        return $this->model->isDirty($this->options()->to);
    }

    private function options(): Sluggable
    {
        return self::resolve($this->model::class)
            ?? throw new RuntimeException(sprintf('Model [%s] is missing the #[Sluggable] attribute.', $this->model::class));
    }

    /**
     * @param  class-string  $class
     */
    public static function resolve(string $class): ?Sluggable
    {
        if (array_key_exists($class, self::$cache)) {
            return self::$cache[$class];
        }

        $reflection = new ReflectionClass($class);

        do {
            $attributes = $reflection->getAttributes(Sluggable::class);

            if ($attributes !== []) {
                return self::$cache[$class] = $attributes[0]->newInstance();
            }

            $reflection = $reflection->getParentClass();
        } while ($reflection instanceof ReflectionClass);

        return self::$cache[$class] = null;
    }

    public static function flush(): void
    {
        self::$cache = [];
    }

    private function resolveSourceValue(): string
    {
        $values = [];

        foreach (Arr::wrap($this->options()->from) as $column) {
            /** @var mixed $value */
            $value = $this->model->{$column};

            if (is_scalar($value) || $value instanceof \Stringable) {
                $values[] = (string) $value;
            }
        }

        return implode(' ', array_filter($values, static fn (string $value): bool => $value !== ''));
    }

    private function slugify(string $value): string
    {
        $options = $this->options();
        $separator = $options->separator;
        $flip = $separator === '-' ? '_' : '-';

        $value = Str::transliterate($value, unknown: '');

        $value = str_replace("'", '', $value);

        $value = (string) preg_replace('!['.preg_quote($flip).']+!u', $separator, $value);

        $value = (string) preg_replace(
            '![^'.preg_quote($separator).'\pL\pN\s.]+!u',
            $separator,
            mb_strtolower($value, 'UTF-8'),
        );

        $value = (string) preg_replace('!['.preg_quote($separator).'\s]+!u', $separator, $value);

        $value = (string) preg_replace(
            '!'.preg_quote($separator).'*\.'.preg_quote($separator).'*!u',
            '.',
            $value,
        );

        $value = (string) preg_replace('!\.+!u', '.', $value);

        $value = trim($value, $separator.'.');

        if ($options->maxLength !== null) {
            return rtrim(mb_substr($value, 0, $options->maxLength), $separator.'.');
        }

        return $value;
    }

    /**
     * @throws CouldNotGenerateSlugException
     */
    private function ensureUnique(string $slug): string
    {
        $options = $this->options();

        if (! $options->unique) {
            return $slug;
        }

        $originalSlug = $slug;
        $count = 1;

        while ($this->slugAlreadyExists($slug)) {
            $count++;

            if ($count > $options->maxAttempts) {
                $this->throwMaxAttemptsException($originalSlug);
            }

            $suffix = $options->separator.$count;

            $slug = $options->maxLength !== null
                ? rtrim(mb_substr($originalSlug, 0, max(0, $options->maxLength - mb_strlen($suffix))), $options->separator).$suffix
                : $originalSlug.$suffix;
        }

        return $slug;
    }

    /**
     * @throws CouldNotGenerateSlugException
     */
    private function throwMaxAttemptsException(string $originalSlug): never
    {
        $options = $this->options();
        $from = Arr::wrap($options->from);
        $errorKey = $options->errorKey ?? $from[0];
        $columns = implode(', ', $from);

        $exception = CouldNotGenerateSlugException::withMessages([
            $errorKey => $this->resolveErrorMessage($options, 'slug_unique'),
        ]);

        $message = 'No unique slug could be generated for model ['.$this->model::class.sprintf('] using column(s) [%s] with value [%s] after %d attempts.', $columns, $originalSlug, $options->maxAttempts);

        (fn (): string => $this->message = $message)->call($exception);

        throw $exception;
    }

    private function slugAlreadyExists(string $slug): bool
    {
        $options = $this->options();
        $model = $this->model;

        $query = $model::query()->withoutGlobalScopes();

        if (in_array(SoftDeletes::class, class_uses_recursive($model), true)) {
            /** @phpstan-ignore-next-line */
            $query->withTrashed();
        }

        $query->where($options->to, $slug);

        foreach (Arr::wrap($options->scope) as $column) {
            $query->where($column, $model->{$column});
        }

        if ($model->exists) {
            $query->where($model->getKeyName(), '!=', $model->getKey());
        }

        return $query->exists();
    }
}
