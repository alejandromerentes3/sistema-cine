<?php
namespace App\Services;

use App\Core\AppLogger;

class ValidationService {
    private static $logger;

    public static function init() {
        if (!self::$logger) {
            self::$logger = AppLogger::getLogger('validation');
        }
    }

    public static function validate(array $data, array $rules): array {
        self::init();
        
        $errors = [];
        
        foreach ($rules as $field => $ruleString) {
            $value = $data[$field] ?? null;
            $rules = explode('|', $ruleString);
            
            foreach ($rules as $rule) {
                if ($rule === 'required' && (is_null($value) || $value === '')) {
                    $errors[$field][] = "El campo $field es requerido";
                    continue;
                }
                
                if ($rule === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[$field][] = "El campo $field debe ser un email válido";
                    continue;
                }
                
                if (strpos($rule, 'min:') === 0) {
                    $min = (int) str_replace('min:', '', $rule);
                    if (strlen($value) < $min) {
                        $errors[$field][] = "El campo $field debe tener al menos $min caracteres";
                        continue;
                    }
                }
                
                if (strpos($rule, 'max:') === 0) {
                    $max = (int) str_replace('max:', '', $rule);
                    if (strlen($value) > $max) {
                        $errors[$field][] = "El campo $field no puede tener más de $max caracteres";
                        continue;
                    }
                }
                
                if ($rule === 'same:password' && $value !== ($data['password'] ?? null)) {
                    $errors[$field][] = "Las contraseñas no coinciden";
                    continue;
                }
            }
        }
        
        $result = [
            'success' => empty($errors),
            'errors' => $errors,
            'firstError' => self::getFirstError($errors)
        ];
        
        if (!$result['success']) {
            self::$logger->warning('Validation failed', ['errors' => $errors]);
        }
        
        return $result;
    }

    public static function validatePasswordSecurity($password): array {
        self::init();
        
        $errors = [];
        
        if (strlen($password) < 8) {
            $errors[] = "La contraseña debe tener al menos 8 caracteres";
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = "La contraseña debe contener al menos una letra mayúscula";
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = "La contraseña debe contener al menos una letra minúscula";
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = "La contraseña debe contener al menos un número";
        }
        
        $result = [
            'success' => empty($errors),
            'message' => empty($errors) ? 'Password is secure' : implode(', ', $errors)
        ];
        
        if (!$result['success']) {
            self::$logger->warning('Password validation failed', ['errors' => $errors]);
        }
        
        return $result;
    }

    public static function audit($action, $table, $recordId, $data = []) {
        self::init();
        self::$logger->info('Audit log', [
            'action' => $action,
            'table' => $table,
            'record_id' => $recordId,
            'data' => $data
        ]);
    }

    private static function getFirstError($errors) {
        foreach ($errors as $fieldErrors) {
            if (!empty($fieldErrors)) {
                return $fieldErrors[0];
            }
        }
        return null;
    }
}
