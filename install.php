<?php

// Security
// Block direct access to the plugin
defined( 'ABSPATH' ) or die( 'Action not allowed bub.' );

// Data Structure
global $davestates_db_version;
$davestates_db_version = '1.2';

//  Table: states - states
//
//    fields:   id
//              name - text State Name
//              statecode - 2 Character State code


function davestates_install() {
  global $wpdb;
  global $davestates_db_version;

  // TODO Verify Each Table as we go
  // TODO Rollback Table Creation on error

  // After version 1.0 use code below
  /*
  $installed_ver = get_option("davestates_db_version")
  if ($installed_ver != $davestates_db_version) {
       ...... table code here instead of below
  }
  */

  require_once( ABSPATH . 'wp-admin/includes/upgrade.php');

  $charset_collate = $wpdb->get_charset_collate();

  // States
  $states_table_name = $wpdb->prefix . 'davestates';
  $states_sql = "CREATE TABLE $states_table_name (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    name tinytext NOT NULL,
    statecode CHAR(2) NOT NULL,
    UNIQUE KEY id (id)
    ) $charset_collate;";
  dbDelta( $states_sql);

  add_option( 'davestates_db_version', $davestates_db_version);
}

/**
 * Install Data to DaveStates Tables
 */
function davestates_install_data() {

global $wpdb;

$table_name = $wpdb->prefix . "davestates";

$wpdb->query("INSERT INTO $table_name
  values
  (NULL, 'Alabama', 'AL'),
  (NULL, 'Alaska', 'AK'),
  (NULL, 'Arizona', 'AZ'),
  (NULL, 'Arkansas', 'AR'),
  (NULL, 'California', 'CA'),
  (NULL, 'Colorado', 'CO'),
  (NULL, 'Connecticut', 'CT'),
  (NULL, 'Delaware', 'DE'),
  (NULL, 'District of Columbia', 'DC'),
  (NULL, 'Florida', 'FL'),
  (NULL, 'Georgia', 'GA'),
  (NULL, 'Hawaii', 'HI'),
  (NULL, 'Idaho', 'ID'),
  (NULL, 'Illinois', 'IL'),
  (NULL, 'Indiana', 'IN'),
  (NULL, 'Iowa', 'IA'),
  (NULL, 'Kansas', 'KS'),
  (NULL, 'Kentucky', 'KY'),
  (NULL, 'Louisiana', 'LA'),
  (NULL, 'Maine', 'ME'),
  (NULL, 'Maryland', 'MD'),
  (NULL, 'Massachusetts', 'MA'),
  (NULL, 'Michigan', 'MI'),
  (NULL, 'Minnesota', 'MN'),
  (NULL, 'Mississippi', 'MS'),
  (NULL, 'Missouri', 'MO'),
  (NULL, 'Montana', 'MT'),
  (NULL, 'Nebraska', 'NE'),
  (NULL, 'Nevada', 'NV'),
  (NULL, 'New Hampshire', 'NH'),
  (NULL, 'New Jersey', 'NJ'),
  (NULL, 'New Mexico', 'NM'),
  (NULL, 'New York', 'NY'),
  (NULL, 'North Carolina', 'NC'),
  (NULL, 'North Dakota', 'ND'),
  (NULL, 'Ohio', 'OH'),
  (NULL, 'Oklahoma', 'OK'),
  (NULL, 'Oregon', 'OR'),
  (NULL, 'Pennsylvania', 'PA'),
  (NULL, 'Rhode Island', 'RI'),
  (NULL, 'South Carolina', 'SC'),
  (NULL, 'South Dakota', 'SD'),
  (NULL, 'Tennessee', 'TN'),
  (NULL, 'Texas', 'TX'),
  (NULL, 'Utah', 'UT'),
  (NULL, 'Vermont', 'VT'),
  (NULL, 'Virginia', 'VA'),
  (NULL, 'Washington', 'WA'),
  (NULL, 'West Virginia', 'WV'),
  (NULL, 'Wisconsin', 'WI'),
  (NULL, 'Wyoming', 'WY')
  ");

}

register_activation_hook( __FILE__ , 'davestates_install');
register_activation_hook( __FILE__ , 'davestates_install_data');


function davestates_update_db_check() {
  global $davestates_db_version;

  if ( get_site_option('davestates_db_version') != $davestates_db_version) {
    davestates_install();
    davestates_install_data();
  }

  update_option( 'davestates_db_version', $davestates_db_version);
}
add_action('plugins_loaded', 'davestates_update_db_check');