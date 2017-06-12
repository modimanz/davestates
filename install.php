<?php

// Security
// Block direct access to the plugin
defined( 'ABSPATH' ) or die( 'Action not allowed bub.' );

// Data Structure
global $davestates_db_version;
$davestates_db_version = '1.2';

/**
 * Install Davestates Plugin
 *
 * TODO Be nice; lets make an uninstall function; Drop Tables; Ask while uninstallling
 */
function davestates_install() {
  global $wpdb;
  global $davestates_db_version;

  require_once( ABSPATH . 'wp-admin/includes/upgrade.php');

  //drop a custom db table
  global $wpdb;
  $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}davestatescategories" );
  $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}davestatescategory" );
  $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}davestatessubcategory" );
  $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}davestatessubcategories" );
  $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}davestatesdata" );
  $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}davestates" );
  $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}davestatespages" );
  $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}davestatesreferences" );

  add_option( 'davestates_db_version', $davestates_db_version);
}

register_activation_hook( __FILE__ , 'davestates_install');

function davestates_update_db_check() {
  global $davestates_db_version;

  if ( get_site_option('davestates_db_version') != $davestates_db_version) {
    davestates_install();
  }

  update_option( 'davestates_db_version', $davestates_db_version);
}
add_action('plugins_loaded', 'davestates_update_db_check');
