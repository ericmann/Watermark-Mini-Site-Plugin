<?php
if ( ! class_exists( 'Watermark_Mini_Site_Admin' ) ) :

class Watermark_Mini_Site_Admin {
	public static function add_menu_page() {
		add_menu_page( 'Mini-Sites', 'Mini-Sites', 'manage_options', 'mini-sites', array( 'Watermark_Mini_Site_Admin', 'minisite_page' ) );
	}

	public static function minisite_page() {
		global $wpdb;

		if ( isset( $_POST['update'] ) && isset( $_GET['id'] ) )
			Watermark_Mini_Site_Admin::update_site();

		if ( isset( $_POST['delete'] ) && isset( $_GET['id'] ) )
			Watermark_Mini_Site_Admin::delete_site();

		if ( isset( $_POST['add'] ) )
			Watermark_Mini_Site_Admin::add_site( $_POST['path'], $_POST['name'] );

		if ( isset( $_GET['updated'] ) ) :
?>
			<div id="message" class="updated fade"><p><?php _e('Options saved.') ?></p></div>
<?php
		elseif ( isset($_GET['added'] ) ) :
?>
			<div id="message" class="updated fade"><p><?php _e('Network created.'); ?></p></div>
<?php
		elseif( isset( $_GET['deleted'] ) ) :
?>
			<div id="message" class="updated fade"><p><?php _e('Network(s) deleted.'); ?></p></div>
<?php
		endif;

		if ( isset( $_GET['action'] ) )
			switch( $_GET['action'] ) {
				case 'createnetwork':
					Watermark_Mini_Site_Admin::create_network();
					break;
			}

		$network_id = get_site_option( 'network_id' );

		print '<div class="wrap" style="position: relative">';
?>
		<div id="icon-tools" class="icon32"></div>
		<h2><?php _e ( 'Mini Sites' ) ?></h2>
<?php
		if(0 != (int)$network_id) :
			Watermark_Mini_Site::switch_to_network( $network_id );
			$wp_list_table = _get_list_table('WP_MS_Sites_List_Table');
			$pagenum = $wp_list_table->get_pagenum();
			$wp_list_table->prepare_items();
			Watermark_Mini_Site::restore_current_network();
?>
			<div id="col-left">
				<h3><?php _e( 'Add Resident MiniSite' ); ?></h3>
				<form method="POST" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
					<input id="add" name="add" value="add" type="hidden" />
					<table class="form-table">
						<tr><th scope="row"><label for="newName"><?php _e( 'Resident Name' ); ?>:</label></th><td><input type="text" name="name" id="newName" title="<?php _e( 'A friendly name for your resident' ); ?>" /></td></tr>
						<tr><th scope="row"><label for="newPath"><?php _e( 'Path' ); ?>:</label></th><td><input type="text" name="path" id="newPath" title="<?php _e( 'If you are unsure, put in /' ); ?>" value="/" /></td></tr>
					</table>
					<input type="submit" class="button" name="add" value="<?php _e('Create MiniSite'); ?>" />
				</form>
			</div>
			<form id="form-site-list" action="edit.php?action=allblogs" method="post">
				<?php $wp_list_table->display(); ?>
			</form>
<?php
		else :
?>
		<form method="POST" action="<?php echo $_SERVER['REQUEST_URI'] . "&amp;action=createnetwork"; ?>">
			<input type="submit" class="button" name="add" value="<?php _e('Create Network'); ?>" />
		</form>
<?php
		endif;

		print '</div>';
	}

	public static function update_site() {

	}

	public static function delete_site() {

	}

	public static function add_site($path, $title) {
		global $current_site, $current_user;

		get_currentuserinfo();
		
		$id = wpmu_create_blog(
			$current_site->domain,
			'/residents' . $path,
			$title,
			$current_user->ID,
			array( 'public' => 1 ),
			$current_site->id
		);
	}

	public static function create_network() {
		if ( null != get_site_option( 'network_id' ) )
			return;

		$result = Watermark_Mini_Site::add_network(
			$_SERVER['HTTP_HOST'],
			'/residents/',
			'Residents',
			null,
			null
			);

		if($result) {
			Watermark_Mini_Site::switch_to_network( $result );
			add_site_option( 'site_name', 'Residents' );
			Watermark_Mini_Site::restore_current_network();
			add_site_option( 'network_id', $result );
		}
	}
}

endif;
?>