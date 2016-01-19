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
    add_action('wp_enqueue_scripts', array(__Class__, "statemap_enqueue_scripts"), 101);

    // Fix scripts for rocketscript
    //if (!is_admin()) {
    //  add_filter( 'clean_url', array(__CLASS__, 'rocket_loader_attributes_mark'), 11, 1);
    //  add_filter('wp_print_scripts', array(__CLASS__, 'rocket_loader_attributes_start'));
    //  add_filter('print_head_scripts', array(__CLASS__, 'rocket_loader_attributes_end'));
    //}

    //add_action('wp_print_header_scripts', function() {
      //if (wp_script_is())
    //});

    // Fix Javscript url for jvqmap
    //add_filter('clean_url', array(__Class__, 'clean_url_utf'));

    add_action('admin_print_scripts', array(__Class__, 'admin_print_scripts'));
    add_action('admin_print_styles', array(__Class__, 'admin_print_styles'));

    // Rewrite Rules
    add_filter('rewrite_rules_array', array(__Class__, 'statemap_rewrite_rules'));
    add_filter('query_vars', array(__Class__,'statemap_rewrite_query_vars'));

    // On Deactivate
    //register_deactivation_hook(__FILE__, array(__Class__, 'deactivate'));

    add_action('add_meta_boxes', array(__Class__, 'statemap_meta_fields'));
    add_action('save_post', array(__Class__, 'save_statemap_meta_fields'));

    //add_action('wp_footer', array(__Class__,'footer'));

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
        'menu_icon' => 'dashicons-location-alt',
        'taxonomies' => array('category'),
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

      $img_id = get_post_meta($post->ID, 'davestates_statemap_background_image', true);
      if ($img_id) {
        $img_src = wp_get_attachment_url($img_id);
      } else {
        $img_src = '';
      }
      $content = sprintf("<style>#vmap {background-image:url(\"%s\");background-size:cover;}</style>" .
        "%s<div id=\"davestates-map\" class=\"entry-content davestates-map\">" .
        "    <div id=\"vmap\" class=\"map\" style=\"width: 600px; height: 400px;\"></div>" .
        "   </div>", $img_src, $content
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
    //$postid = $post->ID;
    $statename = get_query_var('state');
    $statename = preg_replace('/\-/', ' ', $statename );

    if ($statename == '') $statename = 'United States';

    //error_log(sprintf('%s - statename -> %s', __FUNCTION__, $statename));

    $selectedTableIds = get_post_meta($post->ID, 'davestates_tableids', true);

    //$statecode = $state;
    $tableshtml = '<div id="davestates-tables">';

    if ($post->post_type == 'davestates_statemap') {
      $tables = self::get_tablepress_tables($post->ID);
      //$content.=print_r($tables,1);
      foreach ($tables as $tid => $tablename) {
        if ($tid == 0) continue;
        // DEBUG CODE BELOW
        //$table = TablePress::$controller->model_table->load($tid);
        //$data = print_r($table['data'],1);

        $use_responsive = (isset($selectedTableIds[$tid]['responsive'])) ? $selectedTableIds[$tid]['responsive'] : "1";
        $flip = ($use_responsive=="1") ? "responsive='flip'" : "";
        $tablecode = sprintf("[table id=%s davestates-state='%s' %s /]", $tid, $statename, $flip);
        $tableshtml = sprintf("%s<div class='entry-content davestates-table'>
                %s
           </div>", $tableshtml, $tablecode);
      }
    }
    $tableshtml.='</div>';
    $content = sprintf("%s %s %s", $content, $tableshtml, Davestates::footer());

    //$content.=Davestates::footer();

    return $content;
  }

  public static function save_statemap_meta_fields($post_id) {
    global $post;

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
      return;
    }
    // Quick exit if not statemap post type
    if ((isset ($_POST['post_type'])) && ('davestates_statemap' == $_POST['post_type'])) {
      // Verify Nonce
      // Verify Post Type
      if (!current_user_can('edit_post', $post_id)) {
        return;
      }
    } else {
      return;
    }

    // Update Footer
    $nonceName = 'davestates_statemap_meta_footer';
    if (isset($_POST[$nonceName]) && wp_verify_nonce($_POST[$nonceName], "save")) {
      update_post_meta($post_id, 'davestates_statemap_footer', $_POST['davestates_statemap_footer']);
    }

    // Update Table IDS
    $nonceName = 'davestates_statemap_meta_tableids';
    if (wp_verify_nonce($_POST[$nonceName], "save")) {
      $tables = self::get_tablepress_tables();
      $selected_tables = array();
      //get_post_me
      foreach ($tables as $tableid => $tablename) {
        //saves bob's value
        if ($tableid == 0) continue;
        $stable = "davestates-table-" . $tableid;
        $weight_field = $stable . '--weight';
        $responsive_field = $stable . '--responsive';
        if (isset($_POST[$stable])) {
          // Get responsive
          $responsive = (isset($_POST[$responsive_field])) ? (int) $_POST[$responsive_field] : true;

          // Weight will equal value from field or be false
          $weight = (isset($_POST[$weight_field])) ? (int) $_POST[$weight_field] : 100;

          $selected_tables[$tableid] = array(
            'weight' => $weight,
            'responsive' => $responsive
          );
        }
      }

      // Make sure array is sorted by weight
      // Obtain a list of columns
      foreach ($selected_tables as $key => $row) {
        $weight_arr[$key] = $row['weight'];
        $responsive_arr[$key] = $row['responsive'];
      }
      $selected_keys = array_keys($selected_tables);
      // Sort the data with weight ascending and responsive descending
      // Add $data as the last parameter, to sort by the common key
      array_multisort($weight_arr, SORT_ASC, SORT_NUMERIC,
        $responsive_arr, SORT_DESC, SORT_NUMERIC,
        $selected_tables,
        $selected_keys);
      $selected_tables = array_combine($selected_keys, $selected_tables);

      // Update the post meta data
      update_post_meta($post_id, 'davestates_tableids', $selected_tables);

      // Delete table cache of post
      Davestates::delete_post_cache(sprintf('davestates_tables%s', $post_id));
    }

    $nonceName = 'davestates_statemap_meta_background_image';
    if (wp_verify_nonce($_POST[$nonceName], "save")) {
      update_post_meta($post_id, 'davestates_statemap_background_image', $_POST['upload_statemap_image']);
    }

    $nonceName = "davestates_statemap_meta_hover_colors";
    if (wp_verify_nonce($_POST[$nonceName], "save")) {

      $hoverColorsPost = $_POST['davestates_statemap_hover_colors'];

      $hoverColors = array_filter($hoverColorsPost, function($var){return ($var!=='');});

      update_post_meta($post_id, 'davestates_statemap_hover_colors', $hoverColors);
    }

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

    add_meta_box(
      'davestates_statemap_background_image',
      "Statemap Background Image",
      array(__CLASS__, 'metabox_statemap_background_image'),
      'davestates_statemap',
      'normal',
      'default'
    );

    add_meta_box(
      'davestates_statemap_footer',
      "Statemap Footer",
      array(__Class__, 'metabox_statemap_footer'),
      'davestates_statemap',
      'normal',
      'default'
    );

    add_meta_box(
      'davestates_statemap_hover_colors',
      "Statemap Hover Colors",
      array(__Class__, 'metabox_statemap_hover_colors'),
      'davestates_statemap',
      'normal',
      'default'
    );
  }

  public static function metabox_statemap_hover_colors() {
    global $post;
    $nonceName = 'davestates_statemap_meta_hover_colors';
    wp_nonce_field('save', $nonceName);

    $hoverColors = get_post_meta($post->ID, 'davestates_statemap_hover_colors', true);

    /*if ($hoverColors) {
      echo print_r($hoverColors,1);
    }*/

    echo "<div id=\"statemap-hover-colors-container\">";

    $countries = Davestates::get_country_codes();
    echo "<table><tr><th>Country/State</th><th>Color</th></tr>";
    echo "<tbody>";
    foreach ($countries as $name => $cc) {
      $cc = strtolower($cc);
      $hoverColor = isset ($hoverColors[$cc]) ? $hoverColors[$cc] : '';
      $fieldname = sprintf("davestates_statemap_hover_colors[%s]",strtolower($cc));
      $fieldid = sprintf("davestates_statemap_hover_colors_%s",$cc);
      echo "<tr>";
      echo sprintf("  <td>%s</td><td>
            <input id=\"%s\" name=\"%s\" value='%s' type=\"text\" class=\"colorSelector\" /></td>",
        $name,
        $fieldid,
        $fieldname,
        $hoverColor);
      echo "</tr>";
    }
    echo "</tbody></table>";
    echo "</div>";

  }

  /**
   *
   */
  public static function metabox_statemap_background_image() {
    global $post;
    $nonceName = 'davestates_statemap_meta_background_image';
    wp_nonce_field('save', $nonceName);

    $img_id = get_post_meta($post->ID, 'davestates_statemap_background_image', true);
    Davestates::get_country_codes();
    ?>

    <label for="upload_statemap_image">
      <input id="upload_statemap_image" type="text" size="36" name="upload_statemap_image" value="<?php echo $img_id; ?>" />
      <input id="upload_statemap_image_button" type="button" value="Upload Image" />
      <br />Enter an URL or upload an image for the banner.
    </label>
    <?php
  }


  /**
   *
   */
  public static function metabox_statemap_footer() {
    global $post;
    $nonceName = "davestates_statemap_meta_footer";
    wp_nonce_field('save', $nonceName);
    $statemap_footer = get_post_meta($post->ID, 'davestates_statemap_footer', true);
    echo "<div id=\"poststuff\">";
    wp_editor($statemap_footer,'davestates_statemap_footer');
    echo "</div>";
  }


  /**
   * Create Statemap Post Specific Settings / Meta Fields
   */
  public static function metabox_statemap_tableids($post_id) {
    global $post;

    $tables = self::get_tablepress_tables();

    // If no tables do not show the metabox
    if (count($tables) < 1) return;

    $nonceName = "davestates_statemap_meta_tableids";
    wp_nonce_field('save', $nonceName);

    $checkboxMeta = get_post_meta($post->ID);

    $selectedTableIds = isset($checkboxMeta['davestates_tableids']) ?
      unserialize($checkboxMeta['davestates_tableids'][0]) : array();

    //echo print_r($tables, 1);
    // Create HTML table to output Tablepress table selectors and weights
    echo print_r($selectedTableIds, 1);
    echo '<table class="davestates-table-settings">';
    echo '<tbody>';
    echo '<tr><th>Table</th><th>Weight</th><th>Responsive?</th></tr>';

    foreach ($tables as $tableid => $tablename) {
      echo '<tr>';
      $stable = "davestates-table-".$tableid;
      $checked = array_key_exists($tableid, $selectedTableIds) ? checked(true, true , false) : '';
      echo sprintf(
        '<td><input type="checkbox" name="%s" id="%s" value="%s" %s/>%s</td>',
        $stable,
        $stable,
        $tableid,
        $checked,
        $tablename);

      $weight_field = $stable."--weight";
      $weight = (isset($selectedTableIds[$tableid]['weight'])) ? $selectedTableIds[$tableid]['weight'] : 100;

      echo sprintf(
        '<td><input type="text" class="davestates-weight" title="Lower Values are listed first on state map" name="%s" id="%s" value="%s"/></td>',
        $weight_field,
        $weight_field,
        $weight);

      $responsive_field = $stable."--responsive";
      $use_responsive = (isset($selectedTableIds[$tableid]['responsive'])) ? $selectedTableIds[$tableid]['responsive'] : true;
      $checked = checked($use_responsive, true , false);
      echo sprintf(
        '<td><input type="checkbox" name="%s" id="%s" value="%s" %s/>%s</td>',
        $responsive_field,
        $responsive_field,
        '1',
        $checked,
        'Use Responsive Table?'
      );

      echo '</tr>';
    }
    echo '</tbody>';
    echo '</table>';
  }


  /**
   * Get all the table press tables or the tables used by a statemap
   *
   * @param $postid int|bool false or postid of statemap to load selected tables
   *
   * @returns array
   */
  public static function get_tablepress_tables($postid = false) {

    //error_log(sprintf('get_tablepress_tables - Post ID %s', $postid));
    if (!$postid) {
      $postidtag = "";
    } else {
      $postidtag = $postid;

      // Load the Selected Table Ids from
      if ( in_array('davestates_tableids', get_post_custom_keys($postid))) {
        $selectedTableIds = get_post_meta($postid, 'davestates_tableids', true);
      }
    }

    // Get the Cache if it exists
    $tablesArr = wp_cache_get(sprintf('davestates_tables%s',$postidtag),'davestates');
    if ($tablesArr) return $tablesArr;

    // If Selected Table Ids we don't need to load tables we already know ids
    $tables = (isset($selectedTableIds)) ? array_keys($selectedTableIds) : TablePress::$controller->model_table->load_all();

    $tablesArr = array();
    foreach ($tables as $table_id ) {

      // Load each wordpress table
      $table = TablePress::$controller->model_table->load($table_id);
      // Get the name of each table and it's ID as an array to return
      $stateheader = $table['data'][0][0];

      // filter to only show tables with a column 1 name = State
      if (false !== stripos($stateheader, "State")) {
        $tablesArr[$table_id] = $table['name'];
      }
    }

    // Cache this function
    wp_cache_add(sprintf('davestates_tables%s', $postidtag), $tablesArr, 'davestates', 300);
    return $tablesArr;
  }

  public static function admin_print_scripts() {
    global $post;
    $postType = get_post_type();

    if ($postType == 'davestates_statemap') {
      wp_enqueue_script('wp_editor');
      wp_enqueue_script('thickbox');
      wp_enqueue_script('media-upload');
      wp_enqueue_script('wp-color-picker');
      wp_register_script('statemap-upload', plugins_url("js/statemap-admin.js", DAVESTATES__FILE__), array('jquery', 'media-upload', 'thickbox', 'wp-color-picker'), "1.1", true);
      wp_enqueue_script('statemap-upload');

    }
  }

  public static function admin_print_styles() {
    $postType = get_post_type();

    if ($postType == 'davestates_statemap') {
      wp_enqueue_style('thickbox');
      wp_enqueue_style('wp-color-picker');
    }
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

    //error_log(sprintf('Here is the Post Type - %s', $postType));

    error_log("TABLE".print_r($table,1));

    if ($postType == 'davestates_statemap') {
    //if (is_singular('davestates_statemap')) {
      //if (empty($render_options['davestates-state'])) {
      //  return $table;
      //} //elseif ($render_options['davestates-state'] == 'all') {
        //return $table;
      //}

      //return false;
      $hidden_rows = array();

      $statename = (!empty($render_options['davestates-state'])) ?
        $render_options['davestates-state'] : 'xxx';

      $statename = preg_replace('/\-/', ' ', $statename );

      //$states = explode( ',', $options['states']);

      $rows = $table['data'];

      $doTotals = false; // TODO get Total boolean from statemap settings

      $pattern = '/\{(\W*(?i)state(?-i)\W*)\}/';

      $totals_rows = array();

      $tablename = $table['name'];
      $table['name'] = preg_replace($pattern, ucwords($statename), $tablename);
      $description = $table['description'];
      $table['description'] = preg_replace($pattern, ucwords($statename), $description);

      $last_row_key = count($rows) - 1;
      foreach ($rows as $key => $row) {

        // Look through each $column for the {State} value and replace it
        foreach ($row as $colKey => $column) {
          $table['data'][$key][$colKey] = ucwords(preg_replace($pattern, $statename, $column, 1));
        }

        if (($key === 0 && $render_options['table_head']) ||
          ($last_row_key === $key && $render_options['table_foot'])
        ) continue;

        if ((stripos($row[0], 'united states') === 0) ||
            (stripos($row[0], 'nationwide') === 0 ) ||
            (stripos($row[0], 'usa') === 0 )){
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

        if (stripos($row[0], $statename) !== 0) {
          $hidden_rows[] = $key;
        }
      }
      foreach ($hidden_rows as $key) {
        unset($table['data'][$key]);
        unset($table['visibility']['rows'][$key]);
      }

      if ($doTotals) {
        if ($render_options['table_foot']) {
          // Remove last item from table and apply after totals
          $last_row = array_pop($table['data']);
          $last_vis = array_pop($table['visibility']['rows']);
        }
        foreach ($totals_rows as $row) {
          $new_row = array(count($row) - 1);
          $new_row[1] = ucwords($row[0]);
          $table['data'][] = $new_row;
          $table['visibility']['rows'][] = true;
          $table['data'][] = $row;
          $table['visibility']['rows'][] = true;
        }

        if ($render_options['table_foot']) {
          // Remove last item from table and apply after totals
          $table['data'][] = $last_row;
          $table['visibility']['rows'][] = $last_vis;
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
   * Rocketscript Fix for Scripts
   */
  public static function rocket_loader_attributes_start() {
    ob_start();
  }

  /**
   * Rocketscript Fix for Scripts
   */
  public static function rocket_loader_attributes_end() {
    $script_out = ob_get_clean();
    $script_out = str_replace(
      "type='text/javascript' src='{rocket-ignore}",
      'data-cfasync="false"'." src='",
      $script_out);
    print $script_out;
  }

  /**
   * Rocketscript Fix for Scripts
   *
   * @param $url
   *
   * @return string
   */
  public static function rocket_loader_attributes_mark($url) {
    $dir = dirname(__FILE__);
    $plugin_dir = plugin_dir_url($dir);
    // Set up which scripts/strings to ignore
    $ignore = array (
      '/wp-includes/js/jquery/jquery.js',
      $plugin_dir.'js/jqvmap/jquery.vmap.js',
      $plugin_dir.'js/jqvmap/maps/jquery.vmap.usa.js',
      $plugin_dir.'js/statemap.js'
    );
    //matches only the script file name
    preg_match('/(.*)\?/', $url, $_url);
    if (isset($_url[1]) && substr($_url[1], -3)=='.js') {
      foreach($ignore as $s) {
        if (strpos($_url[1], $s)!==false)
          return "{rocket-ignore}$url";
      }
      return "$url' data-cfasync='true";
    }

    return "$url";
  }

  /**
   * Load Script and Stylesheet for custom Post Type
   */
  public static function statemap_enqueue_scripts() {
    global $post;
    global $post_type;
    //$dir = dirname(__FILE__);
    //if (is_singular('davestates_statemap')) {
    if ($post_type == 'davestates_statemap') {
      wp_register_style('statemap-style', plugins_url("css/statemap.css", DAVESTATES__FILE__), false, '1.4', true);
      wp_enqueue_style('statemap-style');

      wp_enqueue_script('jquery');

      // Load jvqmap Javascript
      wp_register_script('statemap-vmap', plugins_url("js/jqvmap/jquery.vmap.js", DAVESTATES__FILE__), array('jquery'), '1.4', true);
      wp_enqueue_script('statemap-vmap');

      // Load jqvmap usa map javascript
      wp_register_script('statemap-usa', plugins_url("js/jqvmap/maps/jquery.vmap.usa.js", DAVESTATES__FILE__), array('jquery', 'statemap-vmap'), '1.4', true);
      wp_enqueue_script('statemap-usa');

      //
      $statename = get_query_var('state');
      $state = self::get_state($statename);

      if ($state === false) {
        $statecode = '';
      } else {
        $statecode = $state['statecode'];
      }

      $hoverColors = get_post_meta($post->ID, 'davestates_statemap_hover_colors', true);

      if (!$hoverColors) $hoverColors = array();

      wp_register_script('davestates-statemap-script', plugins_url("js/statemap.js", DAVESTATES__FILE__), array('jquery', 'statemap-usa', 'statemap-vmap'), '1.4', true);
      wp_localize_script('davestates-statemap-script', 'statemap_params', array(
        'hoverColor' => '#3300ff',
        'hoverColors' => $hoverColors,
        'backgroundColor' => '#000000',
        'selectedColor' => '#0033ff',
        'statemapUrl' => get_permalink($post->ID),
        'statecode' => $statecode
      ));
      wp_enqueue_script('davestates-statemap-script');
    }
  }


  public static function delete_post_cache($key){
    // Delete Cache for this tables/post
    wp_cache_delete(sprintf($key),'davestates');
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
   * Read the jvqmap country map file and extract countries and names
   * @param string $country
   */
  public static function get_country_codes($country = 'usa') {
    $file_path = plugins_url(sprintf("js/jqvmap/maps/jquery.vmap.%s.js", $country), DAVESTATES__FILE__);
    $country_file = file($file_path);
    $states = array();
    $data = false;

    foreach($country_file as $line) {
      if (strpos($line, 'vectorMap') != false) {
        $start = strpos($line, "{");
        $end = strrpos($line, "}") + 1;
        $json = substr($line, $start, $end-$start);
        $data = json_decode($json, true);
        break;
      }
    }

    // We found country codes
    if ($data) {
      foreach ($data['paths'] as $statecode => $row) {
        $states[$row['name']] = strtoupper($statecode);
      }
    }

    ksort($states);
    return $states;
  }

  /**
   * Get all the states and cache them
   *
   * @DEPRECIATED use get_country_codes() instead
   * @see Davestates::get_country_codes:
   *
   * @return array|bool|mixed|null|object
   */
  public static function get_states($country = 'usa') {
    // Cache this function
    $states = wp_cache_get(sprintf('davestates_states%s',$country),'davestates');

    if ( false == $states ) {
      $states = Davestates::get_country_codes($country);

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

  /**
   * Print the statemap footer
   */
  public static function footer() {
    global $post;
    $footer = get_post_meta($post->ID, 'davestates_statemap_footer', true);
    if ($footer) {
      return $footer;
    }
  }
}

