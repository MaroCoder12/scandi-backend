<?php
abstract class Attribute {
    protected $id;
    protected $name;
    protected $value;
    protected $type;
    protected $displayValue;

    public function __construct($id, $name, $value, $type = 'text', $displayValue = null) {
        $this->id = $id;
        $this->name = $name;
        $this->value = $value;
        $this->type = $type;
        $this->displayValue = $displayValue ?? $value;
    }

    abstract public function getAdditionalPrice();
    abstract public function getDisplayFormat();
    abstract public function validate($value);

    public function getId() {
        return $this->id;
    }

    public function getName() {
        return $this->name;
    }

    public function getValue() {
        return $this->value;
    }

    public function getType() {
        return $this->type;
    }

    public function getDisplayValue() {
        return $this->displayValue;
    }

    public function isRequired() {
        return false; // Override in subclasses if needed
    }

    public function getValidationRules() {
        return []; // Override in subclasses
    }
}
?>
