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
include( plugin_dir_path( __FILE__ ) . 'data.php');


// Include Settings Pages
include( plugin_dir_path( __FILE__ ) . 'settings.php');

// Include Settings Pages
include( plugin_dir_path( __FILE__ ) . 'statemap.php');

// TODO create urls to admin pages
// TODO Example @ https://gist.github.com/kasparsd/2924900
// TODO USE add_permastruct
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

// TODO create urls to statepages
//    FOR EACH BELOW
//    /states/{statename}/{category->url}/   OR statepage->url

// TODO Allow user to upload a CSV file into the statedata->data field

// TODO foreach Category Create a Statemap -
// TODO foreach Category/State combo create a URL to davestate_data_page($catid, stateid)


/**
 * Generate a page for each state for the category.
 *
 * @param $catid
 * @param stateid $
 */
function davesatate_generate_pages($category) {

}

function davestate_generate_page_urls() {
  // TODO for each page make a url link to
}

function davestate_data_page($pageid) {

}




/**
 * Import the csv file into subcategory
 *
 * @param $file
 * @param $type
 * @param int $subcatid
 * @param bool|false $overwrite
 * @return bool
 */
function davestate_import_csv($file, $type, $subcat, $overwrite = false) {
  $status = false;

  $import_rows = array();

  $subcatid = $subcat->id;

  // Cannot continue without a subcat id
  if ($subcatid == 0) return false;

  // TODO Looks like this is a new subcat

  if ($overwrite) {
    // TODO Delete davestates_data rows with subcatid


  } else {
    // Do not continue if data laready exists
    // TODO if davestates_data count > 0 for subcatid return false
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
              // TODO Get title
              $title = $data[0];
              break;
        case 2:
              // Header Row
              // TODO Get Field headers
              // TODO Get all rows starting with row 3 as array
              $headers = $data; // TODO get rid of first two array items
              // TODO Serialize the array
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
            // TODO Column One should be statecode
            // TODO Column two should be state
            switch ($c) {
              case 0;
                $statecode = $data[$c];
                break;
              case 1;
                $state_name = $data[$c];
                break;
              default:
                // TODO Finish adding fields to array
                $fields[$c] = $data[$c];
            }
          }
          $fields_string = serialize($fields);

          $import_rows = array($subcatid, $statecode, $state_name, $fields_string);
      }

      $row++;

    }
    fclose($handle);

    // TODO update field headers
    $subcat->title = $title;
    $subcat->headers = $headers_text;

    // TODO Import the import rows into davestates_data
  }

  return $status;
}