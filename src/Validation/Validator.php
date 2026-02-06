<?php

namespace Redium\Validation;

class Validator
{
    private array $errors = [];
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Validate data against rules
     * 
     * @param array $rules ['field' => 'required|email|min:3']
     * @return bool
     */
    public function validate(array $rules): bool
    {
        $this->errors = [];

        foreach ($rules as $field => $ruleString) {
            $fieldRules = explode('|', $ruleString);
            
            foreach ($fieldRules as $rule) {
                $this->applyRule($field, $rule);
            }
        }

        return empty($this->errors);
    }

    /**
     * Apply a single validation rule
     */
    private function applyRule(string $field, string $rule): void
    {
        $value = $this->data[$field] ?? null;

        // Parse rule with parameters (e.g., "min:3")
        $parts = explode(':', $rule);
        $ruleName = $parts[0];
        $params = $parts[1] ?? null;

        switch ($ruleName) {
            case 'required':
                if (empty($value) && $value !== '0' && $value !== 0) {
                    $this->addError($field, "The {$field} field is required");
                }
                break;

            case 'email':
                if ($value && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->addError($field, "The {$field} must be a valid email address");
                }
                break;

            case 'min':
                if ($value && strlen($value) < (int)$params) {
                    $this->addError($field, "The {$field} must be at least {$params} characters");
                }
                break;

            case 'max':
                if ($value && strlen($value) > (int)$params) {
                    $this->addError($field, "The {$field} must not exceed {$params} characters");
                }
                break;

            case 'numeric':
                if ($value && !is_numeric($value)) {
                    $this->addError($field, "The {$field} must be a number");
                }
                break;

            case 'alpha':
                if ($value && !ctype_alpha($value)) {
                    $this->addError($field, "The {$field} must contain only letters");
                }
                break;

            case 'alphanumeric':
                if ($value && !ctype_alnum($value)) {
                    $this->addError($field, "The {$field} must contain only letters and numbers");
                }
                break;

            case 'url':
                if ($value && !filter_var($value, FILTER_VALIDATE_URL)) {
                    $this->addError($field, "The {$field} must be a valid URL");
                }
                break;

            case 'in':
                $allowedValues = explode(',', $params);
                if ($value && !in_array($value, $allowedValues)) {
                    $this->addError($field, "The {$field} must be one of: " . implode(', ', $allowedValues));
                }
                break;

            case 'unique':
                // For database uniqueness check - requires model
                // Format: unique:table,column
                if ($params) {
                    [$table, $column] = explode(',', $params);
                    if ($this->checkUnique($table, $column, $value)) {
                        $this->addError($field, "The {$field} has already been taken");
                    }
                }
                break;
        }
    }

    /**
     * Check if the value is unique in database
     */
    private function checkUnique(string $table, string $column, $value): bool
    {
        if (!$value) return false;

        try {
            $pdo = \Redium\Database\Connection::getPdo();
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM {$table} WHERE {$column} = ?");
            $stmt->execute([$value]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $result['count'] > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Add validation error
     */
    private function addError(string $field, string $message): void
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        $this->errors[$field][] = $message;
    }

    /**
     * Get all validation errors
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Get first error for a field
     */
    public function firstError(string $field): ?string
    {
        return $this->errors[$field][0] ?? null;
    }

    /**
     * Check if validation failed
     */
    public function fails(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Throw exception if validation fails
     */
    public function validateOrFail(array $rules): void
    {
        if (!$this->validate($rules)) {
            throw new ValidationException($this->errors);
        }
    }

    /**
     * Static helper for quick validation
     */
    public static function make(array $data, array $rules): self
    {
        $validator = new self($data);
        $validator->validate($rules);
        return $validator;
    }
}
