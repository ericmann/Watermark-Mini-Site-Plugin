<?php
/**
Plugin Name: Watermark Mini-Sites
Plugin URI: http://jumping-duck.com
Description: Allow the creation of multiple mini-sites within a WordPress network.
Version: 1.0
Author: Eric Mann
Author URI: http://eamann.com
Tags: multi, site, network, blog, domain
License: GPL3+
*/

/*
 * This plugin borrows heavily and re-uses GPL-compatible code from the WordPress Multi-Network
 * plugin written by David Dean and John James Jacoby.
 */

/* Copyright 2011  Eric Mann, Jumping Duck Media
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 3 or, at
 * your discretion, any later version, as published by the Free
 * Software Foundation.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

/*
 * Sets admin warnings regarding required PHP version.
 */
function _watermark_mini_site_php_warning() {
	echo '<div id="message" class="error">';
	echo '  <p>The Watermark Mini-Site Plugin requires at least PHP 5.  Your system is running version ' . PHP_VERSION . ', which is not compatible!</p>';
	echo '</div>';
}

function _watermark_mini_site_relations_warning() {
	echo '<div id="message" class="error">';
	echo '  <p>The Watermark Mini-Site Plugin depends on the Relation Post Types plugin. Please install it before continuing!</p>';
	echo '</div>';
}

if ( version_compare( PHP_VERSION, '5.0', '<' ) ) {
	add_action( 'admin_notices', '_watermark_mini_site_php_warning' );
} elseif( ! defined('RPT_VERSION') ) {
	add_action( 'admin_notices', '_watermark_mini_site_relations_warning' );
} else {

	if( ! defined('MINI_SITE_PLUGIN_DIR') )
		define( 'MINI_SITE_PLUGIN_DIR', WP_PLUGIN_URL . '/watermark-mini-site' );

	// Load required class definitions.
	require_once( 'lib/class-mini-site.php' );

	// Initialize all of the plugin's hooks.
	add_action( 'init',                      array( 'Mini_Site', 'resident_post_type' ) );
	add_action( 'init',                      array( 'Mini_Site', 'register_sidebar' ) );
	add_action( 'init',                      array( 'Mini_Site', 'register_post_status' ) );
	add_action( 'do_meta_boxes',             array( 'Mini_Site', 'add_headshot' ) );

	add_filter( 'enter_title_here',          array( 'Mini_Site', 'enter_name_here' ) );
	add_filter( 'admin_post_thumbnail_html', array( 'Mini_Site', 'rename_headshot' ), 10 );
}

?>