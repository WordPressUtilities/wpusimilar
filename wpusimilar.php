<?php

/*
Plugin Name: WPU Similar
Plugin URI: http://github.com/Darklg/WPUtilities
Description: Retrieve Similar Posts
Version: 0.3.1
Author: Darklg
Author URI: http://darklg.me/
License: MIT License
License URI: http://opensource.org/licenses/MIT
*/

class WPUSimilar {

    private $top_nb = 15;
    private $query_cache_duration = 60;
    private $exclude_outofstock_products = true;

    public function __construct() {
        add_action('plugins_loaded', array(&$this, 'plugins_loaded'));
    }

    public function plugins_loaded() {
        $this->top_nb = apply_filters('wpusimilar__settings__top_nb', $this->top_nb);
        $this->query_cache_duration = apply_filters('wpusimilar__settings__query_cache_duration', $this->query_cache_duration);
        $this->exclude_outofstock_products = apply_filters('wpusimilar__settings__exclude_outofstock_products', $this->exclude_outofstock_products);
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
        foreach ($taxonomies as $tax_name => $tax_details) {

            $_tax_name = $tax_name;
            if (!is_array($tax_details)) {
                /* Extract tax name */
                $_tax_name = $tax_details;
                /* Extract params */
                $tax_details = array();
            }
            if (!isset($tax_details['points'])) {
                $tax_details['points'] = 1;
            }

            /* Extract term list for this post */
            $term_list = wp_get_post_terms($post_id, $_tax_name, array("fields" => "all"));
            foreach ($term_list as $term) {
                /* Get latest posts with this term */
                $top_posts = $this->get_posts_for_term($post_id, $post_types, $term);
                foreach ($top_posts as $top_post) {
                    /* Add a point for each occurence of this post */
                    if (!isset($posts_results[$top_post])) {
                        $posts_results[$top_post] = 0;
                    }
                    $posts_results[$top_post] += $tax_details['points'];
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

            /* Exclude out of stock products */
            if ($this->exclude_outofstock_products) {
                $args['meta_query'] = array(
                    array(
                        'key' => '_stock_status',
                        'value' => 'outofstock',
                        'compare' => 'NOT IN'
                    )
                );
            }

        }

        $args = apply_filters('wpusimilar__get_posts_for_term__args', $args, $post_types, $term);

        $cache_id = 'wpusimilar_query_' . md5(json_encode($args));

        // GET CACHED VALUE
        $_posts = wp_cache_get($cache_id);
        if ($_posts === false) {

            // COMPUTE RESULT
            $_posts = get_posts($args);

            // CACHE RESULT
            wp_cache_set($cache_id, $_posts, '', $this->query_cache_duration);
        }

        return $_posts;

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
