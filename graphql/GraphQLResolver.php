<?php

require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/ProductFactory.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Cart.php';
require_once __DIR__ . '/../services/OrderService.php';

class GraphQLResolver {
    private $pdo;
    private $productFactory;

    public function __construct() {
        // Initialize the PDO connection
        $database = new Database();
        $this->pdo = $database->getConnection();
        $this->productFactory = new ProductFactory($this->pdo);
    }
    /**
     * Normalize various DB representations of stock into a boolean.
     * Accepts values like 'true'/'false', '1'/'0', 'yes'/'no', numeric counts.
     */
    private function normalizeInStock($value): bool {
        if (is_bool($value)) {
            return $value;
        }
        if ($value === null) {
            return false;
        }
        $val = strtolower(trim((string)$value));
        // Numeric quantities: any positive number means in stock
        if (is_numeric($val)) {
            return intval($val) > 0;
        }
        // Common truthy/falsey strings
        $truthy = ['true', 'yes', 'y', 'on', 'available', 'in stock'];
        $falsey = ['false', 'no', 'n', 'off', 'unavailable', 'out of stock', ''];
        if (in_array($val, $truthy, true)) {
            return true;
        }
        if (in_array($val, $falsey, true)) {
            return false;
        }
        // Fallback: not in stock
        return false;
    }
    private function normalizeAttributesJson($attributes, $productId): string {
        // If provided, normalize keys and ensure stable JSON
        if ($attributes) {
            $decoded = json_decode($attributes, true);
            if (is_array($decoded)) {
                ksort($decoded);
                return json_encode($decoded, JSON_UNESCAPED_UNICODE);
            }
        }
        // Derive defaults based on available attributes for the product
        $available = $this->getAttributes(['id' => $productId]);
        if (is_array($available) && !empty($available)) {
            $defaults = [];
            foreach ($available as $key => $values) {
                if (is_array($values) && count($values) > 0) {
                    $defaults[$key] = $values[0];
                }
            }
            if (!empty($defaults)) {
                ksort($defaults);
                return json_encode($defaults, JSON_UNESCAPED_UNICODE);
            }
        }
        // Fallback placeholder for products with no attributes
        return json_encode(['Options' => 'Default'], JSON_UNESCAPED_UNICODE);
    }


    // Method to retrieve a single product by ID using polymorphism
    public function getProduct($variables) {
        $product = $this->productFactory->getProductByIdWithType($variables['id']);
        if ($product) {
            // Map DB field `in_stock` (stored as string) to GraphQL field `inStock` (boolean)
            $product['inStock'] = $this->normalizeInStock($product['in_stock'] ?? null);
        }
        return $product;
    }

    // Method to retrieve all products using polymorphism
    public function getProducts() {
        $products = $this->productFactory->getAllProductsWithTypes();
        return array_map(function ($item) {
            return [
                'id' => $item['id'],
                'name' => $item['name'],
                'amount' => $item['amount'],
                'image_url' => $item['image_url'],
                'category_id' => $item['category_id'],
                'brand' => $item['brand'] ?? '',
                'product_type' => $item['product_type'] ?? 'general',
                // Normalize DB in_stock to GraphQL inStock boolean
                'inStock' => $this->normalizeInStock($item['in_stock'] ?? null),
            ];
        }, $products);
    }

    // Method to create a new product using polymorphism
    public function createProduct($variables) {
        $categoryId = $variables['category_id'];
        $product = $this->productFactory->createProduct($categoryId);
        $product->create($variables['name'], $variables['brand'], $categoryId);
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
        $incomingAttributes = $variables['attributes'] ?? null;

        // Check if product exists
        $stmt = $this->pdo->prepare("SELECT * FROM products WHERE id = :id");
        $stmt->bindParam(':id', $productId);
        $stmt->execute();
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            throw new Exception("Product not found");
        }

        // Normalize/derive attributes (default for quick shop)
        $normalizedAttributes = $this->normalizeAttributesJson($incomingAttributes, $productId);

        // Check if a cart line with same product and same attributes exists
        $stmt = $this->pdo->prepare("SELECT * FROM cart WHERE product_id = :productId AND COALESCE(attributes,'') = :attributes");
        $stmt->bindParam(':productId', $productId);
        $stmt->bindParam(':attributes', $normalizedAttributes);
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
            // Insert new cart item with attributes
            $stmt = $this->pdo->prepare("INSERT INTO cart (product_id, attributes, quantity) VALUES (:productId, :attributes, :quantity)");
            $stmt->bindParam(':productId', $productId);
            $stmt->bindParam(':attributes', $normalizedAttributes);
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
                'price' => $productDetails['amount'] ?? 0,
                'image' => $productDetails['image_url'] ?? '',
                'attributes' => $normalizedAttributes,
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
                COALESCE(product_gallery.image_url, '') as image_url,
                cart.attributes as selected_attributes
            FROM cart
            JOIN products ON cart.product_id = products.id
            LEFT JOIN prices ON cart.product_id = prices.product_id
            LEFT JOIN product_gallery ON cart.product_id = product_gallery.product_id
            ORDER BY cart.added_at DESC
        ");
        $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(function ($item) {
            // Use stored selected attributes or derive defaults
            $selected = $item['selected_attributes'] ?? '';
            if (!$selected) {
                // Derive defaults for stability
                $selected = $this->normalizeAttributesJson(null, $item['id']);
            }

            return [
                'id' => $item['cart_id'],
                'product' => [
                    'id' => $item['id'],
                    'name' => $item['name'],
                    'price' => $item['amount'],
                    'image' => $item['image_url'],
                    'attributes' => $selected,
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
        // Delegate to OrderService so dedicated endpoint and GraphQL share the same logic
        $orderService = new OrderService();
        return $orderService->placeOrder();
    }
}
