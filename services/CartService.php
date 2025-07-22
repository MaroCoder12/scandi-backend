<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Cart.php';

class CartService {
    private $db;
    private $cart;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->cart = new Cart($this->db);
    }
    
    public function validateCartItemData($data) {
        $errors = [];
        
        if (!isset($data['itemId']) || empty($data['itemId'])) {
            $errors[] = 'Item ID is required';
        }
        
        if (isset($data['quantityChange']) && !is_numeric($data['quantityChange'])) {
            $errors[] = 'Quantity change must be a number';
        }
        
        return $errors;
    }
    
    public function validateProductData($data) {
        $errors = [];
        
        if (!isset($data['productId']) || empty($data['productId'])) {
            $errors[] = 'Product ID is required';
        }
        
        if (!isset($data['quantity']) || !is_numeric($data['quantity']) || $data['quantity'] <= 0) {
            $errors[] = 'Quantity must be a positive number';
        }
        
        return $errors;
    }
    
    public function removeFromCart($itemId) {
        // Validate input
        $errors = $this->validateCartItemData(['itemId' => $itemId]);
        if (!empty($errors)) {
            throw new InvalidArgumentException(implode(', ', $errors));
        }
        
        // Check if cart item exists
        $stmt = $this->db->prepare("SELECT * FROM cart WHERE id = :id");
        $stmt->bindParam(':id', $itemId, PDO::PARAM_INT);
        $stmt->execute();
        $cartItem = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$cartItem) {
            throw new Exception("Cart item not found with ID: {$itemId}");
        }
        
        // Get product details before deletion
        $stmt = $this->db->prepare("
            SELECT p.*, pr.amount, pg.image_url
            FROM products p
            LEFT JOIN prices pr ON p.id = pr.product_id
            LEFT JOIN product_gallery pg ON p.id = pg.product_id
            WHERE p.id = :product_id
        ");
        $stmt->bindParam(':product_id', $cartItem['product_id'], PDO::PARAM_STR);
        $stmt->execute();
        $productDetails = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Delete the cart item
        $stmt = $this->db->prepare("DELETE FROM cart WHERE id = :id");
        $stmt->bindParam(':id', $itemId, PDO::PARAM_INT);
        $deleteResult = $stmt->execute();
        
        if (!$deleteResult) {
            throw new Exception("Failed to delete cart item");
        }
        
        // Return the deleted cart item data
        return [
            'id' => $cartItem['id'],
            'product' => [
                'id' => $productDetails['id'] ?? $cartItem['product_id'],
                'name' => $productDetails['name'] ?? 'Unknown Product',
                'price' => $productDetails['amount'] ?? 0,
                'image' => $productDetails['image_url'] ?? '',
            ],
            'quantity' => 0 // Set to 0 to indicate it was removed
        ];
    }
    
    public function addToCart($productId, $quantity) {
        // Validate input
        $errors = $this->validateProductData(['productId' => $productId, 'quantity' => $quantity]);
        if (!empty($errors)) {
            throw new InvalidArgumentException(implode(', ', $errors));
        }
        
        // Check if product exists
        $stmt = $this->db->prepare("SELECT * FROM products WHERE id = :id");
        $stmt->bindParam(':id', $productId, PDO::PARAM_STR);
        $stmt->execute();
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$product) {
            throw new Exception("Product not found");
        }
        
        return $this->cart->addToCart($productId, $quantity);
    }
    
    public function updateCartQuantity($itemId, $quantityChange) {
        // Validate input
        $errors = $this->validateCartItemData(['itemId' => $itemId, 'quantityChange' => $quantityChange]);
        if (!empty($errors)) {
            throw new InvalidArgumentException(implode(', ', $errors));
        }
        
        // Check if cart item exists
        $stmt = $this->db->prepare("SELECT * FROM cart WHERE id = :id");
        $stmt->bindParam(':id', $itemId, PDO::PARAM_INT);
        $stmt->execute();
        $cartItem = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$cartItem) {
            throw new Exception("Cart item not found with ID: {$itemId}");
        }
        
        $newQuantity = $cartItem['quantity'] + $quantityChange;
        
        if ($newQuantity <= 0) {
            // Remove item if quantity becomes 0 or negative
            return $this->removeFromCart($itemId);
        }
        
        // Update quantity
        $stmt = $this->db->prepare("UPDATE cart SET quantity = :quantity WHERE id = :id");
        $stmt->bindParam(':quantity', $newQuantity, PDO::PARAM_INT);
        $stmt->bindParam(':id', $itemId, PDO::PARAM_INT);
        $stmt->execute();
        
        // Return updated cart item
        return $this->getCartItemById($itemId);
    }
    
    public function getCartItemById($itemId) {
        $stmt = $this->db->prepare("
            SELECT c.*, p.name, p.brand, pr.amount, pg.image_url
            FROM cart c
            JOIN products p ON c.product_id = p.id
            LEFT JOIN prices pr ON p.id = pr.product_id
            LEFT JOIN product_gallery pg ON p.id = pg.product_id
            WHERE c.id = :id
        ");
        $stmt->bindParam(':id', $itemId, PDO::PARAM_INT);
        $stmt->execute();
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$item) {
            return null;
        }
        
        return [
            'id' => $item['id'],
            'product' => [
                'id' => $item['product_id'],
                'name' => $item['name'],
                'price' => $item['amount'],
                'image' => $item['image_url'],
            ],
            'quantity' => $item['quantity']
        ];
    }
    
    public function clearCart() {
        $stmt = $this->db->prepare("DELETE FROM cart");
        return $stmt->execute();
    }
}
?>
