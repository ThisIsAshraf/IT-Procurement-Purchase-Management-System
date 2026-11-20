<?php

declare(strict_types=1);

namespace App\Core;

final class Validator
{
    private array $errors = [];

    public function required(string $field, mixed $value, string $label): self
    {
        if ($value === null || trim((string)$value) === '') {
            $this->errors[$field] = "{$label} is required.";
        }
        return $this;
    }

    public function email(string $field, mixed $value, string $label): self
    {
        $val = trim((string)$value);
        if ($val !== '' && !filter_var($val, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = "{$label} must be a valid email address.";
        }
        return $this;
    }

    public function minLength(string $field, mixed $value, int $min, string $label): self
    {
        $val = (string)$value;
        if ($val !== '' && mb_strlen($val) < $min) {
            $this->errors[$field] = "{$label} must be at least {$min} characters.";
        }
        return $this;
    }

    public function maxLength(string $field, mixed $value, int $max, string $label): self
    {
        $val = (string)$value;
        if (mb_strlen($val) > $max) {
            $this->errors[$field] = "{$label} must not exceed {$max} characters.";
        }
        return $this;
    }

    public function positiveNumber(string $field, mixed $value, string $label): self
    {
        if (!is_numeric($value) || (float)$value <= 0) {
            $this->errors[$field] = "{$label} must be greater than zero.";
        }
        return $this;
    }

    public function nonNegativeNumber(string $field, mixed $value, string $label): self
    {
        if (!is_numeric($value) || (float)$value < 0) {
            $this->errors[$field] = "{$label} cannot be negative.";
        }
        return $this;
    }

    public function inList(string $field, mixed $value, array $allowed, string $label): self
    {
        if ($value !== null && $value !== '' && !in_array($value, $allowed, true)) {
            $this->errors[$field] = "Invalid selection for {$label}.";
        }
        return $this;
    }

    public function fails(): bool
    {
        return !empty($this->errors);
    }

    public function firstError(): ?string
    {
        return $this->errors ? reset($this->errors) : null;
    }

    public function errors(): array
    {
        return $this->errors;
    }
}
