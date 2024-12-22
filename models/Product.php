<?php
require_once 'AbstractProduct.php';

class Product extends AbstractProduct {
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

    public function getAllProducts() {
        $stmt = $this->db->query("SELECT * FROM products JOIN product_gallery ON products.id = product_gallery.product_id JOIN prices ON products.id = prices.product_id");
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(function ($item) {
            return [
                'id' => $item['product_id'],
                'name' => $item['name'],
                'amount' => $item['amount'],
                'image_url' => $item['image_url'],
                'category_id' => $item['category_id']
            ];
        }, $products);
    }

    public function getProductById($id): mixed {
        $sql = "SELECT * FROM products JOIN product_gallery ON products.id = product_gallery.product_id JOIN prices ON products.id = prices.product_id  WHERE products.id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getProductsByCategory($id): mixed {
        $sql = "SELECT * FROM products JOIN product_gallery ON products.id = product_gallery.product_id JOIN prices ON products.id = prices.product_id  WHERE products.category_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function groupArrays($arrays) {
        $result = [];

        foreach ($arrays as $array) {
            // Append the value to the key's array
            $result[$array['attribute_name']][] = $array['value'];
        }

        return $result;
    }

    public function getAttributes($id): mixed {
        $sql = "SELECT * FROM attributes JOIN attribute_items ON attributes.id = attribute_items.attribute_id WHERE attributes.product_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_STR);
        $stmt->execute();
        $attributes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $this->groupArrays($attributes);
    }
}
