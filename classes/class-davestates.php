<?php
/**
 * Created by IntelliJ IDEA.
 * User: morgan
 * Date: 11/13/15
 * Time: 8:54 AM
 */

// TODO Use meta fields to add the statemap settings

// Modified Statemap Page to call Shortcodes for Table_Ids / State
// Created shortcode caller for Tablespress State Tables
// Created Filter functions for Tablepress tables / State
// Using CSS to Hide the First Column of data

//define('WP_DEBUG', true);
// Security
// Block direct access to the plugin
defined( 'ABSPATH' ) or die( 'Action not allowed bub.' );

abstract class Davestates {

  const version = '0.9.1';

  const db_version = '0.1.0';

  /**
   * Start Here
   */
  public static function run() {
    /**
     * Fires when davestates is loaded
     */
    do_action('davestates_run');

    // Register Statemap Post Type
    self::statemap_register_post_type();

    // Add Content filters
    add_filter("the_content", array(__Class__, "statemap_content"));
    add_filter("the_content", array(__Class__, "statemap_data_content"));

    // Enqueue scripts
    add_action('wp_enqueue_scripts', array(__Class__, "statemap_enqueue_scripts"));

    //add_action('wp_print_header_scripts', function() {
      //if (wp_script_is())
    //});

    // Fix Javscript url for jvqmap
    //add_filter('clean_url', array(__Class__, 'clean_url_utf'));

    // Rewrite Rules
    add_filter('rewrite_rules_array', array(__Class__, 'statemap_rewrite_rules'));
    add_filter('query_vars', array(__Class__,'statemap_rewrite_query_vars'));

    // On Deactivate
    //register_deactivation_hook(__FILE__, array(__Class__, 'deactivate'));

    add_action('add_meta_boxes', array(__Class__, 'statemap_meta_fields'));
    add_action('save_post', array(__Class__, 'save_statemap_tableids'));

    //add_action('tablepress_run', array(__Class__, 'tablepress_init'));
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
        'register_meta_box_cb' => array(__CLASS__, 'statemap_meta_fields')
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
        "<div id=\"davestates-map\" class=\"entry-content davestates-map\">
            <div id=\"vmap\" class=\"map\" style=\"width: 600px; height: 400px;\"></div>
           </div>%s", $content
      );
    }

    return $content;
  }

  /**
   * Adds the States Data Page to the_content of the statemap so we don't need a template
   *
   * @param $content string
   *
   * @return string
   */
  public static function statemap_data_content($content) {
    global $post;
    $postid = $post->ID;
    $statename = get_query_var('state');
    $statename = preg_replace('/\-/', ' ', $statename );

    if ($statename == '') $statename = 'United States';

    error_log(sprintf('%s - statename -> %s', __FUNCTION__, $statename));

    //$state = self::get_state($statename);
    //$statecode = $state;
    $tableshtml = '<div id="davestates-tables">';

    if ($post->post_type == 'davestates_statemap') {
      $tables = self::get_tablepress_tables($postid);
      foreach ($tables as $tid => $tablename) {
        // DEBUG CODE BELOW
        //$table = TablePress::$controller->model_table->load($tid);
        //$data = print_r($table['data'],1);
        $tablecode = sprintf("[table id=%s davestates-state='%s' responsive='flip' /]", $tid, $statename);
        $tableshtml = sprintf("%s<div class='entry-content davestates-table'>
                %s
           </div>", $tableshtml, $tablecode);
      }
    }
    $tableshtml.='</div>';
    $content = sprintf("%s %s", $content, $tableshtml);
    return $content;
  }

  /**
   * Meta fields for statemap
   */
  public static function statemap_meta_fields() {
    add_meta_box(
      'davestates_statemap_tableids',
      "Included Tables",
      array(__CLASS__,'metabox_statemap_tableids'),
      'davestates_statemap',
      'normal',
      'default'
    );
  }

  /**
   * Create Statemap Post Specific Settings / Meta Fields
   */
  public static function metabox_statemap_tableids() {
    global $post;

    echo '<input type="hidden" name="davestates_statemap_meta_noncename"'.
    ' id="davestates_statemap_meta_noncename" value="' .
      wp_create_nonce(DAVESTATES_BASENAME). '" />';

    wp_nonce_field('davestates_statemap_meta_action', 'davestates_statesmap_meta_field');

    $checkboxMeta = get_post_meta($post->ID);

    $selectedTableIds = isset($checkboxMeta['davestates_tableids']) ?
      unserialize($checkboxMeta['davestates_tableids'][0]) : array();

    $tables = self::get_tablepress_tables();

    foreach ($tables as $tableid => $tablename) {

      $stable = "davestates-table--".sanitize_title_with_dashes($tablename);
      $checked = in_array($tableid, $selectedTableIds) ? checked(true, true , false) : '';
      echo sprintf(
        '<input type="checkbox" name="%s" id="%s" value="%s" %s/>%s<br />',
        $stable,
        $stable,
        $tableid,
        $checked,
        $tablename.print_r($selectedTableIds, 1));
    }
  }

  /**
   * Save the Statemap Meta Data
   *
   * @param $post_id
   */
  public static function save_statemap_tableids( $post_id) {
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
      return;
    if ( ( isset ( $_POST['davestates_statemap_meta_action'] ) ) && ( ! wp_verify_nonce( $_POST['davestates_statesmap_meta_field\''], plugin_basename( __FILE__ ) ) ) )
      return;
    if ( ( isset ( $_POST['post_type'] ) ) && ( 'davestates_statemap' == $_POST['post_type'] )  ) {
      if ( ! current_user_can( 'edit_page', $post_id ) ) {
        return;
      }
    } else {
      if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
      }
    }

    if ( ( isset ( $_POST['post_type'] ) ) && ( 'davestates_statemap' == $_POST['post_type'] )  ) {
      $tables = self::get_tablepress_tables();

      $selected_tables = array();
      //get_post_me
      foreach ($tables as $tableid => $tablename) {
        //saves bob's value
        $stable = "davestates-table--" . sanitize_title_with_dashes($tablename);
        if (isset($_POST[$stable])) {
          $selected_tables[] = $tableid;
        }
      }

      update_post_meta($post_id, 'davestates_tableids', array_map('sanitize_text_field', $selected_tables));

      wp_cache_delete(sprintf('davestates_tables%s',$post_id),'davestates');
    }
  }

  /**
   * Get all the table press tables
   *
   * @param $postid bool
   *
   * @returns array
   */
  public static function get_tablepress_tables($postid = false) {

    error_log(sprintf('get_tablepress_tables - Post ID %s', $postid));
    if (!$postid) {
      $postidtag = "";
    } else {
      $postidtag = $postid;
      // Load the Selected Table Ids from
      //$checkboxMeta = get_post_meta($postid);
      //$selectedTableIds = isset($checkboxMeta['davestates_tableid']) ?
      //  unserialize($checkboxMeta['davestates_tableid'][0]) : array();

      if ( in_array('davestates_tableids', get_post_custom_keys($postid))) {
        $selectedTableIds = get_post_meta($postid, 'davestates_tableids');
        $selectedTableIds = reset($selectedTableIds);
      }
    }

    // Cache the table function
    $tablesArr = wp_cache_get(sprintf('davestates_tables%s',$postidtag),'davestates');

    if ($tablesArr) return $tablesArr;

    $tables = TablePress::$controller->model_table->load_all();

    $tablesArr = array();
    //echo $postid.$selectedTableIds;
    //print_r($selectedTableIds);
    foreach ($tables as $table_id ) {
      // If PostID is found then only load tables that are selected
      if ($postid !== false && isset($selectedTableIds)) {
       if (in_array($table_id, $selectedTableIds) === false) {
         continue;
       }
      }

      // Load each wordpress table
      $table = TablePress::$controller->model_table->load($table_id);
      // Get the name of each table and it's ID as an array to return

      $stateheader = $table['data'][0][0];

      // filter to only show tables with a column 1 name = State
      if (false !== stripos($stateheader, "State")) {
        $tablesArr[$table_id] = $table['name'];
      }
    }

    wp_cache_add(sprintf('davestates_tables%s', $postidtag), 'davestates', 300);
    return $tablesArr;
  }

  /**
   * Filter the table data to remove rows that do not contain $statename
   *
   * TODO Add ability to lookup multiple states
   *
   * @param $table
   * @param $render_options
   *
   * @return array
   */
  public static function table_filter_rows($table, $render_options = array()) {

    $postType = get_post_type();

    error_log(sprintf('Here is the Post Type - %s', $postType));

    if ($postType == 'davestates_statemap') {
    //if (is_singular('davestates_statemap')) {
      //if (empty($render_options['davestates-state'])) {
      //  return $table;
      //} //elseif ($render_options['davestates-state'] == 'all') {
        //return $table;
      //}

      //return false;
      $hidden_rows = array();

      $state = (!empty($render_options['davestates-state'])) ?
        $render_options['davestates-state'] : 'xxx';

      $state = preg_replace('/\-/', ' ', $state );

      //$states = explode( ',', $options['states']);

      $rows = $table['data'];

      $doTotals = false; // TODO get Total boolean from statemap settings

      $pattern = '/\{(\W*(?i)state(?-i)\W*)\}/';

      $totals_rows = array();

      $tablename = $table['name'];
      $table['name'] = preg_replace($pattern, ucwords($state), $tablename);

      $last_row_key = count($rows) - 1;
      foreach ($rows as $key => $row) {

        // Look through each $column for the {State} value and replace it
        foreach ($row as $colKey => $column) {
          $table['data'][$key][$colKey] = ucwords(preg_replace($pattern, $state, $column, 1));
        }

        if (($key === 0 && $render_options['table_head']) ||
          ($last_row_key === $key && $render_options['table_foot'])
        ) continue;

        if ((stripos($row[0], 'united states') !== false) ||
            (stripos($row[0], 'nationwide') !== false )) {
          // TODO Allow Data for USA or All as state)


            //if ($state == 'xxx') {
              $state = $row[0];
              // Look through each $column for the {State} value and replace it
              foreach ($row as $colKey => $column) {
                $table['data'][$key][$colKey] = ucwords(preg_replace($pattern, $state, $column, 1));
              }
            //}
            //else {
              if ($doTotals) {
                $totals_rows[] = $row;
                $hidden_rows[] = $key;
              }
            //}

          continue;
        }

        if (stripos($row[0], $state) === false) {
          $hidden_rows[] = $key;
        }
      }
      foreach ($hidden_rows as $key) {
        unset($table['data'][$key]);
        unset($table['visibility']['rows'][$key]);
      }

      if ($doTotals) {
        foreach ($totals_rows as $row) {
          $new_row = array(count($row) - 1);
          $new_row[1] = ucwords($row[0]);
          $table['data'][] = $new_row;
          $table['visibility']['rows'][] = true;
          $table['data'][] = $row;
          $table['visibility']['rows'][] = true;
        }
      }


      // Reset array keys.
      $table['data'] = array_merge($table['data']);
      $table['visibility']['rows'] = array_merge($table['visibility']['rows']);

      if (count($table['data']) == 0) {
        return false;
      }
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
    $attr['davestates-state'] = 'xxx';
    return $attr;
  }

  /**
   * Load Script and Stylesheet for custom Post Type
   */
  public static function statemap_enqueue_scripts() {
    global $post;
    $dir = dirname(__FILE__);
    if (is_singular('davestates_statemap')) {
      wp_register_style('statemap-style', plugin_dir_url($dir) . "css/statemap.css", array(), '1.1');
      wp_enqueue_style('statemap-style');

      wp_enqueue_script('jquery');

      // Load jvqmap Javascript
      wp_register_script('statemap-vmap', plugin_dir_url($dir) . "js/jqvmap/jquery.vmap.js", array('jquery'), '1.1');
      wp_enqueue_script('statemap-vmap');

      // Load jqvmap usa map javascript
      wp_register_script('statemap-usa', plugin_dir_url($dir) . "js/jqvmap/maps/jquery.vmap.usa.js", array('jquery'), '1.1');
      wp_enqueue_script('statemap-usa');

      //
      $statename = get_query_var('state');
      $state = self::get_state($statename);

      if ($state === false) {
        $statecode = '';
      } else {
        $statecode = $state['statecode'];
      }

      wp_register_script('davestates-statemap-script', plugin_dir_url($dir) . "js/statemap.js", array('jquery', 'statemap-usa', 'statemap-vmap'), '1.1', true);
      wp_localize_script('davestates-statemap-script', 'statemap_params', array(
        'hoverColor' => '#3300ff',
        'backgroundColor' => '#000000',
        'selectedColor' => '#0033ff',
        'statemapUrl' => get_permalink($post->ID),
        'statecode' => $statecode
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
    // Cache this function
    $states = wp_cache_get('davestates_states','davestates');

    if ( false == $states ) {
      $states = array(
        'Alabama' => 'AL',
        'Alaska' => 'AK',
        'Arizona' => 'AZ',
        'Arkansas' => 'AR',
        'California' => 'CA',
        'Colorado' => 'CO',
        'Connecticut' => 'CT',
        'Delaware' => 'DE',
        'District of Columbia' => 'DC',
        'Florida' => 'FL',
        'Georgia' => 'GA',
        'Hawaii' => 'HI',
        'Idaho' => 'ID',
        'Illinois' => 'IL',
        'Indiana' => 'IN',
        'Iowa' => 'IA',
        'Kansas' => 'KS',
        'Kentucky' => 'KY',
        'Louisiana' => 'LA',
        'Maine' => 'ME',
        'Maryland' => 'MD',
        'Massachusetts' => 'MA',
        'Michigan' => 'MI',
        'Minnesota' => 'MN',
        'Mississippi' => 'MS',
        'Missouri' => 'MO',
        'Montana' => 'MT',
        'Nebraska' => 'NE',
        'Nevada' => 'NV',
        'New Hampshire' => 'NH',
        'New Jersey' => 'NJ',
        'New Mexico' => 'NM',
        'New York' => 'NY',
        'North Carolina' => 'NC',
        'North Dakota' => 'ND',
        'Ohio' => 'OH',
        'Oklahoma' => 'OK',
        'Oregon' => 'OR',
        'Pennsylvania' => 'PA',
        'Rhode Island' => 'RI',
        'South Carolina' => 'SC',
        'South Dakota' => 'SD',
        'Tennessee' => 'TN',
        'Texas' => 'TX',
        'Utah' => 'UT',
        'Vermont' => 'VT',
        'Virginia' => 'VA',
        'Washington' => 'WA',
        'West Virginia' => 'WV',
        'Wisconsin' => 'WI',
        'Wyoming' => 'WY',
        'United States' => 'US');

      wp_cache_add('davestates_states', $states, 'davestates');
    }
    return $states;
  }

  /**
   * Get a state object
   *
   * @param $value
   * @return bool
   */
  public static function get_state($value) {
    if (!$value || strlen($value) < 2) {
      return false;
    }

    $states = self::get_states();

    $code = '';
    $name = '';

    if (strlen($value) == 2) {
      // This is a statecode get name
      $states = array_flip ($states);
      $code = strtoupper($value);
      $name = $states[$code];
    } elseif (strlen($value) > 2) {
      // This is a name get statecode
      $name = preg_replace('/\-/', ' ', $value );
      $name = ucwords($name);
      $code = $states[$name];
    }

    $state =  array('name' => $name, 'statecode' => $code);

    return $state;
  }

  /**
   * Fire on Deactivate Plugin
   */
  public static function deactivate() {
    $option_name = 'davestates_db_version';
    delete_option( $option_name );

  }
}