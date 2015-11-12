<?php

// Security
// Block direct access to the plugin
defined( 'ABSPATH' ) or die( 'Action not allowed bub.' );

class Davestates_StateData {

  public $items;

  protected $_args;

  public $states;

  public function __construct( $args = array() ) {
    $args = wp_parse_args($args, array(
      'stateid' => '',
      'categoryid' => ''
    ));

    $this->states = $this::get_states();

    $this->_args = $args;
  }

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

  public static function get_state($state) {

    $stateArr = Davestates_StateData::sanitize_state($state);
    $states = Davestates_StateData::get_states();
    $state = false;

    foreach ($states as $key => $row) {
      if ($row[$stateArr['statefield']] == $stateArr['value']) {
        $state = $row;
      }
    }
    return $state;
  }

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
   * Get the state data rows by state and post
   *
   * @param string/integer/bool|false $state
   *    can be statecode / statename / or stateid
   * @param bool|false $postid
   * @return array
   */
  public static function get_state_data($state = false, $postid = false) {
    global $wpdb;
    $rows = array();

    $stateArr = Davestates_StateData::get_state($state);

    $sql = "SELECT * FROM {$wpdb->prefix}davestatesdata";

    if (isset($stateArr['id']) || ($postid != false)) {
      $sql.=" Where ";
    }
    if (isset($stateArr['id'])) {
      $sql.=sprintf("id = %s", $stateArr['id']);
    }
    if ($postid != false) {
      $sql.=sprintf("postid = %s", $postid);
    }

    $rows = $wpdb->get_results($sql, 'ARRAY_A');

    return $rows;
  }

  /**
   * Insert multiple rows of data into the State Data table
   * @param $state
   * @param $postid
   * @param $rows
   */
  public function insert_multiple($state, $postid, $rows) {


  }

  public function insert($state, $postid, $row) {

  }

}