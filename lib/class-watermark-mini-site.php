<?php
if ( ! class_exists('Watermark_Mini_Site') ) :

class Watermark_Mini_Site {
	public static function init() {
		
	}

	/**
	 * Check to see if new WPDB functions (insert, update, prepare) are available and cache the result
	 * @return boolean Result of check for these functions
	 */
	public static function are_new_wpdb_funcs_available() {
		static $available = 'unchecked';

		if ( is_bool( $available ) )
			return $available;

		global $wpdb;

		$available = method_exists( $wpdb, 'insert' );

		return $available;
	}

	/**
	 * Check to see if a network exists. Will check the networks object before checking the database.
	 * @param integer $site_id ID of network to verify
	 * @return boolean true if found, false otherwise
	 */
	public static function network_exists( $site_id ) {
		global $sites, $wpdb;

		$site_id = (int)$site_id;

		$sites = (array)$sites;
		foreach ( $sites as $network ) {
			if ( $site_id == $network->id )
				return true;
		}

		/* check db just to be sure */
		$network_list = $wpdb->get_results( 'SELECT id FROM ' . $wpdb->site );
		if ( $network_list ) {
			foreach( $network_list as $network ) {
				if ( $network->id == $site_id )
					return true;
			}
		}

		return false;
	}

	/**
	 * Problem: the various *_site_options() functions operate only on the current network
	 * Workaround: change the current network
	 * @param integer $new_network ID of network to manipulate
	 */
	public static function switch_to_network( $new_network ) {
		global $old_network_details, $wpdb, $site_id, $switched_network, $switched_network_stack, $current_site, $sites;

		if ( ! Watermark_Mini_Site::network_exists( $new_network ) )
			$new_network = $site_id;

		if ( empty( $switched_network_stack ) )
			$switched_network_stack = array();

		$switched_network_stack[] = $site_id;

		if ( $new_network == $site_id )
			return;

		// backup
		$old_network_details['site_id'] = $site_id;
		$old_network_details['id'] = $current_site->id;
		$old_network_details['domain'] = $current_site->domain;
		$old_network_details['path'] = $current_site->path;
		$old_network_details['site_name'] = $current_site->site_name;

		foreach ( $sites as $network ) {
			if ( $network->id == $new_network ) {
				$current_site = $network;
				break;
			}
		}

		$wpdb->siteid = $new_network;
		$current_site->site_name = get_site_option('site_name');
		$site_id = $new_network;

		do_action( 'switch_network', $site_id, $old_network_details[ 'site_id' ] );
		$switched_network = true;
	}

	/**
	 * Return to the operational network after our operations
	 */
	public static function restore_current_network() {
		global $old_network_details, $wpdb, $site_id, $switched_network, $current_site, $switched_network_stack;

		if ( !$switched_network )
			return;

		$site_id = array_pop( $switched_network_stack );

		if ( $site_id == $current_site->id )
			return;

		$prev_site_id = $wpdb->site_id;

		$wpdb->siteid = $site_id;
		$current_site->id = $old_network_details[ 'id' ];
		$current_site->domain = $old_network_details[ 'domain' ];
		$current_site->path = $old_network_details[ 'path' ];
		$current_site->site_name = $old_network_details[ 'site_name' ];

		unset( $old_network_details );

		do_action( 'switch_network', $site_id, $prev_site_id );
		$switched_network = false;
	}

	/**
	 * Add a new network
	 * @param string $domain domain name for new network - for VHOST=no, this should be FQDN, otherwise domain only
	 * @param string $path path to root of network hierarchy - should be '/' unless WP is cohabiting with another product on a domain
	 * @param string $site_name Name of the root blog to be created on the new network
	 * @param integer $clone_network ID of network whose networkmeta values are to be copied - default NULL
	 * @param array $options_to_clone override default networkmeta options to copy when cloning - default NULL
	 * @return integer ID of newly created network
	 */
	public static function add_network( $domain, $path, $site_name = NULL, $clone_network = NULL, $options_to_clone = NULL ) {

		if ( $site_name == NULL )
			$site_name = __( 'New Network Created' );

		global $wpdb, $sites, $options_to_copy;

		if ( is_null( $options_to_clone ) )
			$options_to_clone = array_keys( $options_to_copy );

		$query = "SELECT * FROM {$wpdb->site} WHERE domain='" . $wpdb->escape( $domain ) . "' AND path='" . $wpdb->escape( $path ) . "' LIMIT 1";
		$network = $wpdb->get_row( $query );

		if ( $network )
			return new WP_Error( 'network_exists', __( 'Network already exists.' ) );


		if ( Watermark_Mini_Site::are_new_wpdb_funcs_available() ) {
			$wpdb->insert( $wpdb->site, array(
				'domain'	=> $domain,
				'path'		=> $path
			));
			$new_network_id =  $wpdb->insert_id;
		} else {
			$query = "INSERT INTO {$wpdb->site} (domain, path) VALUES ('" . $wpdb->escape( $domain ) . "','" . $wpdb->escape( $path ) . "')";
			$wpdb->query( $query );
			$new_network_id =  $wpdb->insert_id;
		}

		/* update network list */
		$sites = $wpdb->get_results( 'SELECT * FROM ' . $wpdb->site );

		if ( $new_network_id ) {

			if ( !defined( 'WP_INSTALLING' ) )
				define( 'WP_INSTALLING', true );

			$new_blog_id = wpmu_create_blog( $domain, $path, $site_name, get_current_user_id(), '', (int)$new_network_id );

			if ( is_a( $new_blog_id, 'WP_Error' ) )
				return $new_blog_id;
		}

		/** if selected, clone the networkmeta from an existing network */

		if ( !is_null( $clone_network ) && Watermark_Mini_Site::network_exists( $clone_network ) ) {

			$options_cache = array();

			Watermark_Mini_Site::switch_to_network( (int)$clone_network );

			foreach ( $options_to_clone as $option ) {
				$options_cache[$option] = get_site_option( $option );
			}

			Watermark_Mini_Site::restore_current_network();

			Watermark_Mini_Site::switch_to_network( $new_network_id );

			foreach ( $options_to_clone as $option ) {
				if ( $options_cache[$option] !== false ) {
					add_site_option( $option, $options_cache[$option] );
				}
			}
			unset( $options_cache );

			Watermark_Mini_Site::restore_current_network();
		}

		do_action( 'add_network' , $new_network_id );
		return $new_network_id;
	}

	/**
	 * Modify the domain and path of an existing network - and update all of its blogs
	 * @param integer id ID of network to modify
	 * @param string $domain new domain for network
	 * @param string $path new path for network
	 */
	public static function update_network( $id, $domain, $path = '' ) {
		global $wpdb, $options_list;

		if ( !Watermark_Mini_Site::network_exists( (int)$id ) )
			return new WP_Error( 'network_not_exist', __( 'Network does not exist.' ) );

		$query = "SELECT * FROM {$wpdb->site} WHERE id=" . (int)$id;
		$network = $wpdb->get_row($query);
		if ( !$network )
			return new WP_Error('network_not_exist',__('Network does not exist.'));

		if ( Watermark_Mini_Site::are_new_wpdb_funcs_available() ) {

			$update = array('domain'	=> $domain);
			if ( $path != '' )
				$update['path'] = $path;

			$where = array('id'	=> (int)$id);
			$update_result = $wpdb->update( $wpdb->site, $update, $where );
		} else {
			$domain = $wpdb->escape($domain);
			$path   = $wpdb->escape($path);

			$query = "UPDATE {$wpdb->site} SET domain='" . $domain . "' ";

			if ( $path != '' )
				$query .= ", path='" . $path . "' ";

			$query .= ' WHERE id=' . (int)$id;
			$update_result = $wpdb->query( $query );
		}

		if ( !$update_result )
			return new WP_Error( 'network_not_updated', __( 'Network could not be updated.' ) );

		$path = (($path != '') ? $path : $network->path );
		$full_path = $domain . $path;
		$old_path = $network->domain . $network->path;

		/** also updated any associated blogs */
		$query = "SELECT * FROM {$wpdb->blogs} WHERE site_id=" . (int)$id;
		$sites = $wpdb->get_results( $query );

		if ( $sites ) {
			foreach( $sites as $site ) {
				$domain = str_replace( $network->domain, $domain, $site->domain );
				if( Watermark_Mini_Site::are_new_wpdb_funcs_available() ) {
					$wpdb->update(
						$wpdb->blogs,
						array(
							'domain'	=> $domain,
							'path'		=> $path
						),
						array(
							'blog_id'	=> (int)$site->blog_id
						)
					);
				} else {
					$query = "UPDATE {$wpdb->blogs} SET domain='" . $domain . "', path='" . $path . "' WHERE blog_id=" . (int)$site->blog_id;
					$wpdb->query( $query );
				}

				/** fix options table values */
				$optionTable = $wpdb->get_blog_prefix( $site->blog_id ) . "options";

				foreach ( $options_list as $option_name ) {
					$option_value = $wpdb->get_row( "SELECT * FROM $optionTable WHERE option_name='$option_name'" );
					if ( $option_value ) {
						$new_value = str_replace( $old_path, $full_path, $option_value->option_value );
						update_blog_option( $site->blog_id, $option_name, $new_value );
					}
				}
			}
		}
		do_action( 'update_network' , $id, array( 'domain'=>$network->domain, 'path'=>$network->path ) );
	}

	/**
	 * Delete a network and all its blogs
	 * @param integer id ID of network to delete
	 * @param boolean $delete_blogs flag to permit blog deletion - default setting of false will prevent deletion of occupied networks
	 */
	public static function delete_network( $id, $delete_blogs = false ) {
		global $wpdb;

		$override = $delete_blogs;

		/* ensure we got a valid network id */
		$query = "SELECT * FROM {$wpdb->site} WHERE id=" . (int)$id;
		$network = $wpdb->get_row( $query );

		if ( !$network )
			return new WP_Error( 'network_not_exist', __( 'Network does not exist.' ) );

		/* ensure there are no blogs attached to this network */
		$query = "SELECT * FROM {$wpdb->blogs} WHERE site_id=" . (int)$id;
		$sites = $wpdb->get_results($query);

		if ( $sites && !$override )
			return new WP_Error( 'network_not_empty', __( 'Cannot delete network with sites.' ) );

		if ( $override && $sites ) {
			foreach( $sites as $site ) {
				if ( RESCUE_ORPHANED_BLOGS && ENABLE_NETWORK_ZERO )
					Watermark_Mini_Site::move_site( $site->blog_id, 0 );
				else
					wpmu_delete_blog( $site->blog_id, true );
			}
		}

		$query = "DELETE FROM {$wpdb->site} WHERE id=" . (int)$id;
		$wpdb->query($query);

		$query = "DELETE FROM {$wpdb->sitemeta} WHERE site_id=" . (int)$id;
		$wpdb->query($query);

		do_action( 'delete_network' , $network );
	}

	/**
	 * Move a blog from one network to another
	 * @param integer $site_id ID of blog to move
	 * @param integer $new_network_id ID of destination network
	 */
	public static function move_site( $site_id, $new_network_id ) {
		global $wpdb, $options_list;

		/* sanity checks */
		$query = "SELECT * FROM {$wpdb->blogs} WHERE blog_id=" . (int)$site_id;
		$site = $wpdb->get_row( $query );

		if ( !$site )
			return new WP_Error( 'blog not exist', __( 'Site does not exist.' ) );

		if ( (int)$new_network_id == $site->site_id )
			return true;

		$old_network_id = $site->site_id;

		if ( ENABLE_NETWORK_ZERO && $site->site_id == 0 ) {
			$old_network->domain = 'holding.blogs.local';
			$old_network->path = '/';
			$old_network->id = 0;
		} else {
			$query = "SELECT * FROM {$wpdb->site} WHERE id=" . (int)$site->site_id;
			$old_network = $wpdb->get_row( $query );
			if ( !$old_network )
				return new WP_Error( 'network_not_exist', __( 'Network does not exist.' ) );
		}

		if ( $new_network_id == 0 && ENABLE_NETWORK_ZERO ) {
			$new_network->domain = 'holding.blogs.local';
			$new_network->path = '/';
			$new_network->id = 0;
		} else {
			$query = "SELECT * FROM {$wpdb->site} WHERE id=" . (int)$new_network_id;
			$new_network = $wpdb->get_row( $query );

			if ( !$new_network )
				return new WP_Error( 'network_not_exist', __( 'Network does not exist.' ) );

		}

		if ( defined('VHOST') && VHOST == 'yes' ) {
			$ex_dom = substr( $site->domain, 0, ( strpos( $site->domain, '.' ) + 1 ) );
			$domain = $ex_dom . $new_network->domain;
		} else {
			$domain = $new_network->domain;
		}
		$path = $new_network->path . substr( $site->path, strlen( $old_network->path ) );

		if ( Watermark_Mini_Site::are_new_wpdb_funcs_available() ) {
			$update_result = $wpdb->update(
				$wpdb->blogs,
				array(	'site_id'	=> $new_network->id,
						'domain'	=> $domain,
						'path'		=> $path
				),
				array(	'blog_id'	=> $site->blog_id )
			);
		} else {

			$update_result = $query = "UPDATE {$wpdb->blogs} SET site_id=" . $new_network->id . ", domain='" . $domain . "', path='" . $path . "' WHERE blog_id=" . $site->blog_id;
			$wpdb->query( $query );

		}

		if ( !$update_result )
			return new WP_Error( 'blog_not_moved', __( 'Site could not be moved.' ) );


		/** change relevant blog options */
		$options_table = $wpdb->get_blog_prefix( $site->blog_id ) . "options";

		$old_domain = $old_network->domain . $old_network->path;
		$new_domain = $new_network->domain . $new_network->path;

		foreach($options_list as $option_name) {
			$option = $wpdb->get_row( "SELECT * FROM $options_table WHERE option_name='" . $option_name . "'" );
			$new_value = str_replace( $old_domain, $new_domain, $option->option_value );
			update_blog_option( $site->blog_id, $option_name, $new_value );
		}

		do_action( 'move_blog', $site_id, $old_network_id, $new_network_id );
	}
}

endif;
?>