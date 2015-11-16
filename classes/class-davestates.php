<?php
/**
 * Created by IntelliJ IDEA.
 * User: morgan
 * Date: 11/13/15
 * Time: 8:54 AM
 */

// TODO Create Filter functions for Tablepress tables / State
// TODO Create shortcode caller for Tablespress State Tables
// TODO Create metabox to select Tablepress tables to link to Map
// TODO Modify Statemap Page to call Shortcodes for Table_Ids / State
// TODO Use meta fields to add the statemap settings
// TODO Use CSS to Hide the First Column of data


// Security
// Block direct access to the plugin
defined( 'ABSPATH' ) or die( 'Action not allowed bub.' );

abstract class Davestates {

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

    add_action('tablepress_run', array('Davestates', 'tablepress_init'));

  }

  public static function tablepress_init() {
    add_filter( 'tablepress_table_raw_render_data', array( __CLASS__, 'table_filter_rows' ), 10, 2 );
    add_filter( 'tablepress_shortcode_table_default_shortcode_atts', array( __CLASS__, 'shortcode_attributes' ) );
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

      $tables = self::get_tablepress_tables($post->ID);

      $tableshtml = '';

      foreach ($tables as $tid => $tablename) {
        $tablecode = sprintf("[table id=%s state=%s /]", $tid, $statename);

        $tableshtml = sprintf("%s<div class='entry-content'>
                %s
           </div>", $tableshtml, $tablecode);
      }
    }

    $content = sprintf("%s %s", $content, $tableshtml);

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
   * Meta fields for statemap
   */
  public static function statemap_meta_fields() {

  }

  /**
   * Get all the table press tables
   *
   * @param $postid integer
   *
   *  TODO Filter for tables that have a first column named State
   *
   * @return array
   */
  public static function get_tablepress_tables($postid) {

    // TODO cache the table function
    //$tablesArr = wp_cache_get('davestates_tables','davestates');

    $tables = TablePress::$controller->model_table->load_all();

    $tablesArr = array();


    foreach ($tables as $table_id ) {
      // Load each wordpress table
      $table = TablePress::$controller->model_table->load($table_id);
      // Get the name of each table and it's ID as an array to return

      //$stateheader = $table['data'][0][0];

      // filter to only show tables with a column 1 name = State
      //if (stripos($stateheader, 'state')) {
        $tablesArr[$table_id] = $table['name'];
      //}


    }

    return $tablesArr;
  }

  /**
   * Filter the table data to remove rows that do not contain $statename
   *
   * @param $data
   * @param $states
   */
  public static function table_filter_rows($table, $options) {

    if (empty($options['states'])) {
      return $table;
    }

    $states = explode( ',', $options['states']);

    $rows = $table['data'];

    foreach ($rows as $key => $row) {
      if ($key == 0) {
        continue;
      }

      foreach($states as $state) {
        if (stripos($row[0], $state) != false) {
          $hidden_rows[] = $key;
        }
      }
    }
    foreach ($hidden_rows as $key) {
      unset( $table['data'][$key] );
      unset( $table['visibility']['rows'][$key]);
    }

    return $table;
  }

  /**
   * Attributes for the table shortcode
   *
   * @param $attr
   * @return mixed
   */
  public static function shortcode_attributes( $attr ) {
    $attr['state'] = 'all';
    return $attr;
  }



  /**
   * Load Script and Stylesheet for custom Post Type
   */
  public static function statemap_enqueue_scripts() {
    global $post;
    $dir = dirname(__FILE__);
    if (is_singular('davestates_statemap')) {
      wp_register_style('statemap-style', plugin_dir_url($dir) . "css/statemap.css");
      wp_enqueue_style('statemap-style');

      wp_enqueue_script('jquery');

      // Load jvqmap Javascript
      wp_register_script('statemap-vmap', plugin_dir_url($dir) . "js/jqvmap/jquery.vmap.js", array('jquery'));
      wp_enqueue_script('statemap-vmap');

      // Load jqvmap usa map javascript
      wp_register_script('statemap-usa', plugin_dir_url($dir) . "js/jqvmap/maps/jquery.vmap.usa.js", array('jquery'));
      wp_enqueue_script('statemap-usa');

      //
      $statename = get_query_var('state');
      $state = self::get_state($statename);

      wp_register_script('davestates-statemap-script', plugin_dir_url($dir) . "js/statemap.js", array('jquery'));
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
    $states = wp_cache_get('davestates_states','davestates');

    $states = false;

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
      if (strtolower($row[$stateArr['field']]) == strtolower($stateArr['value'])) {
        $state = $row;
      }
    }
    if (!$state) {
      return array('statecode' => $stateArr['value']);
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
      $single_template = DAVESTATES_ABSPATH . 'templates/single-davestates-statemap-template.php';
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