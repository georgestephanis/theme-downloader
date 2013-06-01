<?php
/*
 * Plugin Name: Theme Downloader
 * Plugin URI: http://wordpress.org/plugins/theme-downloader/
 * Description: Gives a quick and convenient way to download a theme installed on the current site as a zip file.
 * Author: George Stephanis
 * Version: 1.0
 * Author URI: http://stephanis.info/
 */

require_once( dirname(__FILE__) . '/class-theme-downloader.php' );

class Theme_Downloader_Plugin {
	static $instance;

	function __construct( $theme = null ) {
		self::$instance = $this;

		add_filter( 'theme_action_links', array( $this, 'theme_action_links' ), 10, 2 );
		add_action( 'wp_ajax_download_theme', array( $this, 'wp_ajax_download_theme' ) );
		add_action( 'admin_footer-themes.php', array( $this, 'admin_footer_themes_php' ) );
	}

	function theme_action_links( $actions, $theme ) {
		if( current_user_can( 'edit_themes' ) && Theme_Downloader::can_zip() ) {
			$args = array(
				'action' => 'download_theme',
				'theme' => $theme->get_stylesheet(),
			);
			$download_url = add_query_arg( $args, admin_url( 'admin-ajax.php' ) );
			$download_link = sprintf( '<a href="%s" target="_blank" style="text-decoration:none;">&darr;</a>', $download_url );
			$actions = array_merge( array( 'download' => $download_link ), $actions );
		}
		return $actions;
	}

	function wp_ajax_download_theme() {
		if( ! current_user_can( 'edit_themes' ) ) {
			wp_die( __( 'Cheatin&#8217; uh?' ) );
		}
		$theme = null;
		if( ! empty( $_REQUEST['theme'] ) ) {
			$theme = new WP_Theme( addslashes( $_REQUEST['theme'] ), get_theme_root() );
		}
		$zip_file_location = Theme_Downloader::build_zip( $theme );
		if( is_wp_error( $zip_file_location ) ) {
			wp_die( $zip_file_location->get_error_message() );
		}
		$result = Theme_Downloader::download( $zip_file_location );
		if( is_wp_error( $result ) ) {
			wp_die( $result->get_error_message() );
		}
	}

	function admin_footer_themes_php() {
		if( ! current_user_can( 'edit_themes' ) || ! Theme_Downloader::can_zip() ) {
			return;
		}
		$args = array(
			'action' => 'download_theme',
			'theme' => wp_get_theme()->get_stylesheet(),
		);
		$download_url = esc_url( add_query_arg( $args, admin_url( 'admin-ajax.php' ) ) );
		?>
		<script>jQuery('#current-theme .theme-options ul').prepend('<li><a href="<?php echo $download_url; ?>"><?php echo esc_attr__('Download'); ?></a></li>');</script>
		<?php
	}
}

new Theme_Downloader_Plugin;
