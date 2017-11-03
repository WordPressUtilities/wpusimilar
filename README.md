# WPUSimilar

Retrieve Similar Posts


```php
<?php
global $WPUSimilar;
if (isset($WPUSimilar) && is_object($WPUSimilar)) {
    $similar = $WPUSimilar->get_similar(get_the_ID(), 'product', 'artiste');
    echo "<pre>";
    var_dump($similar);
    echo '</pre>';
}
?>
```
