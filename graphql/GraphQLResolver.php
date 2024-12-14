<?php

require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Cart.php';


class GraphQLResolver {
    private $pdo;

    public function __construct() {
        // Initialize the PDO connection
        $database = new Database();
        $this->pdo = $database->getConnection();
    }
    // Method to retrieve a single product by ID
    public function getProduct($variables) {
        $product = new Product($this->pdo);
        return $product->getProductById($variables['id']);
    }

    // Method to retrieve all products
    public function getProducts() {
        $product = new Product($this->pdo);
        return $product->getAllProducts();
    }

    // Method to create a new product
    public function createProduct($variables) {
        $product = new Product($this->pdo);
        $product->create($variables['name'], $variables['price'], $variables['category_id']);
    }

    // Method to update an existing product
    public function updateProduct($variables) {
        $product = new Product($this->pdo);
        $product->update($variables['id'], $variables['name'], $variables['price'], $variables['category_id']);
    }

    // Method to delete a product by ID
    public function deleteProduct($variables) {
        $product = new Product($this->pdo);
        return $product->delete($variables['id']);
    }

    public function getAttributes($variables) : mixed{
        $product = new Product($this->pdo);
        return $product->getAttributes($variables['id']);
    }

    // Add to Cart Resolver
    public function addToCart($variables) {
        $productId = $variables['productId'];
        $quantity = $variables['quantity'];

        // Check if product exists
        $stmt = $this->pdo->prepare("SELECT * FROM products WHERE id = :id");
        $stmt->bindParam(':id', $productId);
        $stmt->execute();
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            throw new Exception("Product not found");
        }

        // Add product to cart (or update quantity if already in cart)
        $stmt = $this->pdo->prepare("
            INSERT INTO cart (product_id, quantity) 
            VALUES (:productId, :quantity)
            ON DUPLICATE KEY UPDATE quantity = quantity + :quantity
        ");
        $stmt->bindParam(':productId', $productId);
        $stmt->bindParam(':quantity', $quantity);
        $stmt->execute();

        // Return cart item
        return [
            'id' => $this->pdo->lastInsertId(),
            'product' => $product,
            'quantity' => $quantity,
        ];
    }

    // Get Cart Resolver
    public function getCart() {
        $stmt = $this->pdo->query("
            SELECT cart.id as cart_id, products.*, cart.quantity 
            FROM cart 
            JOIN products ON cart.product_id = products.product_id
        ");
        $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(function ($item) {
            return [
                'id' => $item['cart_id'],
                'product' => [
                    'id' => $item['product_id'],
                    'name' => $item['name'],
                ],
                'quantity' => $item['quantity']
            ];
        }, $cartItems);
    }
}
