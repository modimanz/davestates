<?php

/*
Plugin Name: Davestates
Plugin URI: http://URI_Of_Page_Describing_Plugin_and_Updates
Description: State map plugin to pull from spreadsheet data
Version: 1.0
Author: modimanz - Morgan Massens
Author URI: http://modimanz.com
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

// Security
// Block direct access to the plugin
defined( 'ABSPATH' ) or die( 'Action not allowed bub.' );

// Include Install Functions
include( plugin_dir_path( __FILE__ ) . 'install.php');

// Include Database Object Functions
include( plugin_dir_path( __FILE__ ) . 'data/state.php');
include( plugin_dir_path( __FILE__ ) . 'data/categories.php');

// Include Settings Pages
include( plugin_dir_path( __FILE__ ) . 'settings.php');

// Include Settings Pages
include( plugin_dir_path( __FILE__ ) . 'statemap.php');

// TODO create urls to admin pages
// TODO Example @ https://gist.github.com/kasparsd/2924900
//    /davestates/categories/ - View all StatePage Categories
//    /davestates/categories/create - Create New State Page Cat
//    /davestates/categories/#id - View a category
//    /davestates/categories/#id/edit - edit a category
//    /davestates/categories/#id/delete - delete a cataegory
//`   /davestates/categories/#id/subs/create - create new sub category
//    /davestates/categories/#id/subs/#id - list subs and data
//    /davestates/categories/#id/subs/#id/edit - edit a sub category
//`   /davestates/categories/#id/subs/#id/delete - delete a subcategory
//    /davestate/statepages/
//    /davestate/statepages/create
//    /davestate/statepages/#id
//    /davestate/statepages/#id/edit
//    /davestate/statepages/#id/delete

// TODO Allow user to upload a CSV file into the statedata->data field

/**
 * Import the csv file into Category
 *
 * @param $file
 * @param $type
 * @param int $catid
 * @param bool|false $overwrite
 * @return bool
 */
function davestate_import_csv($file, $category, $overwrite = false) {
  $status = false;

  $import_rows = array();

  $catid = $category->id;

  // Cannot continue without a $catid
  if ($catid == 0) return false;

  if ($overwrite) {
    // TODO Delete davestates_data rows with catid
  } else {
    // Do not continue if data already exists
    // TODO if davestates_data count > 0 for catid return false
  }

  // Iterate through the File to get rows to import
  $row = 1;
  if (($handle = fopen($file, "r")) !== FALSE) {
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
      $num = count($data);
      echo "<p> $num fields in line $row: <br /></p>\n";

      switch ($row) {
        case 1:
              // Title Row
              // Get title
              $title = $data[0];
              break;
        case 2:
              // Header Row
              // Get Field headers
              // Get all rows starting with row 3 as array
              $headers = array_slice($data, 2); // get rid of first two array items
              // Serialize the array
              $headers_text = serialize($headers);
              break;
        //case 3:
        //    // Data Type Row
        //    break;
        default:
          // The rest of the rows starting real data
          $fields = array();
          for ($c=0; $c < $num; $c++) {
            echo $data[$c] . "<br />\n";
            // Column One should be statecode
            // Column two should be state
            switch ($c) {
              case 0;
                $statecode = $data[$c];
                break;
              case 1;
                $state_name = $data[$c];
                break;
              default:
                // Finish adding fields to array
                $fields[$c] = $data[$c];
            }
          }
          $fields_string = serialize($fields);

          // Add row to end of import rows
          $import_rows[] = array($catid, $statecode, $state_name, $fields_string);
      }

      $row++;

    }
    fclose($handle);

    // TODO update field headers
    DavestatesCategory_List::update_field_headers($catid);

    $category->title = $title;
    $category->headers = $headers_text;

    // TODO Import the import rows into davestates_data
  }

  return $status;
}

/**
 * Gets a list of states as a wp
 *
 */
function davestates_get_states() {

  $states = wp_cache_get('davesstates_states','davestates');
  $states = false;
  if ( false == $states ) {
    $states = DaveStates_List::get_states();
    wp_cache_add('davestates_states', $states, 'davestates');
  }

  return $states;
}

/**
 * Returns State as a query row
 *
 * @param $state
 * @return mixed
 */
function davestates_get_state($state) {

  $states = davestates_get_states();

  // If 2 characters then this is a statecode
  $field = (strlen((trim($state)) == 2 )) ? "statecode" : "name";

  foreach( $states as $key => $row) {
    if (strtolower($row[$field]) == strtolower($state) ) {
      return $row;
    }
  }

  return array("statecode" => "Not Found: ->".$state, "name" => "Not Found");
}