<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/CartService.php';

class OrderService {
    private $db;
    private $order;
    private $cartService;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->order = new Order();
        $this->cartService = new CartService();
    }
    
    public function validateOrderData($data = []) {
        $errors = [];
        
        // Check if cart is not empty
        $stmt = $this->db->query("SELECT COUNT(*) as count FROM cart");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] == 0) {
            $errors[] = 'Cart is empty. Cannot place order.';
        }
        
        // Additional validation can be added here
        // For example: customer information, payment details, etc.
        
        return $errors;
    }
    
    public function placeOrder($customerData = []) {
        try {
            // Validate order data
            $errors = $this->validateOrderData($customerData);
            if (!empty($errors)) {
                throw new InvalidArgumentException(implode(', ', $errors));
            }
            
            // Begin transaction
            $this->db->beginTransaction();
            
            // Get cart items before clearing
            $stmt = $this->db->query("
                SELECT c.*, p.name, p.brand, pr.amount
                FROM cart c
                JOIN products p ON c.product_id = p.id
                LEFT JOIN prices pr ON p.id = pr.product_id
            ");
            $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($cartItems)) {
                throw new Exception('Cart is empty');
            }
            
            // Calculate total
            $total = 0;
            foreach ($cartItems as $item) {
                $total += $item['amount'] * $item['quantity'];
            }
            
            // Create order record
            $customerName = $customerData['name'] ?? 'Guest Customer';
            $stmt = $this->db->prepare("
                INSERT INTO orders (customer_name, total_amount, status, created_at) 
                VALUES (:customer_name, :total_amount, :status, NOW())
            ");
            
            // Add total_amount and status columns if they don't exist
            try {
                $stmt->execute([
                    'customer_name' => $customerName,
                    'total_amount' => $total,
                    'status' => 'pending'
                ]);
            } catch (PDOException $e) {
                // If columns don't exist, use simpler insert
                $stmt = $this->db->prepare("INSERT INTO orders (customer_name) VALUES (:customer_name)");
                $stmt->execute(['customer_name' => $customerName]);
            }
            
            $orderId = $this->db->lastInsertId();
            
            // Create order items (if order_items table exists)
            try {
                foreach ($cartItems as $item) {
                    $stmt = $this->db->prepare("
                        INSERT INTO order_items (order_id, product_id, quantity, price) 
                        VALUES (:order_id, :product_id, :quantity, :price)
                    ");
                    $stmt->execute([
                        'order_id' => $orderId,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'price' => $item['amount']
                    ]);
                }
            } catch (PDOException $e) {
                // If order_items table doesn't exist, skip this step
                error_log("Order items table not found: " . $e->getMessage());
            }
            
            // Clear the cart
            $this->cartService->clearCart();
            
            // Commit transaction
            $this->db->commit();
            
            return [
                'success' => true,
                'message' => 'Order placed successfully!',
                'order_id' => $orderId,
                'total_amount' => $total,
                'items_count' => count($cartItems)
            ];
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $this->db->rollBack();
            
            return [
                'success' => false,
                'message' => 'Failed to place order: ' . $e->getMessage()
            ];
        }
    }
    
    public function getOrderById($orderId) {
        $errors = [];
        
        if (!$orderId || !is_numeric($orderId)) {
            $errors[] = 'Valid order ID is required';
        }
        
        if (!empty($errors)) {
            throw new InvalidArgumentException(implode(', ', $errors));
        }
        
        $stmt = $this->db->prepare("SELECT * FROM orders WHERE id = :id");
        $stmt->bindParam(':id', $orderId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function getOrderHistory($limit = 10) {
        $stmt = $this->db->prepare("
            SELECT * FROM orders 
            ORDER BY created_at DESC 
            LIMIT :limit
        ");
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
