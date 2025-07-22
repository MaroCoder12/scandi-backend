<?php
require_once 'Attribute.php';
require_once 'SizeAttribute.php';
require_once 'ColorAttribute.php';
require_once 'CapacityAttribute.php';

class AttributeFactory {
    
    public static function createAttribute($id, $name, $value, $type = null, $displayValue = null) {
        // Determine attribute type based on name if not provided
        if (!$type) {
            $type = self::determineTypeFromName($name);
        }
        
        switch (strtolower($type)) {
            case 'size':
                return new SizeAttribute($id, $name, $value, $displayValue);
            
            case 'color':
            case 'colour':
                return new ColorAttribute($id, $name, $value, $displayValue);
            
            case 'capacity':
            case 'storage':
                return new CapacityAttribute($id, $name, $value, $displayValue);
            
            default:
                return new GenericAttribute($id, $name, $value, $type, $displayValue);
        }
    }
    
    private static function determineTypeFromName($name) {
        $name = strtolower($name);
        
        if (in_array($name, ['size', 'sizes'])) {
            return 'size';
        }
        
        if (in_array($name, ['color', 'colour', 'colors', 'colours'])) {
            return 'color';
        }
        
        if (in_array($name, ['capacity', 'storage', 'memory'])) {
            return 'capacity';
        }
        
        return 'text';
    }
    
    public static function getAttributeTypes() {
        return [
            'size' => 'Size/Dimension attributes',
            'color' => 'Color/Appearance attributes',
            'capacity' => 'Storage/Capacity attributes',
            'text' => 'Generic text attributes',
            'boolean' => 'Yes/No attributes',
            'number' => 'Numeric attributes'
        ];
    }
    
    public static function validateAttributeData($data) {
        $errors = [];
        
        if (!isset($data['name']) || empty($data['name'])) {
            $errors[] = 'Attribute name is required';
        }
        
        if (!isset($data['value']) || empty($data['value'])) {
            $errors[] = 'Attribute value is required';
        }
        
        if (isset($data['type']) && !array_key_exists($data['type'], self::getAttributeTypes())) {
            $errors[] = 'Invalid attribute type';
        }
        
        return $errors;
    }
}

// Generic attribute class for attributes that don't need special handling
class GenericAttribute extends Attribute {
    
    public function getAdditionalPrice() {
        return 0.00; // Generic attributes don't affect price
    }
    
    public function getDisplayFormat() {
        switch ($this->type) {
            case 'boolean':
                return [
                    'type' => 'checkbox',
                    'style' => 'toggle'
                ];
            
            case 'number':
                return [
                    'type' => 'input',
                    'input_type' => 'number',
                    'style' => 'number-input'
                ];
            
            default:
                return [
                    'type' => 'text',
                    'style' => 'text-display'
                ];
        }
    }
    
    public function validate($value) {
        switch ($this->type) {
            case 'boolean':
                return in_array(strtolower($value), ['true', 'false', '1', '0', 'yes', 'no']);
            
            case 'number':
                return is_numeric($value);
            
            default:
                return !empty($value);
        }
    }
    
    public function getValidationRules() {
        switch ($this->type) {
            case 'boolean':
                return ['type' => 'boolean'];
            
            case 'number':
                return ['type' => 'numeric'];
            
            default:
                return ['type' => 'string', 'min_length' => 1];
        }
    }
}
?>
