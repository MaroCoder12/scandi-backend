<?php
require_once 'AbstractProduct.php';
require_once 'Product.php';
require_once 'ClothingProduct.php';
require_once 'TechProduct.php';

class ProductFactory {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function createProduct($categoryId) {
        switch ($categoryId) {
            case 14: // Clothes category
                return new ClothingProduct($this->db);
            case 15: // Tech category
                return new TechProduct($this->db);
            default:
                return new Product($this->db); // General product for other categories
        }
    }
    
    public function getProductByIdWithType($productId) {
        // First, get the category of the product
        $sql = "SELECT category_id FROM products WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $productId, PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result) {
            return null;
        }
        
        $categoryId = $result['category_id'];
        $product = $this->createProduct($categoryId);
        
        return $product->getProductById($productId);
    }
    
    public function getAllProductsWithTypes() {
        $clothingProduct = new ClothingProduct($this->db);
        $techProduct = new TechProduct($this->db);
        $generalProduct = new Product($this->db);
        
        $allProducts = [];
        
        // Get clothing products
        $clothingProducts = $clothingProduct->getAllClothingProducts();
        foreach ($clothingProducts as $product) {
            $product['product_type'] = 'clothing';
            $allProducts[] = $product;
        }
        
        // Get tech products
        $techProducts = $techProduct->getAllTechProducts();
        foreach ($techProducts as $product) {
            $product['product_type'] = 'tech';
            $allProducts[] = $product;
        }
        
        // Get other products (if any)
        $sql = "SELECT p.*, pg.image_url, pr.amount 
                FROM products p
                LEFT JOIN product_gallery pg ON p.id = pg.product_id
                LEFT JOIN prices pr ON p.id = pr.product_id  
                WHERE p.category_id NOT IN (14, 15)";
        $stmt = $this->db->query($sql);
        $otherProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($otherProducts as $product) {
            $product['product_type'] = 'general';
            $allProducts[] = $product;
        }
        
        return $allProducts;
    }
    
    public function getProductsByCategory($categoryId) {
        $product = $this->createProduct($categoryId);
        
        switch ($categoryId) {
            case 14: // Clothes
                return $product->getAllClothingProducts();
            case 15: // Tech
                return $product->getAllTechProducts();
            default:
                return $product->getProductsByCategory($categoryId);
        }
    }
}
?>
