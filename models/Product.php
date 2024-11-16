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
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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

    public function getAttributes(): mixed {
        $sql = "SELECT * FROM attributes JOIN attribute_items ON attributes.id = attribute_items.attribute_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
