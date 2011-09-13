<?php
if ( ! class_exists('Mini_Site') ) :

class Mini_Site {
	public static function resident_post_type() {
		$labels = array(
			'name' => _x('Residents', 'post type general name'),
			'singular_name' => _x('Resident', 'post type singular name'),
			'add_new' => _x('Add New', 'resident'),
			'add_new_item' => __('Add New Resident'),
			'edit_item' => __('Edit Resident'),
			'new_item' => __('New Resident'),
			'all_items' => __('All Residents'),
			'view_item' => __('View Resident'),
			'search_items' => __('Search Residents'),
			'not_found' =>  __('No residents found'),
			'not_found_in_trash' => __('No residents found in Trash'),
			'parent_item_colon' => '',
			'menu_name' => 'Residents'
		);

		$args = array(
			'labels' => $labels,
			'public' => true,
			'publicly_queryable' => true,
			'show_ui' => true,
			'show_in_menu' => true,
			'query_var' => true,
			'rewrite' => true,
			'capability_type' => 'post',
			'has_archive' => false,
			'hierarchical' => false,
			'menu_position' => 5,
			'menu_icon' => MINI_SITE_PLUGIN_DIR . '/images/people.png',
			'supports' => array('title','thumbnail')
		);

		register_post_type( 'resident', $args );
	}

	public static function enter_name_here( $title ) {
		$screen = get_current_screen();

		if ( 'resident' == $screen->post_type )
			$title = "Enter Resident's Name";

		return $title;
	}

	public static function add_headshot() {
		remove_meta_box( 'postimagediv', 'resident', 'side' );
		add_meta_box( 'postimagediv', 'Photo', 'post_thumbnail_meta_box', 'resident', 'side' );
	}

	public static function rename_headshot( $featured ) {
		if( strpos($featured, 'Set featured image') != 0 )
			$featured = str_replace('Set featured image', 'Set resident photo', $featured);

		if( strpos($featured, 'Remove featured image') != 0 )
			$featured = str_replace('Remove featured image', 'Remove resident photo', $featured);
		return $featured;
	}

	public static function register_sidebar() {
		$args = array(
			'name'          => __('Resident Sidebar'),
			'id'            => 'resident-sidebar',
			'description'   => '',
			'before_widget' => '<li id="%1$s" class="widget %2$s">',
			'after_widget'  => '</li>',
			'before_title'  => '<h2 class="widgettitle">',
			'after_title'   => '</h2>'
		);

		register_sidebar($args);
	}

	public static function get_tags_with_count( $post, $format = 'list', $before = '', $sep = '', $after = '' ) {
		$posttags = get_the_tags($post->ID, 'post_tag' );

		if ( !$posttags )
			return '';

		foreach ( $posttags as $tag ) {
			if ( $tag->count > 1 && !is_tag($tag->slug) ) {
				$tag_link = '<a href="' . get_term_link($tag, 'post_tag' ) . '" rel="tag">' . $tag->name . ' ( ' . number_format_i18n( $tag->count ) . ' )</a>';
			} else {
				$tag_link = $tag->name;
			}

			if ( $format == 'list' )
				$tag_link = '<li>' . $tag_link . '</li>';

			$tag_links[] = $tag_link;
		}

		return apply_filters( 'tags_with_count', $before . join( $sep, $tag_links ) . $after, $post );
	}

	public static function tags_with_count( $format = 'list', $before = '', $sep = '', $after = '' ) {
		global $post;
		echo Mini_Site::get_tags_with_count( $post, $format, $before, $sep, $after );
	}

	public static function get_album_id( $name ) {
		if(!defined('WPPA_ALBUMS')) return null;
		
		global $wpdb;
		
		$album = $wpdb->query($wpdb->prepare("SELECT id FROM " . WPPA_ALBUMS . " WHERE name = %s", $name));

		return $album;
	}

	public static function comments( $comment, $args ) {
		$GLOBALS['comment'] = $comment;

			?>
	<li id="comment-<?php comment_ID(); ?>" <?php comment_class(); ?>>
		<?php do_action( 'p2_comment' ); ?>

		<?php echo get_avatar( $comment, 32 ); ?>
		<h4>
			<?php echo get_comment_author_link(); ?>
		</h4>
		<div id="commentcontent-<?php comment_ID(); ?>" class="<?php echo esc_attr( $content_class ); ?>"><?php
				echo apply_filters( 'comment_text', $comment->comment_content );

				if ( $comment->comment_approved == '0' ): ?>
					<p><em><?php esc_html_e( 'Your comment is awaiting moderation.', 'p2' ); ?></em></p>
				<?php endif; ?>
		</div>
	<?php
	}

	public static function register_post_status() {
		$args = array(
			'label' => 'Hidden',
			'public' => false,
			'label_count' => _n_noop( 'Hidden <span class="count">(%s)</span>', 'Hidden <span class="count">(%s)</span>' ),
			'exclude_from_search' => false,
			'show_in_admin_all' => false,
			'publicly_queryable' => true,
			'show_in_admin_status_list' => true,
			'show_in_admin_all_list' => false
		);

		register_post_Status( 'hidden', $args );
	}
}

endif;