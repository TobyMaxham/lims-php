<?php

declare(strict_types=1);

namespace TobiasMaxham\PhpLims\Models;

/**
 * Base class for API resources. Attributes are accessible as properties.
 */
abstract class Model
{
    /**
     * Attribute casts. Date fields are unix timestamps and are cast to int.
     * They are intentionally not converted to DateTime objects, because the
     * API uses an unusual timezone handling for these values.
     *
     * @var array<string, 'int'|'float'|'bool'>
     */
    protected array $casts = [];

    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(private array $attributes = [])
    {
        $this->castAttributes();
    }

    public function __get(string $name): mixed
    {
        return $this->attributes[$name] ?? null;
    }

    public function __isset(string $name): bool
    {
        return isset($this->attributes[$name]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->attributes;
    }

    private function castAttributes(): void
    {
        foreach ($this->casts as $key => $type) {
            $value = $this->attributes[$key] ?? null;

            if ($value === null || $value === '') {
                continue;
            }

            $this->attributes[$key] = match ($type) {
                'int' => (int) $value,
                'float' => (float) $value,
                'bool' => (bool) $value,
            };
        }
    }
}
