<?php
require_once 'config/database.php';

// Load JSON data
$jsonData = file_get_contents('data.json');
$data = json_decode($jsonData, true);

$database = new Database();
$db = $database->getConnection();

try {
    $db->beginTransaction();  // Begin transaction to ensure atomicity

    // Insert categories and store their IDs
    $categoryMap = [];  // Map to store category names and their corresponding IDs
    foreach ($data['data']['categories'] as $category) {
        $stmt = $db->prepare("INSERT INTO categories (name) VALUES (:name)");
        $stmt->execute(['name' => $category['name']]);
        $categoryMap[$category['name']] = $db->lastInsertId();  // Store ID of each category
    }

    // Insert products
    foreach ($data['data']['products'] as $product) {
        // Insert the product
        $stmt = $db->prepare("INSERT INTO products (id, name, in_stock, description, category_id, brand) 
                              VALUES (:id, :name, :inStock, :description, :category_id, :brand)");
        $stmt->execute([
            'id' => $product['id'],
            'name' => $product['name'],
            'inStock' => $product['inStock'],
            'description' => $product['description'],
            'category_id' => $categoryMap[$product['category']],  // Assign product to the correct category
            'brand' => $product['brand']
        ]);

        $productId = $product['id'];  // Use product ID for inserting related data

        // Insert product gallery images
        foreach ($product['gallery'] as $image_url) {
            $stmt = $db->prepare("INSERT INTO product_gallery (product_id, image_url) VALUES (:product_id, :image_url)");
            $stmt->execute([
                'product_id' => $productId,
                'image_url' => $image_url
            ]);
        }

        // Insert product attributes
        foreach ($product['attributes'] as $attributeSet) {
            $stmt = $db->prepare("INSERT INTO attributes (product_id, name, type) VALUES (:product_id, :name, :type)");
            $stmt->execute([
                'product_id' => $productId,
                'name' => $attributeSet['name'],
                'type' => $attributeSet['type']
            ]);

            $attributeSetId = $db->lastInsertId();  // Get ID of the attribute set for item insertion

            // Insert attribute items
            foreach ($attributeSet['items'] as $item) {
                $stmt = $db->prepare("INSERT INTO attribute_items (attribute_id, display_value, value) 
                                      VALUES (:attribute_id, :display_value, :value)");
                $stmt->execute([
                    'attribute_id' => $attributeSetId,
                    'display_value' => $item['displayValue'],
                    'value' => $item['value']
                ]);
            }
        }

        // Insert product prices
        foreach ($product['prices'] as $price) {
            $stmt = $db->prepare("INSERT INTO prices (product_id, amount, currency) VALUES (:product_id, :amount, :currency)");
            $stmt->execute([
                'product_id' => $productId,
                'amount' => $price['amount'],
                'currency' => $price['currency']['label']
            ]);
        }
    }

    $db->commit();  // Commit the transaction if everything is successful
    echo "Data imported successfully!";
} catch (Exception $e) {
    $db->rollBack();  // Rollback the transaction if an error occurs
    echo "Failed to import data: " . $e->getMessage();
}
