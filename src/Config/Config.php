<?php

declare(strict_types=1);

namespace Vampqwe\Engine\Config;

use InvalidArgumentException;

final class Config
{
    /** @param array<string, string> $values */
    public function __construct(private readonly array $values)
    {
    }

    public function getString(string $key, string $default = ''): string
    {
        $value = getenv($key);
        $value = $value !== false ? $value : ($_ENV[$key] ?? $_SERVER[$key] ?? $this->values[$key] ?? $default);

        return is_string($value) ? $value : (string) $value;
    }

    public function getBool(string $key, bool $default = false): bool
    {
        $value = filter_var($this->getString($key, $default ? 'true' : 'false'), FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);

        if ($value === null) {
            throw new InvalidArgumentException(sprintf('Configuration value "%s" must be a boolean.', $key));
        }

        return $value;
    }
}