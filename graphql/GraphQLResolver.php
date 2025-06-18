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

        // Check if product already exists in cart
        $stmt = $this->pdo->prepare("SELECT * FROM cart WHERE product_id = :productId");
        $stmt->bindParam(':productId', $productId);
        $stmt->execute();
        $existingCartItem = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existingCartItem) {
            // Update existing cart item quantity
            $newQuantity = $existingCartItem['quantity'] + $quantity;
            $stmt = $this->pdo->prepare("UPDATE cart SET quantity = :quantity WHERE id = :id");
            $stmt->bindParam(':quantity', $newQuantity);
            $stmt->bindParam(':id', $existingCartItem['id']);
            $stmt->execute();
            $cartItemId = $existingCartItem['id'];
            $finalQuantity = $newQuantity;
        } else {
            // Insert new cart item
            $stmt = $this->pdo->prepare("INSERT INTO cart (product_id, quantity) VALUES (:productId, :quantity)");
            $stmt->bindParam(':productId', $productId);
            $stmt->bindParam(':quantity', $quantity);
            $stmt->execute();
            $cartItemId = $this->pdo->lastInsertId();
            $finalQuantity = $quantity;
        }

        // Get product pricing and image
        $stmt = $this->pdo->prepare("
            SELECT
                COALESCE(prices.amount, 0) as amount,
                COALESCE(product_gallery.image_url, '') as image_url
            FROM products
            LEFT JOIN prices ON products.id = prices.product_id
            LEFT JOIN product_gallery ON products.id = product_gallery.product_id
            WHERE products.id = :id
        ");
        $stmt->bindParam(':id', $productId);
        $stmt->execute();
        $productDetails = $stmt->fetch(PDO::FETCH_ASSOC);

        // Return cart item with proper structure matching frontend expectations
        return [
            'id' => $cartItemId,
            'product' => [
                'id' => $product['id'],
                'name' => $product['name'],
                'price' => $productDetails['amount'] ?? 0,  // Use 'price' to match cart query
                'image' => $productDetails['image_url'] ?? '',  // Use 'image' to match cart query
                'attributes' => $product['attributes'] ?? null,
            ],
            'quantity' => $finalQuantity,
        ];
    }

    // Get Cart Resolver
    public function getCart() {
        $stmt = $this->pdo->query("
            SELECT
                cart.id as cart_id,
                products.*,
                cart.quantity,
                COALESCE(prices.amount, 0) as amount,
                COALESCE(product_gallery.image_url, '') as image_url
            FROM cart
            JOIN products ON cart.product_id = products.id
            LEFT JOIN prices ON cart.product_id = prices.product_id
            LEFT JOIN product_gallery ON cart.product_id = product_gallery.product_id
            ORDER BY cart.added_at DESC
        ");
        $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(function ($item) {
            // Get product attributes
            $attributesStmt = $this->pdo->prepare("
                SELECT attributes.name, attribute_items.value, attribute_items.display_value
                FROM attributes
                JOIN attribute_items ON attributes.id = attribute_items.attribute_id
                WHERE attributes.product_id = :product_id
            ");
            $attributesStmt->bindParam(':product_id', $item['id']);
            $attributesStmt->execute();
            $attributesData = $attributesStmt->fetchAll(PDO::FETCH_ASSOC);

            // Group attributes by name
            $groupedAttributes = [];
            foreach ($attributesData as $attr) {
                if (!isset($groupedAttributes[$attr['name']])) {
                    $groupedAttributes[$attr['name']] = [];
                }
                $groupedAttributes[$attr['name']][] = $attr['value'];
            }

            return [
                'id' => $item['cart_id'],
                'product' => [
                    'id' => $item['id'],
                    'name' => $item['name'],
                    'price' => $item['amount'],
                    'image' => $item['image_url'],
                    'attributes' => json_encode($groupedAttributes),
                ],
                'quantity' => $item['quantity']
            ];
        }, $cartItems);
    }

    public function updateCartItem($variables) {
        $cart = new Cart($this->pdo);
        $itemId = $variables['itemId'];
        $quantityChange = $variables['quantityChange'];

        // Get current cart item with product details
        $stmt = $this->pdo->prepare("
            SELECT cart.id as cart_id, products.*, cart.quantity, prices.amount, product_gallery.image_url
            FROM cart
            JOIN products ON cart.product_id = products.id
            JOIN prices ON cart.product_id = prices.product_id
            JOIN product_gallery ON cart.product_id = product_gallery.product_id
            WHERE cart.id = :id
        ");
        $stmt->bindParam(':id', $itemId);
        $stmt->execute();
        $cartItem = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$cartItem) {
            throw new Exception("Cart item not found");
        }

        $newQuantity = $cartItem['quantity'] + $quantityChange;

        if ($newQuantity <= 0) {
            // Remove item if quantity becomes 0 or negative
            $stmt = $this->pdo->prepare("DELETE FROM cart WHERE id = :id");
            $stmt->bindParam(':id', $itemId);
            $stmt->execute();
            return null; // Return null for deleted items
        } else {
            // Update quantity
            $stmt = $this->pdo->prepare("UPDATE cart SET quantity = :quantity WHERE id = :id");
            $stmt->bindParam(':quantity', $newQuantity);
            $stmt->bindParam(':id', $itemId);
            $stmt->execute();

            // Return updated cart item with product details
            return [
                'id' => $cartItem['cart_id'],
                'product' => [
                    'id' => $cartItem['id'],
                    'name' => $cartItem['name'],
                    'price' => $cartItem['amount'],
                    'image' => $cartItem['image_url'],
                ],
                'quantity' => $newQuantity
            ];
        }
    }

    public function removeFromCart($variables) {
        $itemId = $variables['itemId'];

        // First, check if the cart item exists
        $stmt = $this->pdo->prepare("SELECT * FROM cart WHERE id = :id");
        $stmt->bindParam(':id', $itemId);
        $stmt->execute();
        $cartItem = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$cartItem) {
            throw new Exception("Cart item not found with ID: " . $itemId);
        }

        // Get cart item details before deletion with LEFT JOINs to handle missing data
        $stmt = $this->pdo->prepare("
            SELECT
                cart.id as cart_id,
                products.*,
                cart.quantity,
                COALESCE(prices.amount, 0) as amount,
                COALESCE(product_gallery.image_url, '') as image_url
            FROM cart
            JOIN products ON cart.product_id = products.id
            LEFT JOIN prices ON cart.product_id = prices.product_id
            LEFT JOIN product_gallery ON cart.product_id = product_gallery.product_id
            WHERE cart.id = :id
        ");
        $stmt->bindParam(':id', $itemId);
        $stmt->execute();
        $cartItemDetails = $stmt->fetch(PDO::FETCH_ASSOC);

        // Delete the cart item
        $stmt = $this->pdo->prepare("DELETE FROM cart WHERE id = :id");
        $stmt->bindParam(':id', $itemId);
        $deleteResult = $stmt->execute();

        if (!$deleteResult) {
            throw new Exception("Failed to delete cart item");
        }

        // Return the deleted cart item data
        return [
            'id' => $cartItem['id'],
            'product' => [
                'id' => $cartItemDetails['id'] ?? $cartItem['product_id'],
                'name' => $cartItemDetails['name'] ?? 'Unknown Product',
                'price' => $cartItemDetails['amount'] ?? 0,
                'image' => $cartItemDetails['image_url'] ?? '',
            ],
            'quantity' => 0 // Set to 0 to indicate it was removed
        ];
    }

    public function placeOrder() {
        // Simple implementation - just clear the cart
        $stmt = $this->pdo->prepare("DELETE FROM cart");
        $stmt->execute();

        return [
            'success' => true,
            'message' => 'Order placed successfully!'
        ];
    }
}
