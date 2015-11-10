<?php

// Security
// Block direct access to the plugin
defined( 'ABSPATH' ) or die( 'Action not allowed bub.' );


/**
 * Create Custom Post Type
 */
function davestates_create_statemap() {
    register_post_type( 'davestates_statemap',
        array(
            'labels' => array(
                'name' => __( 'State Maps' ),
                'singular_name' => __( 'State Map' ),
            ),
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => true,
            'publicly_queryable' => true,
            'has_archive' => true,
            'rewrite' => array('slug' => 'statemap'),
            'supports' => array( 'title', 'editor', 'custom-fields', 'thumbnail' ),
        )
    );
}
add_action('init', 'davestates_create_statemap');

/**
 * Template Filter for Custom Post Type
 *
 * @param $single_template
 * @return string
 */
function get_davestates_statemap_template($single_template) {
    global $post;

    if ($post->post_type == 'davestates_statemap') {
        $single_template = dirname( __FILE__ ) . '/templates/single-davestates-statemap-template.php';
    }
    return $single_template;
}
add_filter( 'single_template', 'get_davestates_statemap_template' );


/**
 * Load Script and Stylesheet for custom Post Type
 */
function davestates_statemap_enqueue_scripts() {

    $dir = __FILE__;
    if (is_singular('davestates_statemap')) {
        wp_register_style('statemap-style', plugins_url("/css/statemap.css", $dir));
        wp_enqueue_style('statemap-style');

        wp_register_script('davestates-jquery', plugins_url("/js/jquery-1.7.1.min.js", $dir));
        wp_enqueue_script('davestates-jquery');



        wp_register_script('statemap-vmap', plugins_url("/js/jqvmap/jquery.vmap.js", $dir, array('jquery'), false, true));
        wp_enqueue_script('statemap-vmap');

        wp_register_script('statemap-usa', plugins_url("/js/jqvmap/maps/jquery.vmap.usa.js", $dir, array('jquery')), false, true);
        wp_enqueue_script('statemap-usa');

        wp_register_script('statemap-script', plugins_url("/js/statemap.js", $dir, array('jquery')));
        wp_enqueue_script('statemap-script');
    }

    //<script type="text/javascript" src="http://code.jquery.com/jquery-1.11.3.min.js"></script>
    //<script type="text/javascript" src="../jqvmap/jquery.vmap.js"></script>
    //<script type="text/javascript" src="../jqvmap/maps/jquery.vmap.usa.js" charset="utf-8"></script>

}
add_action('wp_enqueue_scripts', 'davestates_statemap_enqueue_scripts');


// TODO Use Shortcode to ad the statemap at the top of the page
// TODO Use Shortcode to return the statemap data when a state is selected
function davestates_statemap_shortcode($atts, $content = null) {
    ob_start();
    ?>
    <div id="vmap" class="map" style="width: 600px; height: 400px;"></div>
    <?php
    return ob_get_clean();
}
add_shortcode('davestates-statemap', 'davestates_statemap_shortcode');

function davestates_statemap_statedata_shortcode($atts, $content = null) {
    $a = shortcode_atts( array(
        'statecode' => 'all',
        'postid' => 0
      ), $atts
    );

    $statecode = $a['statecode'];
    $postid = $a['postid'];

    return davestates_statemap_statedata_page($statecode, $postid);
}
add_shortcode('davestates-statemap-statedata', 'davestates_statemap_statedata_shortcode');

function davestates_statemap_statedata_page($statecode, $postid) {
    return "<div class='entry-content'>
                <span>".$statecode."</span>
                <span>".$postid."</span>
            </div>";
}

function davestates_clean_url_utf($url) {
    if (stripos($url, plugins_url('/js/jqvmap/maps/jquery.vmap.usa.js',__FILE__) !== false)) {
        return $url."\" charset=\"utf-8";
    }
    return $url;
}
add_filter('clean_url', 'davestates_clean_url_utf');