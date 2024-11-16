<?php
require_once __DIR__ . '/../config/database.php';

class Order {
    private $db;

    public function __construct() {
        $this->db = (new Database())->getConnection();
    }

    public function createOrder($customerName, $productIds) {
        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare("INSERT INTO orders (customer_name) VALUES (:customer_name)");
            $stmt->execute(['customer_name' => $customerName]);
            $orderId = $this->db->lastInsertId();

            foreach ($productIds as $productId) {
                $stmt = $this->db->prepare("INSERT INTO order_products (order_id, product_id) VALUES (:order_id, :product_id)");
                $stmt->execute(['order_id' => $orderId, 'product_id' => $productId]);
            }

            $this->db->commit();
            return $orderId;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function getOrderById($id) {
        $stmt = $this->db->prepare("SELECT * FROM orders WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($order) {
            $stmt = $this->db->prepare("SELECT * FROM products WHERE id IN (SELECT product_id FROM order_products WHERE order_id = :order_id)");
            $stmt->execute(['order_id' => $id]);
            $order['products'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        return $order;
    }
}
?>
