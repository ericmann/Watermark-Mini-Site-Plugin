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
	echo '  <p>The Daily Meds Widget requires at least PHP 5.  Your system is running version ' . PHP_VERSION . ', which is not compatible!</p>';
	echo '</div>';
}

if ( version_compare( PHP_VERSION, '5.0', '<' ) ) {
	add_action('admin_notices', '_daily_meds_php_warning');
} else {
	// Load required class definitions.
	require_once( 'lib/class-daily-meds.php' );
	require_once( 'lib/class-daily-meds-widget.php' );

	// Initialize all of the plugin's hooks.
	Daily_Meds::init();
}
?>
