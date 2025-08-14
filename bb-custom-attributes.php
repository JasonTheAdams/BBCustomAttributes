<?php
/**
 * Plugin Name: Beaver Builder Custom Attributes
 * Plugin URI: https://github.com/JasonTheAdams/BBCustomAttributes
 * Description: Adds the ability to set custom attributes for modules, columns, and rows
 * Version: 1.3.1
 * Author: Jason Adams
 * Author URI: https://github.com/jasontheadams
 * Requires PHP: 5.6
 * License: MIT
 * License URI: https://opensource.org/licenses/MIT
 */

namespace JasonTheAdams\BBCustomAttributes;

// Define plugin version
define( 'BBCUSTOMATTRIBUTES_VERSION', '1.3.1' );

// Include core plugin functionality
include_once plugin_dir_path( __FILE__ ) . 'includes/BBCustomAttributes.php';

// Include GitHub updater functionality
include_once plugin_dir_path( __FILE__ ) . 'includes/GithubUpdater.php';

// Initialize the plugin
(new BBCustomAttributes())->load();

// Initialize GitHub updater
function init_updater() {
    $updater = new GithubUpdater( __FILE__ );
    $updater->set_username( 'JasonTheAdams' );
    $updater->set_repository( 'BBCustomAttributes' );
    $updater->set_settings( array(
        'requires'        => '5.6',
        'tested'          => '6.6.1',
        'rating'          => '100.0',
        'num_ratings'     => '10',
        'downloaded'      => '10',
        'added'           => '2024-08-22',
    ) );
    $updater->initialize();
}
init_updater();
