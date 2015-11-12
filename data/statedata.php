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
  public static function get_state_data($state = false, $categoryid = false) {
    global $wpdb;
    $rows = array();
    $stateArr = Davestates_StateData::get_state($state);
    $sql = "SELECT * FROM {$wpdb->prefix}davestatesdata";
    if (isset($stateArr['id']) || ($categoryid != false)) {
      $sql.=" Where ";
    }
    $sql.=(isset($stateAttr['id'])) ? sprintf("id = %s", $stateArr['id']) : "";
    $sql.=($categoryid != false ) ? sprintf("categoyid = %s", $categoryid) : "";
    $rows = $wpdb->get_results($sql, 'ARRAY_A');
    return $rows;
  }

  /**
   * Insert multiple rows of data into the State Data table
   * @param $stateid
   * @param $postid
   * @param $rows
   *
   * @return bool|int
   */
  public static function insert_data_multiple($categoryid, $rows) {
    global $wpdb;
    $sql = "INSERT INTO {$wpdb->prefix}davestatesdata (categoryid, stateid, data) ";
    foreach ($rows as $row) {
      $statecode = $row['statecode'];
      $state = Davestates_StateData::get_state($statecode);
      $stateid = $state['id'];
      $sql.="(";
      $sql.=sprintf("'%s', '%s', '%s'", $categoryid, $stateid, $row['fields']);
      $sql.="),";
    }
    $sql = substr($sql, 0, -1);

    return $wpdb->query($sql);
  }

  /**
   * @param $categoryid
   * @param $row
   * @return bool|int
   */
  public static function insert_data($categoryid, $row) {
    return Davestates_StateData::insert_data_multiple($categoryid, array($row));
  }

  /**
   * Insert a new Statemap Category
   *
   * @param $title
   * @param $headers - Serialized array of field headers array('item 1', 'item 2')
   * @param string $sources
   * @param array $rows
   * @param bool|false $postid
   *
   * @return false|int
   */
  public static function insert_category($title, $headers, $sources = "", $rows = array(), $postid = false) {
    global $wpdb;

    $sql = "INSERT INTO {$wpdb->prefix}davestatescategory (name, headers, sources, active) ";
    $sql.=sprintf("( '%s', '%s', '%s', 1 )", $title, $headers, $sources);

    $record_count = $wpdb->query($sql);
    if (!$record_count) {
      return false;
    };


    $insert_id = $wpdb->insert_id;

    if (count($rows) > 0) {
      Davestates_StateData::insert_data_multiple($insert_id, $rows);
    }

    if ($postid) {
      Davestates_StateData::insert_reference($insert_id, $postid);
    }

    return $record_count;
  }

  /**
   * Inserts a davestatesreference record linking a category and a statemap post
   *
   * @param $categoryid
   * @param $postid
   *
   * @return boot|int
   */
  public static function insert_reference($categoryid, $postid) {
    $reference = Davestates_StateData::load_reference($categoryid, $postid);
    if (!is_null($reference)) {
      // Cannot add a duplicate reference
      return false;
    }



  }


  /**
   * Load all references for a category or a postid
   * @param bool|false $categoryid
   * @param bool|false $postid
   * @return array|null|object
   */
  public static function load_references($categoryid = false, $postid = false) {
    global $wpdb;
    $rows = array();
    $sql = "SELECT * FROM {$wpdb->prefix}davestatesreferences";
    if ($categoryid || $postid) {
      $sql.=" Where ";
    }
    $sql.=($categoryid) ? sprintf("categoryid = %s", $categoryid) : "";
    $sql.=($postid) ? sprintf("postid = %s", $postid) : "";
    $rows = $wpdb->get_results($sql, 'ARRAY_A');
    return $rows;
  }

  /**
   * Load a single reference
   *
   * @param $categoryid
   * @param $postid
   * @return mixed|null
   */
  public static function load_reference($categoryid, $postid) {
    $references = Davestates_StateData::load_references($categoryid,$postid);
    return (!is_null($references)) ? reset($references) : null;
  }


}