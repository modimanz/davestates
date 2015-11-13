<?php
/**
 * Created by IntelliJ IDEA.
 * User: morgan
 * Date: 11/13/15
 * Time: 8:54 AM
 */

// TODO Make statemap settings available per statemap content
// TODO Use meta fields to add the statemap settings
// TODO Format the STATEMAP Data Output

// Security
// Block direct access to the plugin
defined( 'ABSPATH' ) or die( 'Action not allowed bub.' );

abstract class Davestastes {

  const version = '0.9.1';

  const db_version = '0.1.0';


  public static function run() {

    /**
     * Fires when davestates is loaded
     */
    do_action('davestates_run');

    // Register Statemap Post Type
    self::statemap_register_post_type();

    // Add Content filters
    add_filter("the_content", array("Davestates", "statemap_content"));
    add_filter("the_content", array("Davestates", "statemap_data_content"));

    // Enqueue scripts
    add_action('wp_enqueue_scripts', array("Davestates", "statemap_enqueue_scripts"));

    // Fix Javscript url for jvqmap
    add_filter('clean_url', array('Davestates', 'clean_url_utf'));

    // Rewrite Rules
    add_filter('rewrite_rules_array', array('Davestates', 'statemap_rewrite_rules'));
    add_filter('query_vars', array('Davestates','statemap_rewrite_query_vars'));

    // Templates
    add_filter( 'single_template', array('Davestates', 'get_statemap_template'));

    // On Deactivate
    register_deactivation_hook(__FILE__, array('Davestates', 'deactivate'));

  }

  /**
   * Register Statemap Post Type with Wordpress
   */
  public static function statemap_register_post_type() {
    global $wp_rewrite;
    //add_post_type_support()

    // Add rewrite Tag
    add_rewrite_tag('%state%','([^&]+)');

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
        'supports' => array( 'title', 'editor', 'thumbnail', 'revisions' ),
        'register_meta_box_cb' => 'davestates_statemap_metaboxes'
      )
    );

    $wp_rewrite->flush_rules();
  }

  /**
   * Either this adds the charset type or this is not needed
   *
   * @param $url
   * @return string
   */
  public static function clean_url_utf($url) {
    if (stripos($url, plugins_url('/js/jqvmap/maps/jquery.vmap.usa.js',__FILE__) !== false)) {
      return $url."\" charset=\"utf-8";
    }
    return $url;
  }


  /**
   * Adds the statemap html code to the_content of the statemap
   *
   * @param $content
   * @return string
   */
  public static function statemap_content($content) {
    global $post;

    if ($post->post_type == 'davestates_statemap') {
      $content = sprintf(
        "<div class=\"entry-content\">
            <div id=\"vmap\" class=\"map\" style=\"width: 600px; height: 400px;\"></div>
           </div>%s", $content
      );
    }

    return $content;
  }

  /**
   * Adds the States Data Page to the_content of the statemap so we don't need a template
   *
   * @param $content
   * @return string
   */
  public static function statemap_data_content($content) {

    global $post;

    $postid = $post->ID;
    $statename = get_query_var('state');

    $state = self::get_state($statename);
    $statecode = "Code: " . $state['statecode']." Name: " . $statename;

    if ($post->post_type == 'davestates_statemap') {
      $content = sprintf(
        "%s" . self::statemap_statedata_page($statecode, $postid),
        $content
      );
    }

    return $content;
  }

  /**
   * Generates the States Data Page for the statemap posts
   *
   * @param $statecode
   * @param $postid
   * @return string
   */
  public static function statemap_statedata_page($statecode, $postid) {
    return "<div class='entry-content'>
                <span>".$statecode."</span>
                <span>".$postid."</span>
            </div>";
  }

  /**
   * Load Script and Stylesheet for custom Post Type
   */
  public static function statemap_enqueue_scripts() {
    global $post;
    $dir = __FILE__;
    if (is_singular('davestates_statemap')) {
      wp_register_style('statemap-style', plugins_url("/css/statemap.css", $dir));
      wp_enqueue_style('statemap-style');

      wp_enqueue_script('jquery');

      // Load jvqmap Javascript
      wp_register_script('statemap-vmap', plugins_url("/js/jqvmap/jquery.vmap.js", $dir, array('jquery')));
      wp_enqueue_script('statemap-vmap');

      // Load jqvmap usa map javascript
      wp_register_script('statemap-usa', plugins_url("/js/jqvmap/maps/jquery.vmap.usa.js", $dir, array('jquery')));
      wp_enqueue_script('statemap-usa');

      //
      $statename = get_query_var('state');
      $state = self::get_state($statename);

      wp_register_script('davestates-statemap-script', plugins_url("/js/statemap.js", $dir, array('jquery')));
      wp_localize_script('davestates-statemap-script', 'statemap_params', array(
        'hoverColor' => '#3300ff',
        'backgroundColor' => '#000000',
        'selectedColor' => '#0033ff',
        'statemapUrl' => get_permalink($post->ID),
        'statecode' => $state['statecode']
      ));
      wp_enqueue_script('davestates-statemap-script');
    }
  }



  /**
   * Rewrite rules for individual states on a statemap
   *
   * @param $rules
   * @return array
   */
  public static function statemap_rewrite_rules($rules) {
    $newrules = array();
    $newrules['statemap/([^/]*)/([^/]*)'] = 'index.php?davestates_statemap=$matches[1]&state=$matches[2]';
    $finalrules = $newrules + $rules;
    return $finalrules;
  }

  /**
   * This may not be needed.
   *
   * @param $vars
   * @return mixed
   */
  public static function statemap_rewrite_query_vars($vars) {
    array_push($vars, 'state');
    return $vars;
  }

  /**
   * Get all the states and cache them
   *
   * @return array|bool|mixed|null|object
   */
  public static function get_states() {
    //wp_cache_add

    // TODO Cache this function
    $states = wp_cache_get('davesstates_states','davestates');

    //$states = false;
    if ( false == $states ) {
      global $wpdb;
      $sql = "SELECT * FROM {$wpdb->prefix}davestates";
      $states = $wpdb->get_results($sql, 'ARRAY_A');
      wp_cache_add('davestates_states', $states, 'davestates');
    }
    return $states;
  }

  /**
   * Get a state object
   *
   * @param $state
   * @return bool
   */
  public static function get_state($state) {
    $stateArr = self::sanitize_state($state);
    $states = self::get_states();
    $state = false;

    foreach ($states as $key => $row) {
      if ($row[$stateArr['statefield']] == $stateArr['value']) {
        $state = $row;
      }
    }
    return $state;
  }

  /**
   * Sanitize the state ojbect lookup call
   *
   * @param $state
   * @return array
   */
  public static function sanitize_state($state) {
    // Sanitize the state arg
    if (!$state) {
      $statefield = false;
    }
    elseif (is_int($state)) {
      $statefield = 'id';
    } elseif (strlen($state) == 2) {
      $statefield = 'statecode';
    } elseif (strlen($state) > 3) {
      $statefield = 'name';
    } elseif (is_array($state)) {
      $state_arr = $state;
      $state = $state_arr['statecode'];
      $statefield = 'statecode';
    } else {
      $statefield = false;
    }

    return array('value' => $state, 'field' => $statefield);
  }

  /**
   * Template Filter for Custom Post Type
   *
   * @param $single_template
   * @return string
   */
  public static function get_statemap_template($single_template) {
    global $post;

    if ($post->post_type == 'davestates_statemap') {
      $single_template = dirname( __FILE__ ) . '/templates/single-davestates-statemap-template.php';
    }
    return $single_template;
  }


  /**
   * Fire on Deactive Plugin
   */
  public static function deactivate() {
    $option_name = 'davestates_db_version';
    delete_option( $option_name );

    // For site options in multisite
    //delete_site_option( $option_name );
    //drop a custom db table
    global $wpdb;

    $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}davestatessubcategory" );
    $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}davestatessubcategories" );
    $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}davestatesdata" );
    $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}davestatesreferences" );
    $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}davestates" );
    $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}davestatespages" );
    $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}davestatescategories" );
    $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}davestatescategory" );
  }
}