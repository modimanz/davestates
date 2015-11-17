<?php

//if uninstall not called from WordPress exit
//if ( !defined( 'WP_UNINSTALL_PLUGIN' ) )
//exit();


//function davestates_deactivate() {

  $option_name = 'davestates_db_version';

  delete_option( $option_name );

  // For site options in multisite
  //delete_site_option( $option_name );

  //drop a custom db table
  global $wpdb;

  $e = $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}davestates" );
  die(var_dump($e));

  $e = $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}davestatescategories" );
  die(var_dump($e));

  $e = $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}davestatescategory" );
  die(var_dump($e));

  $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}davestatessubcategory" );
  $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}davestatessubcategories" );
  $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}davestatesdata" );

  $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}davestatespages" );
  $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}davestatesreferences" );
//}
//note in multisite looping through blogs to delete options on each blog does not scale. You'll just have to leave them.

//register_deactivation_hook(__FILE__, 'davestates_deactivate');