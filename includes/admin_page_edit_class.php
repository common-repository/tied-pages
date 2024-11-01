<?php
namespace tied_pages;
/**
 * wp_lp_admin_page_edit short summary.
 *
 * wp_lp_admin_page_edit description.
 *
 * @version 1.0
 * @author mipfi
 *
 * Encoding:			UTF-8 (ẅ)
 */

class Tiedpages_Admin_Page_Edit extends Tiedpages_Base {
	private $str_plugin_dir_url;

	public function __construct( $str_plugin_dir_url ) {
		$this->str_plugin_dir_url = $str_plugin_dir_url;

		// add action / filter oder hooks für admin
		add_action( 'add_meta_boxes_page', array( &$this, 'adding_page_clone_meta_boxes' ) );
		add_action( 'admin_enqueue_scripts', array( &$this, 'add_admin_scripts' ) );
		add_action( 'admin_notices', array( &$this, 'classic_editor_admin_notice') );
	}

	/**
	 * Summary of classic_editor_admin_notice
	 */
	public function classic_editor_admin_notice() {
		global $post_id;

		$screen = get_current_screen();

		$arr_active_plugins = get_option( 'active_plugins' );

		$tied_page_id = get_post_meta( $post_id, 'tied_page_id', true );

		// Only render this notice in the post editor.
		if ( ! $screen || 'post' !== $screen->base ) {
			return;
		}
		
		if ( in_array( 'classic-editor/classic-editor.php', $arr_active_plugins ) == true &&
			'' !== $tied_page_id && false !== $tied_page_id ) {
	
			// with a 'notice' class. javascript:scrollToEvent(jQuery("#tied-page-metabox"));
			echo '<div class="notice notice-info"><p>';
			echo sprintf( __( 'Tied-Page Info: <a href="post.php?post=%d&action=edit" target="_blank">' . 
				'View master page</a>', 'tied-pages' ), $tied_page_id );
			echo ' (To the <a href="javascript:scrollToEvent(jQuery(\'#tied-page-metabox\'));">tied-page menu</a>)</p>';
			echo '</div>';
		}
	}

	// enqueue admin styles and scripts
	function add_admin_scripts() {
		wp_enqueue_style( 'tp-admin-style', $this->str_plugin_dir_url . 'css/tied-pages.css' );
		wp_enqueue_script( 'tp-admin-script', $this->str_plugin_dir_url . 'js/tp_functions.js' );
	}


	function adding_page_clone_meta_boxes( $post ) {
		add_meta_box(
		    'tied-page-metabox',
		    __( 'Tied Pages', 'tied-pages' ),
		    array( &$this, 'render_customizable_tied_pages_metabox'),
		    'page',
		    'normal',
		    'default'
		);
	}

	/**
	 * Summary of render_customizable_tied_pages_metabox
	 * @param WP_Post $post
	 * @param mixed $metabox
	 */
	function render_customizable_tied_pages_metabox( $post, $metabox ) {
		// get the related pages if they exist
		$arr_related_pages = $this->get_tied_pages_by_post_id( $post->ID );
		$arr_already_tied_pages = $this->get_all_tied_pages();

		// get the tp_post_title
		$str_tp_title = get_post_meta( $post->ID, 'tied_page_title', true );
		$int_tied_page_id = get_post_meta( $post->ID, 'tied_page_id', true );

		$str_different_title_html = '';
		$str_dropdown_disabled = 'disabled';
		$bool_is_master_page = true;

		// You can only set a different title if the page is not a master-page
		if ( 0 == count( $arr_related_pages ) ) {
			$str_different_title_html = '<label for="tied_pages_different_title">' . 
				'<p>' . __( 'Post title', 'tied-pages' ) . '</p>' . 
				'<input type="text" name="tied_pages_different_title" id="tied_pages_different_title" ' . 
				'value="' . $str_tp_title . '"/></label>';
				
			$str_dropdown_disabled = '';
			$bool_is_master_page = false;
		}
		
		if ( is_post_type_hierarchical( $post->post_type ) ) :
/*			
			$dropdown_args = array(
				'post_type'			=> $post->post_type,
				'selected'			=> get_post_meta( $post->ID, 'tied_page_id', true),
				'name'				=> 'tied_page_id',
				'class'				=> $str_dropdown_class,
				'show_option_none'	=> __( '(none)', 'tied-pages' ),
				'sort_column'		=> 'menu_order, post_title',
				'exclude'			=> [ $post->ID ],
				'echo'				=> 0,
			);

			$dropdown_args = apply_filters( 'page_attributes_dropdown_pages_args', $dropdown_args, $post );
*/			
			$arr_pages = get_pages();

			// if a page is already tied, we remove the page from the array so you cannot tie another one to it.
			for ( $i = 0; $i < count( $arr_pages ); $i++ ) {
				foreach ( $arr_already_tied_pages as $tied_page ) {
					if ( $arr_pages[$i]->ID == $tied_page['post_id'] ) {
						unset( $arr_pages[$i] );
					} else if ( $arr_pages[$i]->ID == $post->ID ) {
						unset( $arr_pages[$i] );
					}
				}
			}
			

			if ( ! empty( $arr_pages ) ) :
?>
				<div class="row">
					<div class="col">
						<p class="post-attributes-label-wrapper">
							<label class="post-attributes-label" for="tied_page_id">
								<?php echo __( 'Tied page', 'tied-pages' );?>
							</label>
						</p>

						<select id="tied_page_id" name="tied_page_id" <?php echo $str_dropdown_disabled;?>>
							<option value=""><?php echo __( '(none)', 'tied-pages' );?></option>
						<?php
							foreach ( $arr_pages as $single_page ) {
								$str_selected = intval( $single_page->ID ) == intval( $int_tied_page_id ) 
									? 'selected="selected"' : '';
								echo '<option value="' . $single_page->ID . '" ' . $str_selected . '>' . 
									$single_page->post_title . '</option>';
							}
						?>
						</select>
						<?php
							if ( $bool_is_master_page ) {
								echo '<i>' . __( 'You cannot tie a master-page to another page', 'tied-pages' ) . '</i>';
							}
						?>

					</div>
					<div class="col">
					<?php
						// we list the child pages if there are any 
						if ( false != $arr_related_pages && !empty( $arr_related_pages ) ) {
							echo '<p>' . __( 'Tied Pages', 'tied-pages' ) . ':</p>';
							foreach ( $arr_related_pages as $single_related_page ) {
							?>
							<ul>
								<li><?php echo '<a href="post.php?post=' . $single_related_page['ID'] . '&action=edit">' . 
									$single_related_page['post_title'] . '</a> (ID: ' . $single_related_page['ID'] . ')';?></li>
							</ul>
							<?php
							}
						}
					?>
					</div>
					<div class="col">
<?php
					echo $str_different_title_html;
?>
					</div>
				</div>
				<?php
			endif; // end empty pages check
		endif;  // end hierarchical check.
	}
}