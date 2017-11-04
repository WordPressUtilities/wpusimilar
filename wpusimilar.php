<?php

/*
Plugin Name: WPU Similar
Plugin URI: http://github.com/Darklg/WPUtilities
Description: Retrieve Similar Posts
Version: 0.2.0
Author: Darklg
Author URI: http://darklg.me/
License: MIT License
License URI: http://opensource.org/licenses/MIT
*/

class WPUSimilar {

    private $top_nb = 15;

    public function __construct() {
        add_action('plugins_loaded', array(&$this, 'plugins_loaded'));
    }

    public function plugins_loaded() {

    }

    public function get_similar($post_id, $post_types = false, $taxonomies = array()) {
        /* Ensure correct args */
        if (!$post_id || !is_numeric($post_id)) {
            return array();
        }
        if (!is_array($post_types)) {
            $post_types = array();
        }
        if (!$post_types || empty($post_types)) {
            $post_types = array(get_post_type($post_id));
        }
        if (!is_array($taxonomies)) {
            $taxonomies = array($taxonomies);
        }
        /* Build results */
        $posts_results = array();
        foreach ($taxonomies as $tax_id) {
            /* Extract term list for this post */
            $term_list = wp_get_post_terms($post_id, $tax_id, array("fields" => "all"));
            foreach ($term_list as $term) {
                /* Get latest posts with this term */
                $top_posts = $this->get_posts_for_term($post_id, $post_types, $term);
                foreach ($top_posts as $top_post) {
                    /* Add a point for each occurence of this post */
                    if (!isset($posts_results[$top_post])) {
                        $posts_results[$top_post] = 0;
                    }
                    $posts_results[$top_post]++;
                }
            }
        }
        /* Order by number of points */
        arsort($posts_results);

        return $posts_results;
    }

    private function get_posts_for_term($post_id, $post_types = array(), $term) {

        /* Build query */
        $args = array(
            'post_type' => $post_types,
            'posts_per_page' => $this->top_nb,
            'post__not_in' => array($post_id),
            'fields' => 'ids',
            'tax_query' => array(
                array(
                    'taxonomy' => $term->taxonomy,
                    'field' => 'term_taxonomy_id',
                    'terms' => $term->term_taxonomy_id
                )
            )
        );

        /* Catalog exclusion for products */
        if (in_array('product', $args['post_type'])) {
            $product_visibility_terms = wc_get_product_visibility_term_ids();
            $args['tax_query'][] = array(
                'taxonomy' => 'product_visibility',
                'field' => 'term_taxonomy_id',
                'terms' => $product_visibility_terms['exclude-from-catalog'],
                'operator' => 'NOT IN'
            );
        }

        $args = apply_filters('wpusimilar__get_posts_for_term__args', $args, $post_types, $term);

        return get_posts($args);

    }

    public function add_similar_to_list($extra_products, $similar_products = array()) {
        if (!is_array($similar_products)) {
            $similar_products = array();
        }
        foreach ($extra_products as $extra_product => $points) {
            if (in_array($extra_product, $similar_products)) {
                continue;
            }
            $similar_products[] = $extra_product;
        }
        return $similar_products;
    }
}

$WPUSimilar = new WPUSimilar();
