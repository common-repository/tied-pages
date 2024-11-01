<?php
namespace tied_pages;
/**
 * wp_linked_page_base short summary.
 *
 * wp_linked_page_base description.
 *
 * @version 1.0
 * @author mipfi
 *
 *
 */

abstract class Tiedpages_Base {
	public function __construct() { }

	protected function grant_access( $nonce, $post_id ) {
		$bool_result = false;
		if( wp_verify_nonce( $nonce, 'pp-linked-pages-' . $post_id ) && current_user_can( 'edit_posts' ) ) {
			// Nonce ist Gültig
			$bool_result = true;
		} else {
			wp_die( 'Security check issue, Please try again.' );
		}

		return $bool_result;
	}

	/*
	* Redirect function
	*/
	protected function tp_redirect( $url ) {
		echo '<script>window.location.href="' . $url . '";</script>';
	}

	/**
	 * Summary of get_tied_pages_by_post_id
	 * @param int $post_id
	 */
	protected function get_tied_pages_by_post_id( $post_id ) {
		global $wpdb;

		$tp_query = 'SELECT `p`.`ID`, `p`.`post_title` FROM `' . $wpdb->posts . '` AS `p` 
			INNER JOIN `' . $wpdb->postmeta . '` AS `m` 
			ON `p`.`ID` = `m`.`post_id`
			WHERE `m`.`meta_key` = "tied_page_id" AND `m`.`meta_value` = ' . $post_id . 
			' AND `p`.`post_status` != "trash" ORDER BY `p`.`post_title`;';

		$tp_result = $wpdb->get_results( $tp_query, ARRAY_A );

		return $tp_result;
	}


	protected function get_all_tied_pages() {
		global $wpdb;

		$query = 'SELECT `post_id` FROM `' . $wpdb->postmeta . '` WHERE `meta_key` = "tied_page_id" AND `meta_value` != "";';

		$result = $wpdb->get_results( $query, ARRAY_A );

		return $result;
	}
}