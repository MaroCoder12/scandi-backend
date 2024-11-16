<?php

abstract class AbstractProduct {
    protected $id;
    protected $name;
    protected $price;
    protected $categoryId;
    protected $db;

    public function __construct($db) {
        $this->db = $db;
    }

    abstract public function create($name, $price, $categoryId);
    abstract public function update($id,$name, $price, $categoryId);
    abstract public function delete($id);



    public function getId() {
        return $this->id;
    }

    public function getName() {
        return $this->name;
    }

    public function getPrice() {
        return $this->price;
    }

    public function getCategoryId() {
        return $this->categoryId;
    }
}
