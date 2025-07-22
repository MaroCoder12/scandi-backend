<?php
require_once 'AbstractCategory.php';

class TechCategory extends AbstractCategory {
    
    public function getDisplayName() {
        return 'Technology & Electronics';
    }

    public function getDescription() {
        return 'Explore cutting-edge technology and electronic devices';
    }

    public function getFilterOptions() {
        return [
            'brand' => [
                'label' => 'Brand',
                'type' => 'checkbox',
                'options' => $this->getAvailableBrands()
            ],
            'capacity' => [
                'label' => 'Storage Capacity',
                'type' => 'checkbox',
                'options' => $this->getAvailableCapacities()
            ],
            'color' => [
                'label' => 'Color',
                'type' => 'color',
                'options' => $this->getAvailableColors()
            ],
            'price' => [
                'label' => 'Price Range',
                'type' => 'range',
                'min' => 0,
                'max' => 2000
            ],
            'features' => [
                'label' => 'Features',
                'type' => 'checkbox',
                'options' => ['Wireless', 'Bluetooth', 'USB-C', 'Touch ID', 'Face ID']
            ]
        ];
    }

    public function getSortOptions() {
        return [
            'name_asc' => 'Name (A-Z)',
            'name_desc' => 'Name (Z-A)',
            'price_asc' => 'Price (Low to High)',
            'price_desc' => 'Price (High to Low)',
            'newest' => 'Latest Models',
            'rating' => 'Highest Rated',
            'popularity' => 'Best Sellers'
        ];
    }

    public function getAvailableColors() {
        $sql = "SELECT DISTINCT ai.value 
                FROM attributes a 
                JOIN attribute_items ai ON a.id = ai.attribute_id 
                JOIN products p ON a.product_id = p.id
                WHERE a.name = 'Color' AND p.category_id = 15";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getAvailableCapacities() {
        $sql = "SELECT DISTINCT ai.value 
                FROM attributes a 
                JOIN attribute_items ai ON a.id = ai.attribute_id 
                JOIN products p ON a.product_id = p.id
                WHERE a.name = 'Capacity' AND p.category_id = 15";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getAvailableBrands() {
        $sql = "SELECT DISTINCT brand FROM products WHERE category_id = 15 AND brand IS NOT NULL";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getTechSpecs() {
        return [
            'connectivity' => ['Bluetooth 5.0', 'Wi-Fi 6', 'USB-C', '5G'],
            'compatibility' => ['iOS', 'Android', 'Windows', 'macOS'],
            'warranty' => ['1 Year Limited Warranty', 'Extended Warranty Available'],
            'support' => ['24/7 Customer Support', 'Online Documentation', 'Video Tutorials']
        ];
    }

    public function getCompatibilityInfo() {
        return [
            'operating_systems' => [
                'iOS' => 'iOS 14.0 or later',
                'Android' => 'Android 8.0 or later',
                'Windows' => 'Windows 10 or later',
                'macOS' => 'macOS 10.15 or later'
            ],
            'connectivity' => [
                'Bluetooth' => 'Bluetooth 5.0 or later',
                'Wi-Fi' => '802.11ac or later',
                'USB' => 'USB 3.0 or USB-C'
            ]
        ];
    }

    public function getWarrantyInfo() {
        return [
            'standard' => [
                'duration' => '1 Year',
                'coverage' => 'Manufacturing defects',
                'support' => 'Phone and online support'
            ],
            'extended' => [
                'duration' => '2-3 Years',
                'coverage' => 'Accidental damage protection',
                'support' => 'Priority support and repair'
            ]
        ];
    }
}
?>
