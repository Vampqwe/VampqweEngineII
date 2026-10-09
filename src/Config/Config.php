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

    public function getInt(string $key, int $default = 0, int $minimum = 0, int $maximum = PHP_INT_MAX): int
    {
        $value = filter_var($this->getString($key, (string) $default), FILTER_VALIDATE_INT);

        if ($value === false || $value < $minimum || $value > $maximum) {
            throw new InvalidArgumentException(sprintf(
                'Configuration value "%s" must be an integer between %d and %d.',
                $key,
                $minimum,
                $maximum,
            ));
        }

        return $value;
    }
}