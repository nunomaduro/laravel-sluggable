<p align="center">
    <p align="center">
        <a href="https://github.com/nunomaduro/laravel-sluggable/actions"><img alt="GitHub Workflow Status (master)" src="https://github.com/nunomaduro/laravel-sluggable/actions/workflows/tests.yml/badge.svg"></a>
        <a href="https://packagist.org/packages/nunomaduro/laravel-sluggable"><img alt="Total Downloads" src="https://img.shields.io/packagist/dt/nunomaduro/laravel-sluggable"></a>
        <a href="https://packagist.org/packages/nunomaduro/laravel-sluggable"><img alt="Latest Version" src="https://img.shields.io/packagist/v/nunomaduro/laravel-sluggable"></a>
        <a href="https://packagist.org/packages/nunomaduro/laravel-sluggable"><img alt="License" src="https://img.shields.io/packagist/l/nunomaduro/laravel-sluggable"></a>
    </p>
</p>

------

**Laravel Sluggable** is my opinionated take on **automatic slug generation for Eloquent models** — the exact pattern I've reached for across projects like **Laravel Cloud**, now packaged up. A single `#[Sluggable]` attribute on the model is all you need. No trait, no base class, no extra wiring.

> **Requires [PHP 8.5+](https://php.net/releases/) and [Laravel 13.5+](https://laravel.com)**

## Installation

```bash
composer require nunomaduro/laravel-sluggable
```

## Getting Started

To make an existing model sluggable, run the **`make:sluggable`** Artisan command:

```bash
php artisan make:sluggable Post
```

This command adds the `#[Sluggable]` attribute to your model and generates a migration for the slug column. It introspects the model's table to guess the source column (`name`, `title`, `headline`, or `subject`):

```diff
+use NunoMaduro\LaravelSluggable\Attributes\Sluggable;

+#[Sluggable(from: 'title')]
class Post extends Model
{
}
```

That's it. After running `php artisan migrate`, when a `Post` is created, a slug will be automatically generated and stored in the `slug` column:

```php
$post = Post::create(['title' => 'Hello World']);
$post->slug; // "hello-world"
```

The command also accepts `--from` and `--to` options:

```bash
php artisan make:sluggable Post --from=headline --to=url_slug
```

## Manual Setup

If you prefer to set things up by hand, add the attribute yourself:

```php
use Illuminate\Database\Eloquent\Model;
use NunoMaduro\LaravelSluggable\Attributes\Sluggable;

#[Sluggable(from: 'title')]
class Post extends Model
{
}
```

Add a `slug` column to the model's table (a unique `string` column is recommended).

The package listens globally on the `eloquent.creating: *` and `eloquent.updating: *` wildcard events, looks for the `#[Sluggable]` attribute on the saved model, and runs the generator when present. Models without the attribute are ignored.

## Configuration

Every aspect of slug generation can be customized directly on the attribute.

> **Important**: The generated migration creates a simple, unique string column. You **may need to tweak it depending on your slug configuration** — for example, removing the `unique()` constraint if `unique: false`, or when using `scope`. The same goes when adding the `#[Sluggable]` attribute to a model with existing data — you may need to **backfill slugs** before applying a unique constraint.

### `from`

By default, the slug is generated from the `name` column. You may customize this with the `from` parameter:

```php
#[Sluggable(from: 'title')]
```

Slugs may also be generated from multiple columns by passing an array:

```php
#[Sluggable(from: ['first_name', 'last_name'])]
class Author extends Model
{
}

$author = Author::create(['first_name' => 'John', 'last_name' => 'Doe']);
$author->slug; // "john-doe"
```

### `to`

By default, the slug is stored in the `slug` column. You may customize this with the `to` parameter:

```php
#[Sluggable(from: 'title', to: 'url_slug')]
```

### `scope`

When slugs should be unique within a scope (for example, per team or per locale), pass one or more scope columns:

```php
#[Sluggable(scope: 'team_id')]
```

Multiple scope columns are also supported:

```php
#[Sluggable(scope: ['team_id', 'locale'])]
```

### `onUpdating`, `separator`, `unique`, `maxAttempts`, `maxLength`

The attribute accepts several other options to fine-tune slug generation. By default, slugs are generated on creation but not on update, use `-` as the separator, enforce uniqueness with up to 100 attempts, and have no maximum length:

```php
#[Sluggable(
    onUpdating: false,
    separator: '-',
    unique: true,
    maxAttempts: 100,
    maxLength: 60,
)]
```

When `onUpdating` is enabled, the slug is regenerated whenever any source column changes. When a slug value is manually provided, it is always preserved — both on creation and on update.

### `errorKey`

When slug generation fails, a `CouldNotGenerateSlugException` is thrown. In HTTP contexts, this exception renders automatically as a `422` validation error response — just like a failed validation rule. The error is attached to the first source column by default:

```json
{
    "errors": {
        "name": ["The name cannot be converted into a valid slug."]
    }
}
```

You may customize the error key using the `errorKey` parameter:

```php
#[Sluggable(errorKey: 'input_name')]
```

Error messages may be customized by defining `validation.slug_required` and `validation.slug_unique` keys in your application's language files. Both keys receive `:attribute` and `:slug` replacements:

```php
// lang/en/validation.php
'slug_required' => 'The :attribute cannot be converted into a valid :slug.',
'slug_unique' => 'Too many :slug entries exist for the given :attribute. Please try a different value.',
```

Because it extends `ValidationException`, the exception is not reported to the application log — consistent with how Laravel handles `ModelNotFoundException` and other Eloquent exceptions.

## Slug Generation Pipeline

The slug generation pipeline transliterates Unicode characters (including CJK scripts) into their Latin equivalents, preserves dots (useful for domain-like values such as `laravel.com`), and converts special characters into clean separators.

A few examples:

| Input                               | Slug                              |
|-------------------------------------|-----------------------------------|
| `Hello World`                       | `hello-world`                     |
| `Café Résumé`                       | `cafe-resume`                     |
| `Straße`                            | `strasse`                         |
| `如何安装 Laravel`                    | `ru-he-an-zhuang-laravel`         |
| `こんにちは`                          | `konnichiha`                      |
| `안녕하세요`                          | `annyeonghaseyo`                  |
| `Привет Мир`                        | `privet-mir`                      |
| `laravel.com`                       | `laravel.com`                     |
| `sub.domain.example.com`            | `sub.domain.example.com`          |
| `document.final.pdf`                | `document.final.pdf`              |
| `über.straße`                       | `uber.strasse`                    |
| `Example/Path`                      | `example-path`                    |
| `Hello — World`                     | `hello-world`                     |
| `🎉 Hello 🌟 World 🚀`              | `hello-world`                     |
| `[2024] Annual Report (Final)`      | `2024-annual-report-final`        |

**Laravel Sluggable** was created by **[Nuno Maduro](https://x.com/enunomaduro)** under the **[MIT license](https://opensource.org/licenses/MIT)**.
