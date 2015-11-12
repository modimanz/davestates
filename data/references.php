<?php

// Security
// Block direct access to the plugin
defined('ABSPATH') or die('Action not allowed bub.');

if (!class_exists('Davestates_List_Table')) {
  //require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
  require_once(plugin_dir_path( dirname(__FILE__) ) . 'includes/class-davestates-list-table.php');
}

// Table List Classes for objects
class DaveStates_List extends Davestates_List_Table {
  public function __construct() {
    parent::__construct([
      'singular' => __('State', 'sp'),
      'plural' => __('States', 'sp'),
      'ajax' => false
    ]);
  }

  /**
   * Show a list of states
   *
   * @param int $per_page
   * @param int $page_number
   * @return mixed
   */
  public static function get_states($per_page = 10, $page_number = 1) {
    global $wpdb;

    $sql = "SELECT * FROM {$wpdb->prefix}davestates";

    if (!empty ($_REQUEST['orderby'])) {
      $sql .= ' ORDER BY ' . esc_sql($_REQUEST['orderby']);
      $sql .= !empty($_REQUEST['order']) ? ' ' . esc_sql($_REQUEST['order']) : ' ASC';
    }

    $sql .= " LIMIT $per_page";

    $sql .= ' OFFSET ' . ($page_number - 1) * $per_page;

    $result = $wpdb->get_results($sql, 'ARRAY_A');

    return $result;
  }

  /**
   * Delete a state record.
   *
   * @param $id
   */
  public static function delete_state($id) {
    global $wpdb;

    $wpdb->delete(
      "{$wpdb->prefix}davestates",
      ['id' => $id],
      ['%d']
    );
  }

  /**
   * Returns the count of records.
   *
   * @return null|string
   */
  public static function record_count() {
    global $wpdb;

    $sql = "SELECT COUNT(*) FROM {$wpdb->prefix}davestates";

    return $wpdb->get_var($sql);
  }

  public function no_items() {
    _e('No States to display.', 'sp');
  }

  /**
   * Renders the Name Column of the table
   * @param $item
   */
  function column_name($item) {
    $delete_nonce = wp_create_nonce('sp_delete_davestate');

    $title = '<strong></strong>'.$item['name'].'</strong>';

    $actions = [
      'delete' => sprintf( '<a href="?page=%s&action=%s$davestate=%s&_wpnonce=%s">Delete</a>',
        esc_attr( $_REQUEST['page'] ), 'delete', absint( $item['id'] ), $delete_nonce )
    ];

    return $title . $this->row_actions( $actions );
  }

  function column_default($item, $column_name) {
    switch ($column_name) {
      case 'statecode':
      case 'id':
        return $item[$column_name];
      default:
        return print_r($item, true);
    }
  }

  /**
   * Render the bulk edit checkbox
   *
   * @param array $item
   *
   * @return string
   */
  function column_cb($item) {
    return sprintf(
      '<input type="checkbox" name="bulk-delete[]" value="%s" />', $item['id']
    );
  }

  /**
   *  Associative array of columns
   *
   * @return array
   */
  function get_columns() {
    $columns = [
      'cb' => '<input type="checkbox" />',
      'name' => __('Name', 'sp'),
      'statecode' => __('Abbr', 'sp')
    ];

    return $columns;
  }

  /**
   * Columns to make sortable.
   *
   * @return array
   */
  public function get_sortable_columns() {
    $sortable_columns = array(
      'name' => array( 'name', true ),
      'statecode' => array( 'statecode', false )
    );

    return $sortable_columns;
  }

  /**
   * Returns an associative array containing the bulk action
   *
   * @return array
   */
  public function get_bulk_actions() {
    $actions = [
      'bulk-delete' => 'Delete'
    ];

    return $actions;
  }

  /**
   * Handles data query and filter, sorting, and pagination.
   */
  public function prepare_items() {

    $this->_column_headers = $this->get_column_info();

    /** Process bulk action */
    $this->process_bulk_action();

    $per_page     = $this->get_items_per_page( 'davestates_per_page', 5 );
    $current_page = $this->get_pagenum();
    $total_items  = self::record_count();

    $this->set_pagination_args( [
      'total_items' => $total_items, //WE have to calculate the total number of items
      'per_page'    => $per_page //WE have to determine how many items to show on a page
    ] );


    $this->items = self::get_states( $per_page, $current_page );
  }

  public function process_bulk_action() {

    //Detect when a bulk action is being triggered...
    if ( 'delete' === $this->current_action() ) {

      // In our file that handles the request, verify the nonce.
      $nonce = esc_attr( $_REQUEST['_wpnonce'] );

      if ( ! wp_verify_nonce( $nonce, 'sp_delete_davestate' ) ) {
        die( 'Go get a life script kiddies' );
      }
      else {
        self::delete_state( absint( $_GET['davestate'] ) );

        wp_redirect( esc_url( add_query_arg() ) );
        exit;
      }

    }

    // If the delete bulk action is triggered
    if ( ( isset( $_POST['action'] ) && $_POST['action'] == 'bulk-delete' )
      || ( isset( $_POST['action2'] ) && $_POST['action2'] == 'bulk-delete' )
    ) {

      $delete_ids = esc_sql( $_POST['bulk-delete'] );

      // loop over the array of record IDs and delete them
      foreach ( $delete_ids as $id ) {
        self::delete_state( $id );

      }

      wp_redirect( esc_url( add_query_arg() ) );
      exit;
    }
  }
}