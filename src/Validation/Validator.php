<?php

declare(strict_types=1);

namespace RestApi\Validation;

/**
 * Simple input validator
 * 
 * Provides common validation rules for request data
 */
class Validator
{
    private array $errors = [];
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Validate required field
     */
    public function required(string $field, ?string $message = null): self
    {
        if (!isset($this->data[$field]) || $this->data[$field] === '' || $this->data[$field] === null) {
            $this->errors[$field][] = $message ?? "Field '{$field}' is required";
        }
        return $this;
    }

    /**
     * Validate email format
     */
    public function email(string $field, ?string $message = null): self
    {
        if (isset($this->data[$field]) && !filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field][] = $message ?? "Field '{$field}' must be a valid email";
        }
        return $this;
    }

    /**
     * Validate minimum length
     */
    public function minLength(string $field, int $min, ?string $message = null): self
    {
        if (isset($this->data[$field]) && strlen((string)$this->data[$field]) < $min) {
            $this->errors[$field][] = $message ?? "Field '{$field}' must be at least {$min} characters";
        }
        return $this;
    }

    /**
     * Validate maximum length
     */
    public function maxLength(string $field, int $max, ?string $message = null): self
    {
        if (isset($this->data[$field]) && strlen((string)$this->data[$field]) > $max) {
            $this->errors[$field][] = $message ?? "Field '{$field}' must not exceed {$max} characters";
        }
        return $this;
    }

    /**
     * Validate numeric value
     */
    public function numeric(string $field, ?string $message = null): self
    {
        if (isset($this->data[$field]) && !is_numeric($this->data[$field])) {
            $this->errors[$field][] = $message ?? "Field '{$field}' must be numeric";
        }
        return $this;
    }

    /**
     * Validate integer value
     */
    public function integer(string $field, ?string $message = null): self
    {
        if (isset($this->data[$field]) && filter_var($this->data[$field], FILTER_VALIDATE_INT) === false) {
            $this->errors[$field][] = $message ?? "Field '{$field}' must be an integer";
        }
        return $this;
    }

    /**
     * Validate value is in array
     */
    public function in(string $field, array $allowed, ?string $message = null): self
    {
        if (isset($this->data[$field]) && !in_array($this->data[$field], $allowed, true)) {
            $this->errors[$field][] = $message ?? "Field '{$field}' must be one of: " . implode(', ', $allowed);
        }
        return $this;
    }

    /**
     * Check if validation passed
     */
    public function isValid(): bool
    {
        return empty($this->errors);
    }

    /**
     * Get all validation errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get first error message
     */
    public function getFirstError(): ?string
    {
        foreach ($this->errors as $fieldErrors) {
            if (!empty($fieldErrors)) {
                return $fieldErrors[0];
            }
        }
        return null;
    }

    /**
     * Get all error messages as flat array
     */
    public function getErrorMessages(): array
    {
        $messages = [];
        foreach ($this->errors as $fieldErrors) {
            $messages = array_merge($messages, $fieldErrors);
        }
        return $messages;
    }
}
