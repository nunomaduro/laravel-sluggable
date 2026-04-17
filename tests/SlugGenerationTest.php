<?php

declare(strict_types=1);

use Tests\Fixtures\SluggableCustomColumnsPost;
use Tests\Fixtures\SluggableCustomSeparatorPost;
use Tests\Fixtures\SluggableMaxLengthPost;
use Tests\Fixtures\SluggableMultiScopedPost;
use Tests\Fixtures\SluggableMultiSourcePost;
use Tests\Fixtures\SluggableMultiSourceUpdatePost;
use Tests\Fixtures\SluggableNonUniquePost;
use Tests\Fixtures\SluggablePost;
use Tests\Fixtures\SluggableScopedPost;
use Tests\Fixtures\SluggableSoftDeletePost;
use Tests\Fixtures\SluggableTinyMaxLengthPost;
use Tests\Fixtures\SluggableUpdatePost;

it('generates slug from name column by default', function (): void {
    $post = SluggablePost::create(['name' => 'My First Post']);

    expect($post->slug)->toBe('my-first-post');
});

it('works with zero config', function (): void {
    $post = SluggablePost::create(['name' => 'My Post']);

    expect($post->slug)->toBe('my-post');
});

it('generates from multiple source columns', function (): void {
    $post = SluggableMultiSourcePost::create(['first_name' => 'John', 'last_name' => 'Doe']);

    expect($post->slug)->toBe('john-doe');
});

it('generates from multiple source columns with partial null', function (): void {
    $post = SluggableMultiSourcePost::create(['first_name' => 'John', 'last_name' => null]);

    expect($post->slug)->toBe('john');
});

it('works with custom source and slug columns', function (): void {
    $post = SluggableCustomColumnsPost::create(['title' => 'Hello World']);

    expect($post->url_slug)->toBe('hello-world');
});

it('works with custom separator', function (): void {
    $post = SluggableCustomSeparatorPost::create(['name' => 'Hello World']);

    expect($post->slug)->toBe('hello_world');
});

it('uses custom separator in collision suffix', function (): void {
    SluggableCustomSeparatorPost::create(['name' => 'Hello World']);
    $post = SluggableCustomSeparatorPost::create(['name' => 'Hello World']);

    expect($post->slug)->toBe('hello_world_2');
});

it('respects scoped uniqueness', function (): void {
    SluggableScopedPost::create(['name' => 'Hello', 'team_id' => 1]);
    $post = SluggableScopedPost::create(['name' => 'Hello', 'team_id' => 1]);

    expect($post->slug)->toBe('hello-2');
});

it('allows same slug in different scopes', function (): void {
    SluggableScopedPost::create(['name' => 'Hello', 'team_id' => 1]);
    $post = SluggableScopedPost::create(['name' => 'Hello', 'team_id' => 2]);

    expect($post->slug)->toBe('hello');
});

it('respects multi-column scoped uniqueness', function (): void {
    SluggableMultiScopedPost::create(['name' => 'Hello', 'team_id' => 1, 'locale' => 'en']);
    $post = SluggableMultiScopedPost::create(['name' => 'Hello', 'team_id' => 1, 'locale' => 'en']);

    expect($post->slug)->toBe('hello-2');
});

it('allows same slug in different multi-column scopes', function (): void {
    SluggableMultiScopedPost::create(['name' => 'Hello', 'team_id' => 1, 'locale' => 'en']);
    $post = SluggableMultiScopedPost::create(['name' => 'Hello', 'team_id' => 1, 'locale' => 'fr']);

    expect($post->slug)->toBe('hello');
});

it('preserves manually provided slug on create', function (): void {
    $post = SluggablePost::create(['name' => 'My First Post', 'slug' => 'custom-slug']);

    expect($post->slug)->toBe('custom-slug');
});

it('does not regenerate slug on update by default', function (): void {
    $post = SluggablePost::create(['name' => 'Hello World']);
    $post->name = 'Updated Name';
    $post->save();

    expect($post->slug)->toBe('hello-world');
});

it('regenerates slug on update when opted in and source changed', function (): void {
    $post = SluggableUpdatePost::create(['name' => 'Hello World']);
    $post->name = 'Updated Name';
    $post->save();

    expect($post->slug)->toBe('updated-name');
});

it('does not regenerate slug on update when source unchanged', function (): void {
    $post = SluggableUpdatePost::create(['name' => 'Hello World']);
    $original = $post->slug;
    $post->updated_at = now();
    $post->save();

    expect($post->slug)->toBe($original);
});

it('preserves manually changed slug on update', function (): void {
    $post = SluggableUpdatePost::create(['name' => 'Hello World']);
    $post->name = 'New Name';
    $post->slug = 'my-custom-slug';
    $post->save();

    expect($post->slug)->toBe('my-custom-slug');
});

it('excludes current model from uniqueness check on update', function (): void {
    $post = SluggableUpdatePost::create(['name' => 'Hello World']);
    $post->name = 'Hello World Updated';
    $post->save();

    expect($post->slug)->toBe('hello-world-updated');
});

it('regenerates slug on update when one of multiple sources changes', function (): void {
    $post = SluggableMultiSourceUpdatePost::create(['first_name' => 'John', 'last_name' => 'Doe']);
    expect($post->slug)->toBe('john-doe');

    $post->last_name = 'Smith';
    $post->save();

    expect($post->slug)->toBe('john-smith');
});

it('does not regenerate slug on update when no source column changes', function (): void {
    $post = SluggableMultiSourceUpdatePost::create(['first_name' => 'John', 'last_name' => 'Doe']);
    $post->updated_at = now();
    $post->save();

    expect($post->slug)->toBe('john-doe');
});

it('resolves collisions with numeric suffixes', function (): void {
    SluggablePost::create(['name' => 'Hello World']);
    $post = SluggablePost::create(['name' => 'Hello World']);

    expect($post->slug)->toBe('hello-world-2');
});

it('resolves multiple collisions incrementally', function (): void {
    SluggablePost::create(['name' => 'Hello World']);
    SluggablePost::create(['name' => 'Hello World']);
    $post = SluggablePost::create(['name' => 'Hello World']);

    expect($post->slug)->toBe('hello-world-3');
});

it('allows duplicate slugs when unique is false', function (): void {
    SluggableNonUniquePost::create(['name' => 'Hello World']);
    $post = SluggableNonUniquePost::create(['name' => 'Hello World']);

    expect($post->slug)->toBe('hello-world');
});

it('includes soft deleted records in uniqueness check', function (): void {
    $post = SluggableSoftDeletePost::create(['name' => 'Hello']);
    $post->delete();

    $newPost = SluggableSoftDeletePost::create(['name' => 'Hello']);

    expect($newPost->slug)->toBe('hello-2');
});

it('respects maximum length', function (): void {
    $post = SluggableMaxLengthPost::create(['name' => 'This Is A Very Long Title That Should Be Truncated']);

    expect(mb_strlen((string) $post->slug))->toBeLessThanOrEqual(20);
});

it('respects maximum length with collision suffix', function (): void {
    SluggableMaxLengthPost::create(['name' => 'This Is A Very Long Title']);
    $post = SluggableMaxLengthPost::create(['name' => 'This Is A Very Long Title']);

    expect(mb_strlen((string) $post->slug))->toBeLessThanOrEqual(20)
        ->and($post->slug)->toEndWith('-2');
});

it('handles max length smaller than suffix', function (): void {
    SluggableTinyMaxLengthPost::create(['name' => 'Hi']);
    $post = SluggableTinyMaxLengthPost::create(['name' => 'Hi']);

    expect($post->slug)->toBe('-2');
});

dataset('slugs', [
    'simple words' => ['Hello World', 'hello-world'],
    'single word' => ['Laravel', 'laravel'],
    'already lowercase' => ['hello world', 'hello-world'],
    'mixed case' => ['HeLLo WoRLd', 'hello-world'],
    'all uppercase' => ['HELLO WORLD', 'hello-world'],
    'single character' => ['A', 'a'],
    'two words' => ['Foo Bar', 'foo-bar'],
    'three words' => ['Foo Bar Baz', 'foo-bar-baz'],
    'long title' => ['The Quick Brown Fox Jumps Over The Lazy Dog', 'the-quick-brown-fox-jumps-over-the-lazy-dog'],

    'multiple spaces' => ['hello   world', 'hello-world'],
    'leading spaces' => ['  hello world', 'hello-world'],
    'trailing spaces' => ['hello world  ', 'hello-world'],
    'tabs' => ["hello\tworld", 'hello-world'],
    'newlines' => ["hello\nworld", 'hello-world'],
    'mixed whitespace' => ["hello \t\n world", 'hello-world'],

    'numbers only' => ['123', '123'],
    'number prefix' => ['123 Example', '123-example'],
    'number suffix' => ['Example 456', 'example-456'],
    'numbers mixed' => ['123 456 Example', '123-456-example'],
    'version number' => ['Version 2', 'version-2'],
    'year in title' => ['Best Of 2024', 'best-of-2024'],
    'ordinal' => ['1st Place', '1st-place'],

    'existing hyphens' => ['hello-world', 'hello-world'],
    'existing underscores' => ['hello_world', 'hello-world'],
    'multiple hyphens' => ['hello---world', 'hello-world'],
    'multiple underscores' => ['hello___world', 'hello-world'],
    'mixed hyphens underscores' => ['hello-_-world', 'hello-world'],
    'underscore in phrase' => ['Test_123_Test', 'test-123-test'],
    'leading hyphen' => ['-hello', 'hello'],
    'trailing hyphen' => ['hello-', 'hello'],

    'ampersand' => ['Tom & Jerry', 'tom-jerry'],
    'plus sign' => ['C++ Programming', 'c-programming'],
    'parentheses' => ['Hello (World)', 'hello-world'],
    'brackets' => ['Hello [World]', 'hello-world'],
    'curly braces' => ['Hello {World}', 'hello-world'],
    'exclamation' => ['Hello World!', 'hello-world'],
    'question mark' => ['What Is This?', 'what-is-this'],
    'comma' => ['Hello, World', 'hello-world'],
    'semicolon' => ['Example;Path', 'example-path'],
    'colon' => ['Example:Path', 'example-path'],
    'quotes single' => ["It's Here", 'its-here'],
    'quotes double' => ['"Hello World"', 'hello-world'],
    'at sign' => ['user@host', 'user-host'],
    'hash' => ['Hello#World', 'hello-world'],
    'dollar' => ['100$ Deal', '100-deal'],
    'percent' => ['100% Done', '100-done'],
    'caret' => ['Hello^World', 'hello-world'],
    'asterisk' => ['Hello*World', 'hello-world'],
    'tilde' => ['Hello~World', 'hello-world'],
    'pipe' => ['Hello|World', 'hello-world'],
    'forward slash' => ['Example/Path', 'example-path'],
    'backslash' => ['Example\\Path', 'example-path'],
    'mixed symbols' => ['Hello!@#$%^&*()', 'hello'],
    'equals sign' => ['A=B', 'a-b'],
    'angle brackets' => ['<Hello>', 'hello'],

    'basic domain' => ['laravel.com', 'laravel.com'],
    'spaces with dot' => ['My Website.js', 'my-website.js'],
    'multiple dots' => ['sub.domain.example.com', 'sub.domain.example.com'],
    'leading dots' => ['...example', 'example'],
    'trailing dots' => ['example...', 'example'],
    'consecutive dots' => ['example..test', 'example.test'],
    'mixed content with dot' => ['Hello World.pdf', 'hello-world.pdf'],
    'dot at boundaries' => ['.hello.', 'hello'],
    'special chars with dots' => ['Café.résumé', 'cafe.resume'],
    'empty segments from dots' => ['a..b..c', 'a.b.c'],
    'dot with separator' => ['hello-world.test', 'hello-world.test'],
    'single char dot segments' => ['a.b.c', 'a.b.c'],
    'numbers with dots' => ['version.2.0', 'version.2.0'],
    'ip address' => ['192.168.1.1', '192.168.1.1'],
    'file extensions' => ['document.final.pdf', 'document.final.pdf'],
    'spaces around dots' => ['hello . world', 'hello.world'],
    'uppercase with dots' => ['HELLO.WORLD', 'hello.world'],
    'mixed case with dots' => ['Hello.World.Test', 'hello.world.test'],
    'dot with underscores' => ['hello_world.test_file', 'hello-world.test-file'],
    'multiple spaces and dots' => ['hello   world . foo   bar', 'hello-world.foo-bar'],
    'dot only between words' => ['hello.world', 'hello.world'],
    'dot and hyphen' => ['hello-.world', 'hello.world'],

    'french accents' => ['Café Résumé', 'cafe-resume'],
    'spanish tilde' => ['Niño Español', 'nino-espanol'],
    'portuguese cedilla' => ['Ação Programação', 'acao-programacao'],
    'scandinavian' => ['Ångström Ölsen', 'angstrom-olsen'],
    'czech characters' => ['Příliš Žluťoučký', 'prilis-zlutoucky'],
    'polish characters' => ['Łódź Źródło', 'lodz-zrodlo'],
    'turkish characters' => ['İstanbul Güneş', 'istanbul-gunes'],
    'vietnamese' => ['Việt Nam', 'viet-nam'],
    'german umlauts' => ['Über die Brücke', 'uber-die-brucke'],
    'german eszett' => ['Straße', 'strasse'],
    'french combined' => ['Crème Brûlée', 'creme-brulee'],
    'nordic combined' => ['Fjörður Ísland', 'fjordur-island'],

    'chinese characters' => ['你好世界', 'ni-hao-shi-jie'],
    'chinese with latin' => ['如何安装 Laravel', 'ru-he-an-zhuang-laravel'],
    'mixed chinese english' => ['Hello 你好', 'hello-ni-hao'],
    'japanese hiragana' => ['こんにちは', 'konnichiha'],
    'korean' => ['안녕하세요', 'annyeonghaseyo'],
    'russian cyrillic' => ['Привет Мир', 'privet-mir'],
    'ukrainian' => ['Київ Україна', 'kiyiv-ukrayina'],
    'greek' => ['Αθήνα Ελλάδα', 'athena-ellada'],
    'arabic' => ['مرحبا بالعالم', 'mrhb-bl-lm'],
    'thai' => ['สวัสดี', 'swasdii'],
    'hindi devanagari' => ['नमस्ते', 'nmste'],

    'accents with dots' => ['über.straße', 'uber.strasse'],
    'chinese with dots' => ['你好.世界', 'ni-hao.shi-jie'],
    'cyrillic with dots' => ['Привет.Мир', 'privet.mir'],

    'emoji suffix' => ['Example 😊', 'example'],
    'emoji prefix' => ['🚀 Example', 'example'],
    'emoji between words' => ['Hello 🌍 World', 'hello-world'],
    'emoji inline' => ['Test🔥Case', 'testcase'],
    'emoji only prefix' => ['💡Test', 'test'],
    'multiple emojis' => ['🎉 Hello 🌟 World 🚀', 'hello-world'],

    'very long word' => ['Supercalifragilisticexpialidocious', 'supercalifragilisticexpialidocious'],
    'repeated word' => ['test test test', 'test-test-test'],
    'single letter words' => ['a b c d', 'a-b-c-d'],
    'number zero' => ['0', '0'],
    'mixed everything' => ['Hello, World! (2024) - A Test_Case', 'hello-world-2024-a-test-case'],
    'url like' => ['https://example.com/path', 'https-example.com-path'],
    'email like' => ['user@example.com', 'user-example.com'],
    'file path' => ['src/Components/Button.tsx', 'src-components-button.tsx'],

    'apostrophe in word' => ["hello'world", 'helloworld'],
    'im contraction' => ["I'm happy", 'im-happy'],
    'its contraction' => ["it's a test", 'its-a-test'],
    'rock n roll' => ["rock 'n' roll", 'rock-n-roll'],
    'dont contraction' => ["don't stop", 'dont-stop'],

    'trademark' => ['Test™ Product', 'test-tm-product'],
    'euro sign' => ['Price €100', 'price-eur100'],
    'pound sign' => ['Weight £50', 'weight-ps50'],
    'degree sign' => ['Temp °C', 'temp-degc'],
    'section sign' => ['Section §1', 'section-ss1'],
    'copyright' => ['©2024 Company', 'c-2024-company'],
    'registered' => ['®Brand', 'r-brand'],

    'em dash with spaces' => ['Hello   —   World', 'hello-world'],
    'en dash' => ['Hello – World', 'hello-world'],
    'em dash' => ['Hello — World', 'hello-world'],
    'double hyphen' => ['hello--world', 'hello-world'],
    'leading double hyphen' => ['--hello--world--', 'hello-world'],
    'test with triple hyphens' => ['Test---Case', 'test-case'],
    'test with triple underscores' => ['Test___Case', 'test-case'],
    'test hyphen spaced' => ['Test - Case', 'test-case'],
    'test underscore spaced' => ['Test _ Case', 'test-case'],

    'filename with version and copy' => ['file_name-v2.0 (copy)', 'file-name-v2.0-copy'],
    'bracketed year report' => ['[2024] Annual Report (Final)', '2024-annual-report-final'],
    'qa with symbols' => ['Q&A: Common Questions!', 'q-a-common-questions'],
    'price with slash' => ['Price: $49.99/month', 'price-49.99-month'],
]);

it('slugifies', function (string $input, string $expected): void {
    $post = SluggablePost::create(['name' => $input]);

    expect($post->slug)->toBe($expected);
})->with('slugs');
