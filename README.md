# WPUSimilar

Retrieve Similar Posts


```php
<?php
global $WPUSimilar;
if (isset($WPUSimilar) && is_object($WPUSimilar)) {
    $similar_post_ids = $WPUSimilar->get_similar(get_the_ID(), 'post', 'category', array(
        /* Return only post ids */
        'return_ids' => true,
        /* Return only top 3 (and return keys only) */
        'max_number' => 3,
        /* Adds a 3 points boost to posts by same author */
        'same_author_boost' => 3
    ));
    echo "<pre>";
    var_dump($similar);
    echo '</pre>';
}
?>
```
