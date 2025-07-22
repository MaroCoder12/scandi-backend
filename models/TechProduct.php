<?php
require_once 'AbstractProduct.php';

class TechProduct extends AbstractProduct {
    private $capacity;
    private $color;
    private $specifications;
    
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

    // Tech-specific methods
    public function getAvailableCapacities($productId) {
        $sql = "SELECT DISTINCT ai.value 
                FROM attributes a 
                JOIN attribute_items ai ON a.id = ai.attribute_id 
                WHERE a.product_id = :product_id AND a.name = 'Capacity'";
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

    public function getTechnicalSpecs($productId) {
        // Return technical specifications specific to tech products
        $sql = "SELECT a.name, ai.value 
                FROM attributes a 
                JOIN attribute_items ai ON a.id = ai.attribute_id 
                WHERE a.product_id = :product_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':product_id', $productId, PDO::PARAM_STR);
        $stmt->execute();
        
        $specs = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $specs[$row['name']][] = $row['value'];
        }
        
        return $specs;
    }

    public function getWarrantyInfo($productId) {
        // Return warranty information for tech products
        return [
            'duration' => '1 year',
            'type' => 'Limited warranty',
            'coverage' => 'Manufacturing defects',
            'support' => '24/7 customer support'
        ];
    }

    public function getCompatibility($productId) {
        // Return compatibility information for tech products
        return [
            'operating_systems' => ['iOS', 'Android', 'Windows', 'macOS'],
            'connectivity' => ['Bluetooth 5.0', 'Wi-Fi', 'USB-C'],
            'requirements' => 'Compatible with most modern devices'
        ];
    }

    // Override to include tech-specific attributes
    public function getProductById($id) {
        $sql = "SELECT p.*, pg.image_url, pr.amount 
                FROM products p
                LEFT JOIN product_gallery pg ON p.id = pg.product_id
                LEFT JOIN prices pr ON p.id = pr.product_id  
                WHERE p.id = :id AND p.category_id = 15"; // 15 is tech category
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_STR);
        $stmt->execute();
        
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($product) {
            $product['available_capacities'] = $this->getAvailableCapacities($id);
            $product['available_colors'] = $this->getAvailableColors($id);
            $product['technical_specs'] = $this->getTechnicalSpecs($id);
            $product['warranty_info'] = $this->getWarrantyInfo($id);
            $product['compatibility'] = $this->getCompatibility($id);
        }
        
        return $product;
    }

    public function getAllTechProducts() {
        $sql = "SELECT p.*, pg.image_url, pr.amount 
                FROM products p
                LEFT JOIN product_gallery pg ON p.id = pg.product_id
                LEFT JOIN prices pr ON p.id = pr.product_id  
                WHERE p.category_id = 15"; // 15 is tech category
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
