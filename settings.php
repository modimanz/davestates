<?php

// Security
// Block direct access to the plugin
defined('ABSPATH') or die('Action not allowed bub.');

// TODO Make statemap settings page / global, default settings

// Includes Settings Pages
class DaveStatesSettingsPage
{
  private $options;

  public function __construct()
  {
    add_action('admin_menu', array( $this, 'add_plugin_page') );
    add_action('admin_init', array( $this, 'page_init') );
  }

  /**
   * Add an Options Page
   */
  public function add_plugin_page()
  {
    add_options_page(
      'Dave State Settings Admin',
      'DaveState Settings',
      'manage_options',
      'davestates-settings-admin',
      array( $this, 'create_admin_page')
    );
  }

  /**
   * Options Page callback
   */
  public function create_admin_page()
  {
    $this->options = get_options('davestatestest');
    ?>
    <div class="wrap">
      <h2>Dave State Data Plugin</h2>
      <form method="post" action="options.php">
        <?php
        settings_fields('davestates-settings-group');
        do_settings_sections('davestates-settings-group');
        submit_button();
        ?>
      </form>
    </div>
    <?php
  }

  public function page_init() {
    register_setting(
      'davestates-settings-group', // Option Group
      'davestatesstatemap',  // Option Name
      array($this, 'sanitize')
    );

    add_settings_section(
      'davestates_settings_id',
      'Dave State Settings',
      array($this, 'print_section_info'),
      'davestates-settings-admin'
    );

    add_settings_field(
      'davestatestest',
      'Dave State Test',
      array($this, 'test_callback'),
      'davestates-settings-admin',
      'davestates_settings_id'
    );
  }

  public function sanitize( $input)
  {
    $new_input = array();
    if( isset( $input['davestatestest'] ) )
      $new_input['davestatestest'] = sanitize_text_field( $input['title'] );
    return $new_input;
  }

  public function print_section_info()
  {
    print 'Enter Your Settings Below:';
  }

  public function davestatetest_callback()
  {
    printf(
      '<input type="text" id="davestatestest" name="davestatestest[davestatestest]" value="%s" />',
      isset( $this->options['davestatestest']) ? esc_attr( $this->options['davestatestest']) : ''
    );
  }

}

if (is_admin() )
  $davestates_settings_page = new DaveStatesSettingsPage();


class SP_Plugin {

// class instance
  static $instance;

// davestates WP_List_Table object
  public $davestate_obj;

// class constructor
  public function __construct() {
    add_filter('set-screen-option', [__CLASS__, 'set_screen'], 10, 3);
    add_action('admin_menu', [$this, 'plugin_menu']);
  }


  public static function set_screen( $status, $option, $value ) {
    return $value;
  }

  public function plugin_menu() {

    $hook = add_menu_page(
      'Sitepoint WP_List_Table Example',
      'SP WP_List_Table',
      'manage_options',
      'wp_list_table_class',
      [ $this, 'plugin_settings_page' ]
    );

    add_action( "load-$hook", [ $this, 'screen_option' ] );

  }

  /**
   * Screen options
   */
  public function screen_option() {

    $option = 'per_page';
    $args   = [
      'label'   => 'Customers',
      'default' => 5,
      'option'  => 'customers_per_page'
    ];

    add_screen_option( $option, $args );

    $this->customers_obj = new Customers_List();
  }

  /**
   * Plugin settings page
   */
  public function plugin_settings_page() {
    ?>
    <div class="wrap">
      <h2>WP_List_Table Class Example</h2>

      <div id="poststuff">
        <div id="post-body" class="metabox-holder columns-2">
          <div id="post-body-content">
            <div class="meta-box-sortables ui-sortable">
              <form method="post">
                <?php
                $this->customers_obj->prepare_items();
                $this->customers_obj->display(); ?>
              </form>
            </div>
          </div>
        </div>
        <br class="clear">
      </div>
    </div>
    <?php
  }

  /** Singleton instance */
  public static function get_instance() {
    if ( ! isset( self::$instance ) ) {
      self::$instance = new self();
    }

    return self::$instance;
  }
}

