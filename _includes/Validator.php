<?php
/**
 * Validator Helper Class
 * Provides validation utilities for form inputs
 */
class Validator {
    private $errors = [];
    
    /**
     * Validate required field
     */
    public function required($value, $fieldName) {
        if (empty(trim($value))) {
            $this->errors[$fieldName] = ucfirst($fieldName) . ' is required';
            return false;
        }
        return true;
    }
    
    /**
     * Validate email
     */
    public function email($value, $fieldName = 'email') {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$fieldName] = 'Invalid email format';
            return false;
        }
        return true;
    }
    
    /**
     * Validate minimum length
     */
    public function minLength($value, $length, $fieldName) {
        if (strlen($value) < $length) {
            $this->errors[$fieldName] = ucfirst($fieldName) . " must be at least {$length} characters";
            return false;
        }
        return true;
    }
    
    /**
     * Validate maximum length
     */
    public function maxLength($value, $length, $fieldName) {
        if (strlen($value) > $length) {
            $this->errors[$fieldName] = ucfirst($fieldName) . " must not exceed {$length} characters";
            return false;
        }
        return true;
    }
    
    /**
     * Validate numeric value
     */
    public function numeric($value, $fieldName) {
        if (!is_numeric($value)) {
            $this->errors[$fieldName] = ucfirst($fieldName) . ' must be a number';
            return false;
        }
        return true;
    }
    
    /**
     * Validate integer value
     */
    public function integer($value, $fieldName) {
        if (!filter_var($value, FILTER_VALIDATE_INT)) {
            $this->errors[$fieldName] = ucfirst($fieldName) . ' must be an integer';
            return false;
        }
        return true;
    }
    
    /**
     * Get all validation errors
     */
    public function getErrors() {
        return $this->errors;
    }
    
    /**
     * Get first validation error message
     */
    public function getFirstError() {
        return empty($this->errors) ? '' : reset($this->errors);
    }
    
    /**
     * Check if there are any errors
     */
    public function hasErrors() {
        return !empty($this->errors);
    }
    
    /**
     * Alias for hasErrors() - Laravel-like syntax
     */
    public function fails() {
        return $this->hasErrors();
    }
    
    /**
     * Check if validation passed (no errors)
     */
    public function passes() {
        return !$this->hasErrors();
    }
    
    /**
     * Clear all errors
     */
    public function clearErrors() {
        $this->errors = [];
    }
    
    /**
     * Add custom error
     */
    public function addError($fieldName, $message) {
        $this->errors[$fieldName] = $message;
    }
    
    /**
     * Sanitize string or array of strings
     */
    public static function sanitize($value) {
        // Handle arrays recursively
        if (is_array($value)) {
            return array_map([self::class, 'sanitize'], $value);
        }
        
        // Handle strings
        if (is_string($value)) {
            return htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES, 'UTF-8');
        }
        
        // Return other types as-is (null, int, float, etc.)
        return $value;
    }
    
    /**
     * Validate phone number (basic)
     */
    public function phone($value, $fieldName = 'phone') {
        if (!preg_match('/^[0-9]{10}$/', $value)) {
            $this->errors[$fieldName] = 'Invalid phone number format (10 digits required)';
            return false;
        }
        return true;
    }
}
