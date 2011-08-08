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

if ( version_compare( PHP_VERSION, '5.0', '<' ) ) {
	add_action('admin_notices', '_watermark_mini_site_php_warning');
} else {

	if ( !defined( 'ENABLE_NETWORK_ZERO' ) )
		define( 'ENABLE_NETWORK_ZERO', true );

	if ( !defined( 'RESCUE_ORPHANED_BLOGS' ) )
		define( 'RESCUE_ORPHANED_BLOGS', false );

	/* blog options affected by URL */
	$options_list = array( 'siteurl', 'home', 'fileupload_url' );

	/* sitemeta options to be copied on clone */
	$options_to_copy = array(
		'admin_email'				=> __( 'Network admin email' ),
		'admin_user_id'				=> __( 'Admin user ID - deprecated' ),
		'allowed_themes'			=> __( 'OLD List of allowed themes - deprecated' ),
		'allowedthemes'				=> __( 'List of allowed themes' ),
		'banned_email_domains'		=> __( 'Banned email domains' ),
		'first_post'				=> __( 'Content of first post on a new blog' ),
		'limited_email_domains'		=> __( 'Permitted email domains' ),
		'site_admins'				=> __( 'List of network admin usernames' ),
		'welcome_email'				=> __( 'Content of welcome email' )
	);

	define( 'NETWORKS_PER_PAGE', 10 );

	// Load required class definitions.
	require_once( 'lib/class-watermark-mini-site.php' );
	require_once( 'lib/class-ms-networks.php' );

	// Initialize all of the plugin's hooks.
	Watermark_Mini_Site::init();
	MS_Networks::init();
}
?>