<?php

declare(strict_types=1);

namespace NunoMaduro\LaravelSluggable\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class Sluggable
{
    /**
     * @param  array<int, string>|string  $from
     * @param  array<int, string>|string  $scope
     */
    public function __construct(
        public array|string $from = 'name',
        public string $to = 'slug',
        public string $separator = '-',
        public array|string $scope = [],
        public bool $onUpdating = false,
        public bool $unique = true,
        public int $maxAttempts = 100,
        public ?int $maxLength = null,
        public ?string $errorKey = null,
    ) {}
}
