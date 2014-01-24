<?php
/*
 * Plugin Name: Theme Downloader
 * Plugin URI: http://wordpress.org/plugins/theme-downloader/
 * Description: Gives a quick and convenient way to download a theme installed on the current site as a zip file.
 * Author: George Stephanis
 * Version: 1.0.1
 * Author URI: http://stephanis.info/
 */

require_once( dirname(__FILE__) . '/class-theme-downloader.php' );

class Theme_Downloader_Plugin {
	static $instance;

	function __construct( $theme = null ) {
		self::$instance = $this;

		add_filter( 'theme_action_links', array( $this, 'theme_action_links' ), 10, 3 );
		add_action( 'wp_ajax_download_theme', array( $this, 'wp_ajax_download_theme' ) );
		if ( version_compare( $GLOBALS['wp_version'], '3.8', '<' ) ) {
			add_action( 'admin_footer-themes.php', array( $this, 'admin_footer_themes_php' ) );
		} else {
			add_filter( 'wp_prepare_themes_for_js', array( $this, 'wp_prepare_themes_for_js' ) );
			add_action( 'tmpl-theme-single_actions', array( $this, 'tmpl_theme_single_actions' ) );
		}
	}

	function theme_action_links( $actions, $theme, $ms_theme = null ) {
		if( is_a( $ms_theme, 'WP_Theme' ) ) {
			// The user is in the Network Admin panel, and it passes different arguments to the filter.
			$theme = $ms_theme;
		}
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
			@unlink( $zip_file_location );
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

	function wp_prepare_themes_for_js( $prepared_themes ) {
		foreach ( $prepared_themes as $slug => $data ) {
			$download_url = null;
			if ( current_user_can( 'edit_themes' ) && Theme_Downloader::can_zip() ) {
				$args = array(
					'action' => 'download_theme',
					'theme' => $slug,
				);
				$download_url = esc_url( add_query_arg( $args, admin_url( 'admin-ajax.php' ) ) );
			}
			$prepared_themes[ $slug ]['actions']['download'] = $download_url;
		}
		return $prepared_themes;
	}

	function tmpl_theme_single_actions( $active_or_inactive ) {
		?>
		<# if ( data.actions.download ) { #>
			<a href="{{{ data.actions.download }}}" target="_blank" class="button button-secondary" title="<?php esc_attr_e( 'Download' ); ?>"><?php esc_html_e( 'Download' ); ?></a>
		<# } #>
		<?php
	}
}

new Theme_Downloader_Plugin;
