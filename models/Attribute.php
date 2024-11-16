<?php
abstract class Attribute {
    protected $id;
    protected $name;
    protected $value;

    public function __construct($id, $name, $value) {
        $this->id = $id;
        $this->name = $name;
        $this->value = $value;
    }

    abstract public function getAdditionalPrice();
}
?>
