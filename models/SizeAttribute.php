<?php
require_once 'Attribute.php';

class SizeAttribute extends Attribute {
    public function getAdditionalPrice() {
        return $this->value === 'large' ? 10 : 5;
    }
}
?>
