<?php
declare(strict_types=1);

namespace Nexus\Security;

/**
 * Schema Validation Engine
 *
 * Evaluates business validation rules and formats structured 422 error envelopes.
 */
class Validator
{
    private array $data;
    private array $rules;
    private array $errors = [];

    public function __construct(array $data, array $rules)
    {
        $this->data = $data;
        $this->rules = $rules;
        $this->validate();
    }

    public static function make(array $data, array $rules): static
    {
        return new static($data, $rules);
    }

    public function passes(): bool
    {
        return empty($this->errors);
    }

    public function fails(): bool
    {
        return !$this->passes();
    }

    public function errors(): array
    {
        return $this->errors;
    }

    private function validate(): void
    {
        foreach ($this->rules as $field => $ruleSet) {
            $rules = is_string($ruleSet) ? explode('|', $ruleSet) : (array)$ruleSet;
            $value = $this->data[$field] ?? null;

            foreach ($rules as $rule) {
                [$ruleName, $param] = str_contains($rule, ':') ? explode(':', $rule, 2) : [$rule, null];

                match ($ruleName) {
                    'required' => $this->validateRequired($field, $value),
                    'email' => $this->validateEmail($field, $value),
                    'uuid' => $this->validateUuid($field, $value),
                    'string' => $this->validateString($field, $value),
                    'int', 'integer' => $this->validateInteger($field, $value),
                    'min' => $this->validateMin($field, $value, (int)$param),
                    'max' => $this->validateMax($field, $value, (int)$param),
                    'in' => $this->validateIn($field, $value, explode(',', (string)$param)),
                    'regex' => $this->validateRegex($field, $value, (string)$param),
                    default => null,
                };
            }
        }
    }

    private function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }

    private function validateRequired(string $field, mixed $value): void
    {
        if ($value === null || $value === '' || (is_array($value) && empty($value))) {
            $this->addError($field, "The {$field} field is required.");
        }
    }

    private function validateEmail(string $field, mixed $value): void
    {
        if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->addError($field, "The {$field} field must be a valid email address.");
        }
    }

    private function validateUuid(string $field, mixed $value): void
    {
        if ($value !== null && $value !== '') {
            $pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';
            if (!preg_match($pattern, (string)$value)) {
                $this->addError($field, "The {$field} field must be a valid UUID.");
            }
        }
    }

    private function validateString(string $field, mixed $value): void
    {
        if ($value !== null && !is_string($value)) {
            $this->addError($field, "The {$field} field must be a string.");
        }
    }

    private function validateInteger(string $field, mixed $value): void
    {
        if ($value !== null && !filter_var($value, FILTER_VALIDATE_INT)) {
            $this->addError($field, "The {$field} field must be an integer.");
        }
    }

    private function validateMin(string $field, mixed $value, int $min): void
    {
        if ($value !== null) {
            $length = is_string($value) ? mb_strlen($value) : (is_numeric($value) ? (int)$value : count((array)$value));
            if ($length < $min) {
                $this->addError($field, "The {$field} field must be at least {$min}.");
            }
        }
    }

    private function validateMax(string $field, mixed $value, int $max): void
    {
        if ($value !== null) {
            $length = is_string($value) ? mb_strlen($value) : (is_numeric($value) ? (int)$value : count((array)$value));
            if ($length > $max) {
                $this->addError($field, "The {$field} field may not be greater than {$max}.");
            }
        }
    }

    private function validateIn(string $field, mixed $value, array $allowed): void
    {
        if ($value !== null && !in_array((string)$value, $allowed, true)) {
            $allowedList = implode(', ', $allowed);
            $this->addError($field, "The selected {$field} is invalid. Allowed: {$allowedList}.");
        }
    }

    private function validateRegex(string $field, mixed $value, string $pattern): void
    {
        if ($value !== null && !preg_match($pattern, (string)$value)) {
            $this->addError($field, "The {$field} format is invalid.");
        }
    }
}

