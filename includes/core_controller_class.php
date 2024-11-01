<?php
namespace tied_pages;
/**
 * WP_Linked_Pages_Controller short summary.
 *
 * WP_Linked_Pages_Controller description.
 *
 * @version 1.0
 * @author mipfi
 *
 * Encoding:			UTF-8 (ẅ)
 */

class Tiedpages_Controller extends Tiedpages_Base
{
	public function __construct() {
		// PHP_INT_MAX as priority so update_tied_pages will (most likely) be the last function to be called
		add_action( 'save_post', array( &$this, 'update_tied_pages' ), PHP_INT_MAX, 2 );
	}


/**
   *
   * @param int $post_id
   * @param WP_Post $post
   * @return void
   */
	public function update_tied_pages( $post_id, $post ) {

		global $wpdb;

		// We do nothing if $post->ID is not valid
		if ( 0 == $post->ID ) {
			return;
		}

		remove_action( 'save_post', array( &$this, 'update_tied_pages' ), PHP_INT_MAX );

		// "no tied page" if tied_page_id is empty
		if ( false == isset( $_REQUEST['tied_page_id'] ) || 
			true == isset( $_REQUEST['tied_page_id'] ) && '' == $_REQUEST['tied_page_id'] ) {
			update_post_meta( $post->ID , 'tied_page_id', '' );
			$int_master_id = 0;
		} else {
			// the ID of the post to be tied with
			$int_master_id =  intval( $_REQUEST['tied_page_id'] );
		}

		$str_different_title = $_REQUEST['tied_pages_different_title'] ?? '';

		$this->update_related_tied_pages( $post->ID );

		// Only copy_master_content_to_child if not autosave or revision because 'save_post'-hook also goes thru them 
		// (so we dont save data in the wrong post on accident)
		if ( !wp_is_post_autosave( $post->ID ) && !wp_is_post_revision( $post->ID ) && $int_master_id > 0 ) {
			$this->copy_master_content_to_child( $int_master_id, $post->ID, $str_different_title, false );
		}

		add_action( 'save_post', array( &$this, 'update_tied_pages' ), PHP_INT_MAX, 2 );

		return;
	}

	/**
	 * Summary of update_related_tied_pages
	 * @return void
	 * If master page is changed, related pages change as well
	 */
	private function update_related_tied_pages( $post_id ) {
		global $wpdb;

		$arr_related_pages = $this->get_tied_pages_by_post_id( $post_id );

		if ( false != $arr_related_pages && !empty( $arr_related_pages ) ) {
			foreach ( $arr_related_pages as $single_related_page ) {
				$this->copy_master_content_to_child( $post_id, $single_related_page['ID'] );
			}
		}

		return;
	}

	/**
	 * Summary of copy_master_content_to_child
	 * @return void
	 * Ties a page to a master page
	 * 
	 * @param int $master_post_id
	 * @param int $child_post_id
	 * @param string $child_title_in_form		is empty if master page was updated
	 * @param boolean $updated_page_is_master	false if the child page was updated
	 */
	private function copy_master_content_to_child( $master_post_id, $child_post_id, $child_title_in_form = '', $updated_page_is_master = true ) {
		global $wpdb;

		$title_taken_from_master = false;

		$obj_master_post = get_post( $master_post_id );

		if ( null === $obj_master_post ) {
			wp_die( sprintf( __( 'Error. Master Post (ID: %d) does not exist.', 'tied-pages' ), $master_post_id ) );
		}
			
		// get the post to be overwritten
		$obj_child_post = get_post( $child_post_id );

		if ( null === $obj_child_post ) {
			wp_die( sprintf( __( 'Error. Child Post (ID: %d) does not exist.', 'tied-pages' ), $child_post_id ) );
		}

		$current_user = wp_get_current_user();
		$new_post_author_id = $current_user->ID;

		$child_title_in_db = get_post_meta( $child_post_id, 'tied_page_title', true );


		// check if post_content or post_title changed
		$has_post_changes = false;

		if ( $child_title_in_db == '' && $obj_child_post->post_title != $obj_master_post->post_title ) {
			$has_post_changes = true;
		} else if ( $obj_child_post->post_content != $obj_master_post->post_content ) {
			$has_post_changes = true;
		}

		if ( false == $updated_page_is_master ) {
			if ( $child_title_in_db !== $child_title_in_form ) {
				$has_post_changes = true;
			}

			if ( $child_title_in_form == '' ) {
				$child_title_in_db = $obj_master_post->post_title;
				$has_post_changes = true;
				$title_taken_from_master = true;
			} else {
				$child_title_in_db = $child_title_in_form;
				$has_post_changes = true;
			}

		}

		// If the master page was changed, we update the child title if it wasnt set individually
		if ( true == $updated_page_is_master && $child_title_in_db == '' ) {
			$child_title_in_db = $obj_master_post->post_title;

			$title_taken_from_master = true;
		}

		// No Changes possible if the post is linked with another
		if ( $has_post_changes ) {
			$obj_child_post->comment_status = $obj_master_post->comment_status;
			$obj_child_post->ping_status = $obj_master_post->ping_status;
			$obj_child_post->post_author = $new_post_author_id;
			$obj_child_post->post_content = $obj_master_post->post_content;
			$obj_child_post->post_excerpt = $obj_master_post->post_excerpt;

			// When making a new page WP automatically sets the post_id as the post_name
			// In this case we make post_name the same as the parent post_name
			if ( $obj_child_post->post_name == $child_post_id ) {
				$obj_child_post->post_name = $obj_master_post->post_name;
			}

			//$obj_child_post->post_parent = $obj_master_post->post_parent;
			$obj_child_post->post_password = $obj_master_post->post_password;
			$obj_child_post->post_status = $obj_master_post->post_status;
			$obj_child_post->post_title = $child_title_in_db;
			$obj_child_post->post_type = $obj_master_post->post_type;
			$obj_child_post->to_ping = $obj_master_post->to_ping;
			//$obj_child_post->menu_order = $obj_master_post->menu_order;
		
			// Overwrite the post with the info from the tied page
			$new_post_id = wp_update_post( $obj_child_post, false, false );
		}
		
		
		// get parent page taxonomies
		$current_post_taxonomies = get_object_taxonomies( $obj_master_post->post_type );

		// set taxonomies
		if ( !empty( $current_post_taxonomies ) && is_array( $current_post_taxonomies ) ):
			foreach ( $current_post_taxonomies as $current_taxonomy ) {
				$current_post_terms = wp_get_object_terms( $master_post_id, $current_taxonomy, 
					array( 
						'fields' => 'slugs' 
					) 
				);
				wp_set_object_terms( $child_post_id, $current_post_terms, $current_taxonomy, false );
			}
		endif;

		$get_postmeta_query = 'SELECT `meta_key`, `meta_value` FROM `' . $wpdb->postmeta . '`' . 
			' WHERE `post_id` = ' . $master_post_id . ';';

		// get postmeta from the parent page
		$post_meta_infos = $wpdb->get_results( $get_postmeta_query );

		if ( count( $post_meta_infos ) != 0 ) {
			// Transaction so we dont lose data (rollback if insert doesn't work)
			$wpdb->query( 'START TRANSACTION' );
			try {
				$wpdb->query( 'DELETE FROM `' . $wpdb->postmeta . '` WHERE `post_id` = ' . $child_post_id . ';' );
				$postmeta_query = 'INSERT INTO `' . $wpdb->postmeta . '` (post_id, meta_key, meta_value) ';

				foreach ( $post_meta_infos as $meta_info ) {
					$meta_key = sanitize_text_field( $meta_info->meta_key );
					$meta_value = addslashes( $meta_info->meta_value );
					$postmeta_query_select[] = $child_post_id . ', "' . $meta_key . '", "' . $meta_value . '"';
				}

				// If the title is taken from the master, we delete the tied_page_title
				$str_title_in_db = $title_taken_from_master ? '' : $child_title_in_db;
				
				// add the alternate post title
				$postmeta_query_select[] = $child_post_id . ', "tied_page_title", "' . $str_title_in_db . '"';

				$postmeta_query .= 'VALUES (';
				$postmeta_query .= implode( '),(', $postmeta_query_select );
				$postmeta_query .= ');';

				
				$insert_result = $wpdb->query( $postmeta_query );

				if ( false !== $insert_result ) {
					$wpdb->query( 'COMMIT' );

					update_post_meta( $child_post_id , 'tied_page_id', intval( $master_post_id ) );
				} else {
					$wpdb->query( 'ROLLBACK' );
				}
			} catch ( Exception $exception ) {
				$wpdb->query( 'ROLLBACK' );

				wp_die( $exception->getMessage() );
			}
		}

		return;
	}
}

