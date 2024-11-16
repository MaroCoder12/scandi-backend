<?php

class Cart {
    private $conn;
    private $table = 'cart';

    public function __construct($db) {
        $this->conn = $db;
    }

    // Add an item to the cart
    public function addToCart($userId, $productId, $quantity, $price) {
        // Check if the product already exists in the cart for the user
        $query = "SELECT * FROM {$this->table} WHERE user_id = :user_id AND product_id = :product_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':product_id', $productId, PDO::PARAM_STR);
        $stmt->execute();
        $existingItem = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existingItem) {
            // Update the quantity if the product exists
            $newQuantity = $existingItem['quantity'] + $quantity;
            return $this->updateCartItem($userId, $productId, $newQuantity);
        } else {
            // Insert a new cart item
            $query = "INSERT INTO {$this->table} (user_id, product_id, quantity, price) 
                      VALUES (:user_id, :product_id, :quantity, :price)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':product_id', $productId, PDO::PARAM_STR);
            $stmt->bindParam(':quantity', $quantity, PDO::PARAM_INT);
            $stmt->bindParam(':price', $price, PDO::PARAM_STR);
            return $stmt->execute();
        }
    }

    // Update the quantity of a cart item
    public function updateCartItem($userId, $productId, $quantity) {
        if ($quantity <= 0) {
            // Remove the item from the cart if quantity is 0 or less
            return $this->removeFromCart($userId, $productId);
        }

        $query = "UPDATE {$this->table} 
                  SET quantity = :quantity 
                  WHERE user_id = :user_id AND product_id = :product_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':quantity', $quantity, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':product_id', $productId, PDO::PARAM_STR);
        return $stmt->execute();
    }

    // Remove an item from the cart
    public function removeFromCart($userId, $productId) {
        $query = "DELETE FROM {$this->table} WHERE user_id = :user_id AND product_id = :product_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':product_id', $productId, PDO::PARAM_STR);
        return $stmt->execute();
    }

    // Clear the cart for a user
    public function clearCart($userId) {
        $query = "DELETE FROM {$this->table} WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // Get all cart items for a user
    public function getCartItems($userId) {
        $query = "SELECT 
                    c.id AS cart_id, 
                    c.product_id, 
                    p.name AS product_name, 
                    p.description AS product_description, 
                    c.quantity, 
                    c.price, 
                    c.quantity * c.price AS total_price, 
                    c.added_at 
                  FROM {$this->table} c
                  JOIN products p ON c.product_id = p.id
                  WHERE c.user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
