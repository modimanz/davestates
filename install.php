<?php

// Security
// Block direct access to the plugin
defined( 'ABSPATH' ) or die( 'Action not allowed bub.' );

// Data Structure
global $davestates_db_version;
$davestates_db_version = '1.0';

//  Table: statecategories - Categories of state page data
//    fields:   id
//              name - text name of category
//              url - path to category
//              description - text description of category
//  Table: statepages - Link to State and Category
//     fields:  id
//              stateid - integer state id
//              categoryid - integer category id
//              url - text path to page relative to base
//              html - blob page html data
//  Table: statesubcategories
//     fields:  id
//              categoryid - integer category id
//              headers - blob - integer key array of header names
//              active - boolean - is this subcat activ
//  Table: statedata
//    fields:   id
//              subcatid - integer sub category id
//              data - blob - integer key (column) array of subcat data / row
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

  require_once( ABSPATH . 'wp-admin/includes/upgrades.php');

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

  // State Categories // USING POST TYPE INSTEAD
  /**$cat_table_name = $wpdb->prefix . 'davestatescategories';
  $cat_sql = "CREATE TABLE $cat_table_name (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    name tinytext NOT NULL,
    description text NOT NULL,
    url tinytext NOT NULL,
    UNIQUE KEY id (id)
    ) $charset_collate;";
  dbDelta( $cat_sql);**/

  // StateMap Categories  // Previously Called Subcategories
  $cat_table_name = $wpdb->prefix . 'davestatescategories';
  $cat_sql = "CREATE TABLE $cat_table_name (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    categoryid mediumint(9) NOT NULL,
    headers blob NOT NULL,
    active tinyint(1) DEFAULT 0,
    FOREIGN KEY (categoryid) REFERENCES $cat_table_name (id) ON DELETE CASCADE,
    UNIQUE KEY id (id)
    ) $charset_collate;";
  //     fields:  id
  //              categoryid - integer category id
  //              headers - blob - integer key array of header names
  //              active - boolean - is this subcat activ
  dbDelta( $cat_sql);

  // State Data
  $data_table_name = $wpdb->prefix . 'davestatesdata';
  $data_sql = "CREATE TABLE $data_table_name (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    catid mediumint(9) NOT NULL,
    stateid mediumint(9) NOT NULL,
    data blob NOT NULL,
    FOREIGN KEY (subcatid) REFERENCES $subcat_table_name (id) ON DELETE CASCADE,
    FOREIGN KEY (stateid) REFERENCES $states_table_name (id) ON DELETE CASCADE,
    UNIQUE KEY id (id)
    ) $charset_collate;";
  //    fields:   id
  //              subcatid - integer sub category id
  //              data - blob - integer key (column) array of subcat data / row
  dbDelta( $data_sql);

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

register_activation_hook( __FILE__, 'davestates_install');
register_activation_hook( __FILE__, 'davestates_install_data');