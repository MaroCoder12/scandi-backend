<?php
require_once 'Attribute.php';

class CapacityAttribute extends Attribute {
    private $unit;
    private $numericValue;
    
    public function __construct($id, $name, $value, $displayValue = null) {
        parent::__construct($id, $name, $value, 'text', $displayValue);
        $this->parseCapacity();
    }

    public function getAdditionalPrice() {
        // Higher capacity typically costs more
        $priceMap = [
            64 => 0,
            128 => 50,
            256 => 100,
            512 => 200,
            1024 => 400, // 1TB
            2048 => 800  // 2TB
        ];
        
        return $priceMap[$this->numericValue] ?? 0;
    }

    public function getDisplayFormat() {
        return [
            'type' => 'button_group',
            'style' => 'capacity-selector',
            'options' => $this->getAvailableCapacities(),
            'show_price_diff' => true
        ];
    }

    public function validate($value) {
        $validFormats = [
            '/^\d+(GB|TB)$/',  // 256GB, 1TB
            '/^\d+G$/',        // 256G
            '/^\d+T$/'         // 1T
        ];
        
        foreach ($validFormats as $pattern) {
            if (preg_match($pattern, $value)) {
                return true;
            }
        }
        
        return false;
    }

    public function getValidationRules() {
        return [
            'required' => true,
            'format' => 'capacity_format'
        ];
    }

    private function parseCapacity() {
        $value = $this->value;
        
        // Extract numeric value and unit
        if (preg_match('/(\d+)(GB|G|TB|T)/', $value, $matches)) {
            $this->numericValue = intval($matches[1]);
            $this->unit = $matches[2];
            
            // Convert to GB for standardization
            if (in_array($this->unit, ['TB', 'T'])) {
                $this->numericValue *= 1024;
                $this->unit = 'GB';
            }
        }
    }

    public function getNumericValue() {
        return $this->numericValue;
    }

    public function getUnit() {
        return $this->unit;
    }

    public function getFormattedCapacity() {
        if ($this->numericValue >= 1024) {
            $tb = $this->numericValue / 1024;
            return $tb . 'TB';
        }
        return $this->numericValue . 'GB';
    }

    public function getAvailableCapacities() {
        return ['64GB', '128GB', '256GB', '512GB', '1TB', '2TB'];
    }

    public function getStorageInfo() {
        return [
            'type' => $this->determineStorageType(),
            'speed' => $this->getStorageSpeed(),
            'description' => $this->getCapacityDescription()
        ];
    }

    private function determineStorageType() {
        // This could be enhanced to detect SSD vs HDD based on product type
        return 'SSD'; // Default to SSD for modern devices
    }

    private function getStorageSpeed() {
        $speedMap = [
            64 => 'Standard',
            128 => 'Standard',
            256 => 'Fast',
            512 => 'Fast',
            1024 => 'Ultra Fast',
            2048 => 'Ultra Fast'
        ];
        
        return $speedMap[$this->numericValue] ?? 'Standard';
    }

    private function getCapacityDescription() {
        $descriptions = [
            64 => 'Perfect for basic usage and essential apps',
            128 => 'Good for moderate usage with some media storage',
            256 => 'Ideal for most users with plenty of space for apps and media',
            512 => 'Great for power users with extensive media libraries',
            1024 => 'Excellent for professionals and content creators',
            2048 => 'Maximum storage for the most demanding users'
        ];
        
        return $descriptions[$this->numericValue] ?? 'Ample storage space';
    }

    public function getRecommendedUsage() {
        if ($this->numericValue <= 128) {
            return 'Basic usage, email, web browsing';
        } elseif ($this->numericValue <= 256) {
            return 'Standard usage, apps, photos, music';
        } elseif ($this->numericValue <= 512) {
            return 'Heavy usage, games, videos, work files';
        } else {
            return 'Professional usage, large media files, extensive libraries';
        }
    }
}
?>
