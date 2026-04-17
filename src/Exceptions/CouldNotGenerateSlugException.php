<?php

declare(strict_types=1);

namespace NunoMaduro\LaravelSluggable\Exceptions;

use Illuminate\Validation\ValidationException;

final class CouldNotGenerateSlugException extends ValidationException {}
