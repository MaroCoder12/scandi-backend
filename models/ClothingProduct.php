<?php
require_once 'AbstractProduct.php';

class ClothingProduct extends AbstractProduct {
    private $size;
    private $color;
    private $material;
    
    public function __construct($db) {
        parent::__construct($db);
    }

    public function create($name, $brand, $categoryId) {
        $sql = "INSERT INTO products (name, brand, category_id) VALUES (:name, :brand, :category_id)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':name', $name, PDO::PARAM_STR);
        $stmt->bindParam(':brand', $brand, PDO::PARAM_STR);
        $stmt->bindParam(':category_id', $categoryId, PDO::PARAM_STR);
        $stmt->execute();
    }

    public function update($id, $name, $brand, $categoryId) {
        $sql = "UPDATE products SET name = :name, brand = :brand, category_id = :category_id WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':name', $name, PDO::PARAM_STR);
        $stmt->bindParam(':brand', $brand, PDO::PARAM_STR);
        $stmt->bindParam(':category_id', $categoryId, PDO::PARAM_STR);
        $stmt->bindParam(':id', $id, PDO::PARAM_STR);
        $stmt->execute();
    }

    public function delete($id) {
        $sql = "DELETE FROM products WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_STR);
        $stmt->execute();
    }

    // Clothing-specific methods
    public function getAvailableSizes($productId) {
        $sql = "SELECT DISTINCT ai.value 
                FROM attributes a 
                JOIN attribute_items ai ON a.id = ai.attribute_id 
                WHERE a.product_id = :product_id AND a.name = 'Size'";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':product_id', $productId, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getAvailableColors($productId) {
        $sql = "SELECT DISTINCT ai.value 
                FROM attributes a 
                JOIN attribute_items ai ON a.id = ai.attribute_id 
                WHERE a.product_id = :product_id AND a.name = 'Color'";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':product_id', $productId, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getSizeGuide($productId) {
        // Return size guide information specific to clothing
        return [
            'XS' => ['chest' => '32-34', 'waist' => '26-28'],
            'S' => ['chest' => '34-36', 'waist' => '28-30'],
            'M' => ['chest' => '36-38', 'waist' => '30-32'],
            'L' => ['chest' => '38-40', 'waist' => '32-34'],
            'XL' => ['chest' => '40-42', 'waist' => '34-36']
        ];
    }

    public function getCareInstructions() {
        return [
            'Machine wash cold',
            'Do not bleach',
            'Tumble dry low',
            'Iron on low heat'
        ];
    }

    // Override to include clothing-specific attributes
    public function getProductById($id) {
        $sql = "SELECT p.*, pg.image_url, pr.amount 
                FROM products p
                LEFT JOIN product_gallery pg ON p.id = pg.product_id
                LEFT JOIN prices pr ON p.id = pr.product_id  
                WHERE p.id = :id AND p.category_id = 14"; // 14 is clothes category
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_STR);
        $stmt->execute();
        
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($product) {
            $product['available_sizes'] = $this->getAvailableSizes($id);
            $product['available_colors'] = $this->getAvailableColors($id);
            $product['size_guide'] = $this->getSizeGuide($id);
            $product['care_instructions'] = $this->getCareInstructions();
        }
        
        return $product;
    }

    public function getAllClothingProducts() {
        $sql = "SELECT p.*, pg.image_url, pr.amount 
                FROM products p
                LEFT JOIN product_gallery pg ON p.id = pg.product_id
                LEFT JOIN prices pr ON p.id = pr.product_id  
                WHERE p.category_id = 14"; // 14 is clothes category
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
