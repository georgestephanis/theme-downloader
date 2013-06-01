<?php

class Theme_Downloader {

	static function can_zip() {
		return class_exists( 'ZipArchive' );
	}

	static function build_zip( $theme = null ) {
		if( ! is_a( $theme, 'WP_Theme' ) ) {
			$theme = wp_get_theme();
		}

		$zip = new ZipArchive();

		$zip_file_name = wp_unique_filename( get_temp_dir(), "{$theme->get_stylesheet()}.zip" );
		$zip_file_location = get_temp_dir() . $zip_file_name;
		if ( true !== $zip->open( $zip_file_location, ZIPARCHIVE::CREATE ) ) {
			$message = sprintf( __( 'Error: Could not create zip file at `%s`.' ), $zip_file_location );
			return new WP_Error( 'zip-create', $message );
		}

		$errors = array();
		$files = $theme->get_files( null, -1 );
		foreach( $files as $relative_path => $absolute_path ) {
			if( ! $zip->addFile( $absolute_path, $relative_path ) ) {
				$errors[] = sprintf( __('Error: Could not add file `%s` to archive.'), $absolute_path );
			}
		}

		$zip->close();

		if( $errors ) {
			return new WP_Error( 'zip-errors', __('Some errors occurred while adding files to the archive.'), $errors );
		}

		return $zip_file_location;
	}

	static function download( $file_path ) {
		if ( headers_sent() ) {
			return new WP_Error( 'headers-sent', __( 'Error: Cannot download file, headers already sent!' ) );
		}

		if ( ! $file_path = realpath( $file_path ) ) {
			return new WP_Error( 'missing-file', __( 'Error: Cannot download file, file does not exist!' ) );
		}

		$basename = basename( $file_path );
		$filesize = filesize( $file_path );

		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Content-Description: File Transfer");
		header("Content-Disposition: attachment; filename={$basename}");
		header("Content-Length: {$filesize}");
		header("Expires: 0");
		header("Pragma: public");

		readfile( $file_path );

		@unlink( $file_path );
		exit;
	}
}
