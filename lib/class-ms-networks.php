<?php
if ( !class_exists( 'MS_Networks' ) ) :
	class MS_Networks {

		function MS_Networks()	{
			if ( function_exists( 'add_action' ) ) {
				add_action( 'admin_menu', array( &$this, 'admin_menu' ) );
				add_action( 'wpmublogsaction', array( &$this, 'assign_blogs_link' ) );
			}
		}

		function assign_blogs_link() {
			global $blog;
			echo '<a href="ms-admin.php?page=networks&amp;action=move&amp;blog_id=' . $blog['blog_id'] . '" class="edit">' . __( 'Move' ) . '</a>';
		}

		function admin_menu() {
			add_submenu_page( 'ms-admin.php', __( 'Networks' ), __( 'Networks' ), 'manage_options', 'networks', array( &$this, 'networks_page' ) );
		}

	/* Config Page*/
		function networks_page() {
			global $wpdb,  $options_to_copy;

			if ( !function_exists( 'is_super_admin' ) || !is_super_admin() )
				wp_die( __( '<p>You do not have permission to access this page.</p>' ) );

			if ( isset( $_POST['update'] ) && isset( $_GET['id'] ) )
				$this->wpmn_update_network_page();

			if ( isset( $_POST['delete'] ) && isset( $_GET['id'] ) )
				$this->wpmn_delete_network_page();

			if ( isset( $_POST['delete_multiple'] ) && isset( $_POST['deleted_networks'] ) )
				$this->wpmn_delete_multiple_network_page();

			if ( isset( $_POST['add'] ) && isset( $_POST['domain'] ) && isset( $_POST['path'] ) )
				$this->wpmn_add_network_page();

			if ( isset( $_POST['move'] ) && isset( $_GET['blog_id'] ) )
				$this->wpmn_move_site_page();

			if ( isset( $_POST['reassign'] ) && isset( $_GET['id'] ) )
				$this->wpmn_reassign_site_page();

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

			print '<div class="wrap" style="position: relative">';

			switch( $_GET[ 'action' ] ) {
				case 'move':
					$this->wpmn_move_site_page();
					break;
				case 'assignblogs':
					$this->wpmn_reassign_site_page();
					break;
				case 'deletenetwork':
					$this->wpmn_delete_network_page();
					break;
				case 'editnetwork':
					$this->wpmn_update_network_page();
					break;
				case 'delete_multinetworks':
					$this->wpmn_delete_multiple_network_page();
					break;
				default:

					/* Strip off the action tag */
					$query_str = substr( $_SERVER['REQUEST_URI'], 0, ( strpos( $_SERVER['REQUEST_URI'], '?' ) + 1 ) );
					$get_params = array();
					$bad_params = array( 'action', 'id', 'updated', 'deleted' );
					foreach ( $_GET as $get_param => $get_value ) {
						if ( !in_array( $get_param,$bad_params ) ) {
							$get_params[] = $get_param . '=' . $get_value;
						}
					}
					$query_str .= implode( '&', $get_params );

					$search_conditions = '';
					if ( isset( $_GET['s'] ) ) {
						if ( isset( $_GET['search'] ) && $_GET['search'] == __( 'Search Domains' ) )
							$search_conditions = 'WHERE ' . $wpdb->site . '.domain LIKE ' . "'%" . $wpdb->escape( $_GET['s'] ) . "%'";
					}

					$count = $wpdb->get_col( 'SELECT COUNT(id) FROM ' . $wpdb->site . $search_conditions );
					$total = $count[0];

					if ( isset( $_GET[ 'start' ] ) == false )
						$start = 1;
					else
						$start = intval( $_GET[ 'start' ] );

					if ( isset( $_GET[ 'num' ] ) == false )
						$num = NETWORKS_PER_PAGE;
					else
						$num = intval( $_GET[ 'num' ] );

					$query = "SELECT {$wpdb->site}.*, COUNT({$wpdb->blogs}.blog_id) as blogs, {$wpdb->blogs}.path as blog_path
						FROM {$wpdb->site} LEFT JOIN {$wpdb->blogs} ON {$wpdb->blogs}.site_id = {$wpdb->site}.id $search_conditions GROUP BY {$wpdb->site}.id" ;

					if( isset( $_GET[ 'sortby' ] ) == false )
						$_GET[ 'sortby' ] = 'ID';


					switch( $_GET['sortby'] ) {
						case 'Domain':
							$query .= ' ORDER BY ' . $wpdb->site . '.domain ';
							break;
						case 'ID':
							$query .= ' ORDER BY ' . $wpdb->site . '.id ';
							break;
						case 'Path':
							$query .= ' ORDER BY ' . $wpdb->site . '.path ';
							break;
						case 'Blogs':
							$query .= ' ORDER BY blogs ';
							break;
					}

					if( $_GET[ 'order' ] == 'DESC' )
						$query .= 'DESC';
					else
						$query .= 'ASC';

					$query .= ' LIMIT ' . (((int)$start - 1 ) * $num ) . ', ' . intval( $num );

					$site_list = $wpdb->get_results( $query, ARRAY_A );

					if ( count( $site_list ) < $num )
						$next = false;
					else
						$next = true;

					// define the columns to display, the syntax is 'internal name' => 'display name'
					$sites_columns = array(
						'check'		=> '',
						'domain'	=> __( 'Domain' ),
						'id'		=> __( 'Site ID' ),
						'path'		=> __( 'Path' ),
						'blogs'		=> __( 'Sites' ),
					);
					$sites_columns = apply_filters( 'manage_networks_columns', $sites_columns );

					// Pagination
					$network_navigation = paginate_links( array(
						'base' => add_query_arg( 'start', '%#%' ),
						'format' => '',
						'total' => ceil($total / $num),
						'current' => $start
					));
?>

	<div id="icon-tools" class="icon32"></div>
	<h2><?php _e ( 'Networks' ) ?></h2>
	<div id="col-container">
		<div id="col-right">
			<div class="tablenav">
<?php if ( isset( $_GET['s'] ) ) : ?>
				<div class="alignleft">
					<?php _e( 'Filter' ); ?>: <?php echo $wpdb->escape( $_GET['s'] ) ?> <a href="./ms-admin.php?page=networks" class="button" title="<?php _e( 'Remove this filter' ) ?>"><?php _e( 'Remove' ) ?></a>
				</div>
<?php endif; ?>
				<div class="alignright">
					<form name="searchform" action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="get">
						<input type="text" name="s" />
						<input type="hidden" name="page" value="networks" />
						<input type="submit" name="search" id="search" class="button" value="<?php _e('Search Domains'); ?>" />
					</form>
				</div>
			</div>
			<form name='formlist' action='<?php echo $_SERVER['REQUEST_URI'] . "&amp;action=delete_multinetworks"; ?>' method='POST'>
				<div class="tablenav">
<?php if ( $network_navigation ) echo "<div class='tablenav-pages'>$network_navigation</div>"; ?>
					<div class="alignleft">
						<input type="submit" class="button-secondary delete" name="allnetwork_delete" value="<?php _e('Delete'); ?>" />
					</div>
				</div>
				<br class="clear" />
				<table width="100%" cellpadding="3" cellspacing="3" class="widefat">
					<thead>
						<tr>
<?php foreach( $sites_columns as $col_name => $column_display_name ) { ?>
<?php if ( $col_name == 'check' ) : ?>
							<th scope="col" class="column-cb check-column">
								<input type="checkbox" id="select_all">
<?php else :?>
							<th scope="col"><a href="ms-admin.php?page=networks&sortby=<?php echo urlencode( $column_display_name ) ?>&<?php if( $_GET[ 'sortby' ] == $column_display_name ) { if( $_GET[ 'order' ] == 'DESC' ) { echo "order=ASC&" ; } else { echo "order=DESC&"; } } ?>start=<?php echo $start ?>"><?php echo $column_display_name; ?></a>
<?php endif; ?>
							</th>
<?php } ?>
						</tr>
					</thead>
					<tfoot>
						<tr>
<?php foreach( $sites_columns as $col_name => $column_display_name ) { ?>

<?php if ( $col_name == 'check' ) : ?>
							<th scope="col" class="column-cb check-column">
								<input type="checkbox" id="select_all">
<?php else :?>
							<th scope="col"><a href="ms-admin.php?page=networks&sortby=<?php echo urlencode( $column_display_name ) ?>&<?php if( $_GET[ 'sortby' ] == $column_display_name ) { if( $_GET[ 'order' ] == 'DESC' ) { echo "order=ASC&" ; } else { echo "order=DESC&"; } } ?>start=<?php echo $start ?>"><?php echo $column_display_name; ?></a>
<?php endif; ?>
							</th>
<?php } ?>
						</tr>
					</tfoot>
					<tbody>
<?php

if ( $site_list ) {
	$bgcolor = '';
	foreach ( $site_list as $site ) {
?>
						<tr>
<?php
		if ( constant( "VHOST" ) == 'yes' )
			$sitename = str_replace( '.' . $current_site->domain, '', $site[ 'domain' ] );
		else
			$sitename = $site[ 'path' ];

		foreach( $sites_columns as $column_name => $column_display_name ) {
			switch( $column_name ) {
				case 'check' :
					if ( $site['blogs'] == 0 || $site['id'] != 1 ) :
?>
							<th scope="row" class="check-column" style="width: auto"><input type='checkbox' id='<?php echo $site[ 'id' ] ?>' name='allnetworks[]' value='<?php echo $site[ 'id' ] ?>'><label for='<?php echo $site[ 'id' ] ?>'></label</th>
<?php
					else :
?>
							<th></th>
<?php
					endif;
					break;

				case 'id':
?>
							<td><?php echo $site[ 'id' ] ?></td>
<?php
					break;

				case 'domain':
?>
							<td><?php echo $site['domain'] ?>
<?php
					$actions	= array();
					$actions[]	= '<a class="edit" href="' . $query_str . "&amp;action=assignblogs&amp;id=" . $site['id'] . '" title="' . __('Assign sites to this network') . '">' . __( 'Assign Sites' ) . '</a>';
					$actions[]	= '<a class="edit" href="' . $query_str . "&amp;action=editnetwork&amp;id=" .  $site['id'] . '" title="' . __('Edit this network') . '">' . __('Edit') . '</a>';
					if ( $site['blogs'] == 0 || $site['id'] != 1 )
						$actions[] = '<a class="delete" href="' . $query_str . "&amp;action=deletenetwork&amp;id=" . $site['id'] . '" title="' . __('Delete this network') . '">' . __('Delete') . '</a>';

					if ( count( $actions ) ) : ?>
								<div class="row-actions">
									<?php echo implode(' | ', $actions); ?>
								</div>
<?php endif; ?>
							</td>
<?php
					break;

				case 'path':
?>
							<td valign='top'><label for='<?php echo $site[ 'id' ] ?>'><?php echo $site['path'] ?></label></td>
<?php
					break;

				case 'blogs':
?>
							<td valign='top'><a href="http://<?php echo $site['domain'] . $site['blog_path'];?>wp-admin/ms-sites.php" title="<?php _e('Sites on this network'); ?>"><?php echo $site['blogs'] ?></a></td>
<?php
					break;

				default:
?>
							<td valign='top'><?php do_action( 'manage_networks_custom_column', $column_name, $site['id'] ); ?></td>
<?php
					break;
			}
		}
?>
						</tr>
<?php
	}
} else {
?>
						<tr style=''>
							<td colspan="8"><?php _e( 'No networks found.' ) ?></td>
						</tr>
<?php
} // end if ($sites)
?>
					</tbody>
				</table>
				<div class="tablenav">
<?php if ( $network_navigation ) echo "<div class='tablenav-pages'>$network_navigation</div>"; ?>
					<div class="alignleft">
						<input type="submit" class="button-secondary delete" name="allnetwork_delete" value="<?php _e('Delete'); ?>" />
					</div>
				</div>
			</form>
		</div>
		<div id="col-left">
			<h3><?php _e( 'Add Network' ); ?></h3>
			<p><?php _e( 'A site will be created at the root of the new network' ); ?>.</p>
				<form method="POST" action="<?php echo $_SERVER['REQUEST_URI'] . "&amp;action=addnetwork"; ?>">
					<table class="form-table">
						<tr><th scope="row"><label for="newName"><?php _e( 'Network Name' ); ?>:</label></th><td><input type="text" name="name" id="newName" title="<?php _e( 'A friendly name for your new network' ); ?>" /></td></tr>
						<tr><th scope="row"><label for="newDom"><?php _e( 'Domain' ); ?>:</label></th><td> http://<input type="text" name="domain" id="newDom" title="<?php _e( 'The domain for your new network' ); ?>" /></td></tr>
						<tr><th scope="row"><label for="newPath"><?php _e( 'Path' ); ?>:</label></th><td><input type="text" name="path" id="newPath" title="<?php _e( 'If you are unsure, put in /' ); ?>" /></td></tr>
						<tr><th scope="row"><label for="newSite"><?php _e( 'Site Name' ); ?>:</label></th><td><input type="text" name="newSite" id="newSite" title="<?php _e( 'The name for the new network\'s site.' ); ?>" /></td></tr>
					</table>
					<div class="metabox-holder meta-box-sortables" id="advanced_network_options">
						<div class="postbox if-js-closed">
							<div title="Click to toggle" class="handlediv"><br/></div>
							<h3><span><?php _e( 'Clone Network Options' ); ?></span></h3>
							<div class="inside">
								<table class="form-table">
								<tr>
									<th scope="row"><label for="cloneNetwork"><?php _e('Clone Network'); ?>:</label></th>
									<?php	$network_list = $wpdb->get_results( 'SELECT id, domain FROM ' . $wpdb->site , ARRAY_A );	?>
									<td colspan="2"><select name="cloneNetwork" id="cloneNetwork"><option value="0"><?php _e('Do Not Clone'); ?></option><?php foreach($network_list as $network) { echo '<option value="' . $network['id'] . '"' . ($network['id'] == 1 ? ' selected' : '' ) . '>' . $network['domain'] . '</option>'; } ?></select></td>
								</tr>
								<tr>
<?php
	$all_network_options = $wpdb->get_results( 'SELECT DISTINCT meta_key FROM ' . $wpdb->sitemeta );

	$known_networkmeta_options = $options_to_copy;
	$known_networkmeta_options = apply_filters( 'manage_networkmeta_descriptions' , $known_networkmeta_options );

	$options_to_copy = apply_filters( 'manage_network_clone_options' , $options_to_copy );
?>
									<td colspan="3">
										<table class="widefat">
											<thead>
												<tr>
													<th scope="col" class="check-column"></th>
													<th scope="col"><?php _e('Meta Value'); ?></th>
													<th scope="col"><?php _e('Description'); ?></th>
												</tr>
											</thead>
											<tbody>
												<?php foreach ($all_network_options as $count => $option) { ?>
												<tr class="<?php echo $class = ('alternate' == $class) ? '' : 'alternate'; ?>">
													<th scope="row" class="check-column"><input type="checkbox" id="option_<?php echo $count; ?>" name="options_to_clone[<?php echo $option->meta_key; ?>]"<?php echo (array_key_exists($option->meta_key,$options_to_copy) ? ' checked' : '' ); ?> /></th>
													<td><label for="option_<?php echo $count; ?>"><?php echo $option->meta_key; ?></label></td>
													<td><label for="option_<?php echo $count; ?>"><?php echo (array_key_exists($option->meta_key,$known_networkmeta_options) ? __($known_networkmeta_options[$option->meta_key]) : '' ); ?></label></td>
												</tr>
												<?php } ?>
											</tbody>
										</table>
									</td>
								</table>
							</div>
						</div>
					</div>
					<input type="submit" class="button" name="add" value="<?php _e('Create Network'); ?>" />
				</form>
			</div>
<script type="text/javascript">
jQuery('.if-js-closed').removeClass('if-js-closed').addClass('closed');
jQuery('.postbox').children('h3').click(function() {
	if (jQuery(this.parentNode).hasClass('closed')) {
		jQuery(this.parentNode).removeClass('closed');
	} else {
		jQuery(this.parentNode).addClass('closed');
	}
});
</script>
<?php

			break;
		} // end switch( $action )
?>
</div>
<?php

	}

	function wpmn_move_site_page() {
		global $wpdb;

		if ( isset( $_POST['move'] ) && isset( $_GET['blog_id'] ) ) {
			if ( isset( $_POST['from'] ) && isset( $_POST['to'] ) ) {
				wpmn_move_site( $_GET['blog_id'], $_POST['to'] );
				$_GET['updated'] = 'yes';
				$_GET['action'] = 'saved';
			}
		} else {
			if( !isset( $_GET['blog_id'] ) )
				die( __( 'You must select a blog to move.' ) );

			$query = "SELECT * FROM {$wpdb->blogs} WHERE blog_id=" . (int)$_GET['blog_id'];
			$site = $wpdb->get_row( $query );

			if ( !$site )
				die( __( 'Invalid blog id.' ) );

			$table_name = $wpdb->get_blog_prefix( $site->blog_id ) . "options";
			$details = $wpdb->get_row( "SELECT * FROM {$table_name} WHERE option_name='blogname'" );

			if ( !$details )
				die( __( 'Invalid blog id.' ) );

			$sites = $wpdb->get_results( "SELECT * FROM {$wpdb->site}" );

			foreach ( $sites as $key => $network ) {
				if ( $network->id == $site->site_id )
					$myNetwork = $sites[$key];
			}
?>
			<div id="icon-tools" class="icon32"></div>
			<h2><?php _e ( 'Networks' ) ?></h2>
			<h3><?php echo __( 'Moving' ) . ' ' . stripslashes( $details->option_value ); ?></h3>
			<form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
				<table class="widefat">
					<thead>
						<tr>
							<th scope="col"><?php _e( 'From' ); ?>:</th>
							<th scope="col"><label for="to"><?php _e( 'To' ); ?>:</label></th>
						</tr>
					</thead>
					<tr>
						<td><?php echo $myNetwork->domain; ?></td>
						<td>
							<select name="to" id="to">
								<option value="0"><?php _e('Select a Network'); ?></option>
<?php
	foreach ( $sites as $network ) {
		if ( $network->id != $myNetwork->id ) :
?>
								<option value="<?php echo $network->id ?>"><?php echo $network->domain ?></option>
<?php
		endif;
	}
?>
							</select>
						</td>
					</tr>
				</table>
				<br />
<?php if ( has_action( 'add_move_blog_option' ) ) : ?>
				<table class="widefat">
					<thead>
						<tr scope="col"><th colspan="2"><?php _e('Options'); ?>:</th></tr>
					</thead>
					<?php do_action( 'add_move_blog_option', $site->blog_id ); ?>
				</table>
				<br />
<?php endif; ?>
				<div>
					<input type="hidden" name="from" value="<?php echo $site->site_id; ?>" />
					<input class="button" type="submit" name="move" value="<?php _e('Move Site'); ?>" />
					<a class="button" href="./ms-sites.php"><?php _e('Cancel'); ?></a>
				</div>
			</form>
<?php
		}
	}

	function wpmn_reassign_site_page() {
		global $wpdb;

		if ( isset( $_POST['reassign'] ) && isset( $_GET['id'] ) ) {
			/** Javascript enabled for client - check the 'to' box */
			if ( isset( $_POST['jsEnabled'] ) ) {
				if ( !isset( $_POST['to'] ) )
					die( __( 'No blogs selected.' ) );

				$sites = $_POST['to'];

			/** Javascript disabled for client - check the 'from' box */
			} else {
				if ( !isset( $_POST['from'] ) )
					die( __( 'No blogs seleceted.' ) );

				$sites = $_POST['from'];
			}

			$current_blogs = $wpdb->get_results( "SELECT * FROM {$wpdb->blogs} WHERE site_id=" . (int)$_GET['id'] );

			foreach( $sites as $site ) {
				wpmn_move_site( $site, (int)$_GET['id'] );
			}

			/* true sync - move any unlisted blogs to 'zero' network */
			if ( ENABLE_NETWORK_ZERO ) {
				foreach( $current_blogs as $current_blog ) {
					if ( !in_array( $current_blog->blog_id, $sites ) )
						wpmn_move_site( $current_blog->blog_id, 0 );

				}
			}

			$_GET['updated'] = 'yes';
			$_GET['action'] = 'saved';

		} else {

			// get network by id
			$query = "SELECT * FROM {$wpdb->site} WHERE id=" . (int)$_GET['id'];
			$network = $wpdb->get_row( $query );

			if ( !$network )
				die( __( 'Invalid network id.' ) );

			$sites = $wpdb->get_results( "SELECT * FROM {$wpdb->blogs}" );
			if ( !$sites )
				die( __( 'Site table inaccessible.' ) );

			foreach( $sites as $key => $site ) {
				$table_name = $wpdb->get_blog_prefix( $site->blog_id ) . "options";
				$site_name = $wpdb->get_row("SELECT * FROM $table_name WHERE option_name='blogname'");

				if ( !$site_name )
					die( __( 'Invalid blog.' ) );

				$sites[$key]->name = stripslashes( $site_name->option_value );
			}
?>
			<div id="icon-tools" class="icon32"></div>
			<h2><?php _e ( 'Networks' ) ?></h2>
			<h3><?php _e( 'Assign Sites to' ); ?>: http://<?php echo $network->domain . $network->path ?></h3>
			<noscript>
				<div id="message" class="updated"><p><?php _e( 'Select the blogs you want to assign to this network from the column at left, and click "Update Assignments."' ); ?></p></div>
			</noscript>
			<form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
				<table class="widefat">
					<thead>
						<tr>
							<th><?php _e( 'Available' ); ?></th>
							<th style="width: 2em;"></th>
							<th><?php _e( 'Assigned' ); ?></th>
						</tr>
					</thead>
					<tr>
						<td>
							<select name="from[]" id="from" multiple style="height: auto; width: 98%">
<?php
	foreach ( $sites as $site ) {
		if ( $site->site_id != $network->id ) echo '<option value="' . $site->blog_id . '">' . $site->name  . ' (' . $site->domain . ')</option>';
	}
?>
							</select>
						</td>
						<td>
							<input type="button" name="unassign" id="unassign" value="<<" /><br />
							<input type="button" name="assign" id="assign" value=">>" />
						</td>
						<td valign="top">
							<?php if ( !ENABLE_NETWORK_ZERO ) { ?><ul style="margin: 0; padding: 0; list-style-type: none;">
								<?php foreach( $sites as $site ) {
									if ( $site->site_id == $network->id ) { ?>
									<li><?php echo $site->name . ' (' . $site->domain . ')'; ?></li>
								<?php } } ?>
							</ul><?php } ?>
							<select name="to[]" id="to" multiple style="height: auto; width: 98%">
							<?php
							if ( ENABLE_NETWORK_ZERO ) {
								foreach( $sites as $site ) {
									if ( $site->site_id == $network->id ) echo '<option value="' . $site->blog_id . '">' . $site->name . ' (' . $site->domain . ')</option>';
								}
							}
							?>
							</select>
						</td>
					</tr>
				</table>
				<br class="clear" />
<?php if ( has_action( 'add_move_blog_option' ) ) : ?>
				<table class="widefat">
					<thead>
						<tr scope="col"><th colspan="2"><?php _e( 'Options' ); ?>:</th></tr>
					</thead>
<?php do_action( 'add_move_blog_option', $site->blog_id ); ?>
				</table>
				<br />
<?php endif; ?>
				<input type="submit" name="reassign" value="<?php _e( 'Update Assigments' ); ?>" class="button" />
				<a href="./ms-admin.php?page=networks"><?php _e( 'Cancel' ); ?></a>
			</form>
			<script type="text/javascript">
				if(document.getElementById) {

					var unassignButton = document.getElementById('unassign');
					var assignButton = document.getElementById('assign');
					var fromBox = document.getElementById('from');
					var toBox = document.getElementById('to');

					/* add field to signal javascript is enabled */
					var myJSVerifier = document.createElement('input');
					myJSVerifier.type = "hidden";
					myJSVerifier.name = "jsEnabled";
					myJSVerifier.value = "true";

					assignButton.parentNode.appendChild(myJSVerifier);

					assignButton.onclick   = function() { move(fromBox, toBox); };
					unassignButton.onclick = function() { move(toBox, fromBox); };
					assignButton.form.onsubmit = function() { selectAll(toBox); };
				}

				// PickList II script (aka Menu Swapper)- By Phil Webb (http://www.philwebb.com)
				// Visit JavaScript Kit (http://www.javascriptkit.com) for this JavaScript and 100s more
				// Please keep this notice intact

			function move(fbox, tbox) {
			     var arrFbox = new Array();
			     var arrTbox = new Array();
			     var arrLookup = new Array();
			     var i;
			     for(i=0; i<tbox.options.length; i++) {
			          arrLookup[tbox.options[i].text] = tbox.options[i].value;
			          arrTbox[i] = tbox.options[i].text;
			     }
			     var fLength = 0;
			     var tLength = arrTbox.length
			     for(i=0; i<fbox.options.length; i++) {
			          arrLookup[fbox.options[i].text] = fbox.options[i].value;
			          if(fbox.options[i].selected && fbox.options[i].value != "") {
			               arrTbox[tLength] = fbox.options[i].text;
			               tLength++;
			          } else {
			               arrFbox[fLength] = fbox.options[i].text;
			               fLength++;
			          }
			     }
			     arrFbox.sort();
			     arrTbox.sort();
			     fbox.length = 0;
			     tbox.length = 0;
			     var c;
			     for(c=0; c<arrFbox.length; c++) {
			          var no = new Option();
			          no.value = arrLookup[arrFbox[c]];
			          no.text = arrFbox[c];
			          fbox[c] = no;
			     }
			     for(c=0; c<arrTbox.length; c++) {
			     	var no = new Option();
			     	no.value = arrLookup[arrTbox[c]];
			     	no.text = arrTbox[c];
			     	tbox[c] = no;
			     }
			}

			function selectAll(box) {    for(var i=0; i<box.length; i++) {  box[i].selected = true;  } }

			</script>
			<?php

		}
	}

	function wpmn_add_network_page() {
		global $wpdb, $options_to_copy;

		if ( isset( $_POST['add'] ) && isset( $_POST['domain'] ) && isset( $_POST['path'] ) ) {

			/** grab custom options to clone if set */
			if ( isset( $_POST['options_to_clone'] ) && is_array( $_POST['options_to_clone'] ) )
				$options_to_clone = array_keys( $_POST['options_to_clone'] );
			else
				$options_to_clone = $options_to_copy;

			$result = wpmn_add_network(
				$_POST['domain'],
				$_POST['path'],
				( isset( $_POST['newSite'] ) ? $_POST['newSite'] : __('New Network Created') ) ,
				( isset( $_POST['cloneNetwork'] ) ? $_POST['cloneNetwork'] : NULL ),
				$options_to_clone
			);

			if ( $result ) {
				if ( isset( $_POST['name'] ) ) {
					wpmn_switch_to_network( $result );
					add_site_option( 'site_name', $_POST['name'] );
					wpmn_restore_current_network();
				}

				$_GET['updated'] = 'yes';
				$_GET['action'] = 'saved';
			}
		}
	}

	function wpmn_update_network_page() {
		global $wpdb;

		if ( isset( $_POST['update'] ) && isset( $_GET['id'] ) ) {
			$query = "SELECT * FROM {$wpdb->site} WHERE id=" . (int)$_GET['id'];
			$network = $wpdb->get_row( $query );
			if ( !$network )
				die( __( 'Invalid network id.' ) );

			wpmn_update_network( (int)$_GET['id'], $_POST['domain'], $_POST['path'] );
			$_GET['updated'] = 'true';
			$_GET['action'] = 'saved';

		} else {

			// get network by id
			$query = "SELECT * FROM {$wpdb->site} WHERE id=" . (int)$_GET['id'];
			$network = $wpdb->get_row( $query );

			if ( !$network )
				wp_die(__('Invalid network id.'));

			/* strip off the action tag */
			$query_str = substr( $_SERVER['REQUEST_URI'], 0, ( strpos( $_SERVER['REQUEST_URI'], '?' ) + 1 ) );
			$get_params = array();

			foreach ( $_GET as $get_param => $get_value ) {
				if ( $get_param != 'action' )
					$get_params[] = $get_param . '=' . $get_value;

			}
			$query_str .= implode( '&', $get_params );
?>
			<div id="icon-tools" class="icon32"></div>
			<h2><?php _e ('Networks') ?></h2>
			<h3><?php _e('Edit Network'); ?>: http://<?php echo $network->domain . $network->path ?></h3>
			<form method="post" action="<?php echo $query_str; ?>">
				<table class="form-table">
					<tr class="form-field"><th scope="row"><label for="domain"><?php _e( 'Domain' ); ?></label></th><td> http://<input type="text" id="domain" name="domain" value="<?php echo $network->domain; ?>"></td></tr>
					<tr class="form-field"><th scope="row"><label for="path"><?php _e( 'Path' ); ?></label></th><td><input type="text" id="path" name="path" value="<?php echo $network->path; ?>" /></td></tr>
				</table>
<?php if ( has_action( 'add_edit_network_option' ) ) : ?>
				<h3><?php _e( 'Options:' ) ?></h3>
				<table class="form-table">
					<?php do_action( 'add_edit_network_option' ); ?>
				</table>
<?php endif; ?>
				<p>
					<input type="hidden" name="networkId" value="<?php echo $network->id; ?>" />
					<input class="button" type="submit" name="update" value="<?php _e( 'Update Network' ); ?>" />
					<a href="./ms-admin.php?page=networks"><?php _e('Cancel'); ?></a>
				</p>
			</form>
<?php
		}
	}

	function wpmn_delete_network_page() {
		global $wpdb;

		if ( isset( $_POST['delete'] ) && isset( $_GET['id'] ) ) {
			$result = wpmn_delete_network( (int)$_GET['id'], ( isset( $_POST['override'] ) ) );

			if ( is_a( $result, 'WP_Error' ) )
				wp_die( $result->get_error_message() );

			$_GET['deleted'] = 'yes';
			$_GET['action'] = 'saved';

		} else {

			/* get network by id */
			$query = "SELECT * FROM {$wpdb->site} WHERE id=" . (int)$_GET['id'];
			$network = $wpdb->get_row( $query );

			if ( !$network )
				die( __( 'Invalid network id.' ) );

			$query = "SELECT * FROM {$wpdb->blogs} WHERE site_id=" . (int)$_GET['id'];
			$sites = $wpdb->get_results( $query );

			/* strip off the action tag */
			$query_str = substr( $_SERVER['REQUEST_URI'], 0, ( strpos( $_SERVER['REQUEST_URI'], '?' ) + 1 ) );
			$get_params = array();

			foreach( $_GET as $get_param => $get_value ) {
				if ( $get_param != 'action' )
					$get_params[] = $get_param . '=' . $get_value;
			}
			$query_str .= implode( '&', $get_params );
?>
			<form method="POST" action="<?php echo $query_str; ?>">
				<div>
					<div id="icon-tools" class="icon32"></div>
					<h2><?php _e( 'Networks' ); ?></h2>
					<h3><?php _e( 'Delete Network' ); ?>: <?php echo $network->domain . $network->path; ?></h3>
<?php if ( $sites ) {
	if ( RESCUE_ORPHANED_BLOGS && ENABLE_NETWORK_ZERO ) {
 ?>
					<div id="message" class="error">
						<p><?php _e('There are blogs associated with this network. ');  _e('Deleting it will move them to the holding network.'); ?></p>
						<p><label for="override"><?php _e('If you still want to delete this network, check the following box'); ?>:</label> <input type="checkbox" name="override" id="override" /></p>
					</div>
<?php } else { ?>
					<div id="message" class="error">
						<p><?php _e('There are blogs associated with this network. '); _e('Deleting it will delete those blogs as well.'); ?></p>
						<p><label for="override"><?php _e('If you still want to delete this network, check the following box'); ?>:</label> <input type="checkbox" name="override" id="override" /></p>
					</div>
<?php } ?>
<?php } ?>
					<p><?php _e('Are you sure you want to delete this network?'); ?></p>
					<input type="submit" name="delete" value="<?php _e('Delete Network'); ?>" class="button" /> <a href="./ms-admin.php?page=networks"><?php _e('Cancel'); ?></a>
				</div>
			</form>
<?php
		}
	}

	function wpmn_delete_multiple_network_page() {

		global $wpdb;

		if(isset($_POST['delete_multiple']) && isset($_POST['deleted_networks'])) {
			foreach($_POST['deleted_networks'] as $deleted_network) {
				$result = wpmn_delete_network((int)$deleted_network,(isset($_POST['override'])));
				if(is_a($result,'WP_Error')) {
					wp_die($result->get_error_message());
				}
			}
			$_GET['deleted'] = 'yes';
			$_GET['action'] = 'saved';
		} else {

			/** ensure a list of networks was sent */
			if(!isset($_POST['allnetworks'])) {
				wp_die(__('You have not selected any networks to delete.'));
			}
			$allnetworks = array_map(create_function('$val','return (int)$val;'),$_POST['allnetworks']);

			/** ensure each network is valid */
			foreach($allnetworks as $network) {
				if(!wpmn_network_exists((int)$network)) {
					wp_die(__('You have selected an invalid network for deletion.'));
				}
			}
			/** remove primary network from list */
			if(in_array(1,$allnetworks)) {
				$sites = array();
				foreach($allnetworks as $network) {
					if($network != 1) $sites[] = $network;
				}
				$allnetworks = $sites;
			}

			$query = "SELECT * FROM {$wpdb->site} WHERE id IN (" . implode(',',$allnetworks) . ')';
			$network = $wpdb->get_results($query);
			if(!$network) {
				wp_die(__('You have selected an invalid network or networks for deletion'));
			}

			$query = "SELECT * FROM {$wpdb->blogs} WHERE site_id IN (" . implode(',',$allnetworks) . ')';
			$sites = $wpdb->get_results($query);

?>
			<form method="POST" action="./ms-admin.php?page=networks"><div>
			<div id="icon-tools" class="icon32"></div>
			<h2><?php _e('Networks') ?></h2>
			<h3><?php _e('Delete Multiple Networks'); ?></h3>
<?php

			if ( $sites ) {
				if ( RESCUE_ORPHANED_BLOGS && ENABLE_NETWORK_ZERO ) {
?>

			<div id="message" class="error">
				<h3><?php _e('You have selected the following networks for deletion'); ?>:</h3>
				<ul>
<?php foreach( $network as $deleted_network ) { ?>
					<li><input type="hidden" name="deleted_networks[]" value="<?php echo $deleted_network->id; ?>" /><?php echo $deleted_network->domain . $deleted_network->path ?></li>
<?php } ?>
				</ul>
				<p><?php _e('There are blogs associated with one or more of these networks.  Deleting them will move these blgos to the holding network.'); ?></p>
				<p><label for="override"><?php _e('If you still want to delete these networks, check the following box'); ?>:</label> <input type="checkbox" name="override" id="override" /></p>
			</div>

<?php } else { ?>

			<div id="message" class="error">
				<h3><?php _e('You have selected the following networks for deletion'); ?>:</h3>
				<ul>
<?php foreach( $network as $deleted_network ) { ?>
					<li><input type="hidden" name="deleted_networks[]" value="<?php echo $deleted_network->id; ?>" /><?php echo $deleted_network->domain . $deleted_network->path ?></li>
<?php } ?>
				</ul>
				<p><?php _e('There are blogs associated with one or more of these networks.  Deleting them will delete those blogs as well.'); ?></p>
				<p><label for="override"><?php _e('If you still want to delete these networks, check the following box'); ?>:</label> <input type="checkbox" name="override" id="override" /></p>
			</div>

<?php
				}
			} else {

?>

			<div id="message">
				<h3><?php _e('You have selected the following networks for deletion'); ?>:</h3>
				<ul>
<?php foreach($network as $deleted_network) { ?>
					<li><input type="hidden" name="deleted_networks[]" value="<?php echo $deleted_network->id; ?>" /><?php echo $deleted_network->domain . $deleted_network->path ?></li>
<?php } ?>
				</ul>
			</div>

<?php
			}
?>
				<p><?php _e('Are you sure you want to delete these networks?'); ?></p>
				<input type="submit" name="delete_multiple" value="<?php _e('Delete Networks'); ?>" class="button" /> <input type="submit" name="cancel" value="<?php _e('Cancel'); ?>" class="button" />
			</div></form>
<?php
		}
	}
}
endif;