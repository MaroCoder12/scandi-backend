<?php
require_once 'Attribute.php';

class ColorAttribute extends Attribute {
    public function getAdditionalPrice() {
        return 0;  // No extra cost for color
    }
}
?>
