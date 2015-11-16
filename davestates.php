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


// Define certain plugin variables as constants.
define( 'DAVESTATES_ABSPATH', plugin_dir_path( __FILE__ ) );
define( 'DAVESTATES__FILE__', __FILE__ );
define( 'DAVESTATES_BASENAME', plugin_basename( DAVESTATES__FILE__ ) );

/**
 * Load Includes which holds common functions and variables.
 */
require_once DAVESTATES_ABSPATH . 'classes/class-davestates.php';

// Start up TablePress on WordPress's "init" action hook.
add_action( 'init', array( 'Davestates', 'run' ) );

// Include Settings Pages
// TODO create urls to admin pages
include( DAVESTATES_ABSPATH . 'settings.php');

// Include Install Functions
include( DAVESTATES_ABSPATH . 'install.php');