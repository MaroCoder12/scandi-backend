<?php
require_once 'AbstractCategory.php';

class ClothesCategory extends AbstractCategory {
    
    public function getDisplayName() {
        return 'Clothing & Fashion';
    }

    public function getDescription() {
        return 'Discover our latest collection of clothing and fashion accessories';
    }

    public function getFilterOptions() {
        return [
            'size' => [
                'label' => 'Size',
                'type' => 'checkbox',
                'options' => ['XS', 'S', 'M', 'L', 'XL', 'XXL']
            ],
            'color' => [
                'label' => 'Color',
                'type' => 'color',
                'options' => $this->getAvailableColors()
            ],
            'brand' => [
                'label' => 'Brand',
                'type' => 'checkbox',
                'options' => $this->getAvailableBrands()
            ],
            'price' => [
                'label' => 'Price Range',
                'type' => 'range',
                'min' => 0,
                'max' => 1000
            ]
        ];
    }

    public function getSortOptions() {
        return [
            'name_asc' => 'Name (A-Z)',
            'name_desc' => 'Name (Z-A)',
            'price_asc' => 'Price (Low to High)',
            'price_desc' => 'Price (High to Low)',
            'newest' => 'Newest First',
            'popularity' => 'Most Popular'
        ];
    }

    public function getAvailableColors() {
        $sql = "SELECT DISTINCT ai.value 
                FROM attributes a 
                JOIN attribute_items ai ON a.id = ai.attribute_id 
                JOIN products p ON a.product_id = p.id
                WHERE a.name = 'Color' AND p.category_id = 14";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getAvailableBrands() {
        $sql = "SELECT DISTINCT brand FROM products WHERE category_id = 14 AND brand IS NOT NULL";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getSizeChart() {
        return [
            'XS' => ['chest' => '32-34"', 'waist' => '26-28"', 'hips' => '34-36"'],
            'S' => ['chest' => '34-36"', 'waist' => '28-30"', 'hips' => '36-38"'],
            'M' => ['chest' => '36-38"', 'waist' => '30-32"', 'hips' => '38-40"'],
            'L' => ['chest' => '38-40"', 'waist' => '32-34"', 'hips' => '40-42"'],
            'XL' => ['chest' => '40-42"', 'waist' => '34-36"', 'hips' => '42-44"'],
            'XXL' => ['chest' => '42-44"', 'waist' => '36-38"', 'hips' => '44-46"']
        ];
    }

    public function getCareInstructions() {
        return [
            'general' => [
                'Machine wash cold with like colors',
                'Do not bleach',
                'Tumble dry low heat',
                'Iron on low temperature if needed'
            ],
            'delicate' => [
                'Hand wash in cold water',
                'Do not wring or twist',
                'Lay flat to dry',
                'Steam or iron on low heat'
            ]
        ];
    }

    public function getSeasonalCollections() {
        return [
            'spring' => 'Spring Collection',
            'summer' => 'Summer Collection',
            'fall' => 'Fall Collection',
            'winter' => 'Winter Collection'
        ];
    }
}
?>
