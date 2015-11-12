
<?php

//if uninstall not called from WordPress exit
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) )
exit();

$option_name = 'davestates_db_version';

delete_option( $option_name );

// For site options in multisite
delete_site_option( $option_name );

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

//note in multisite looping through blogs to delete options on each blog does not scale. You'll just have to leave them.