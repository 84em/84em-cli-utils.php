<?php

/**
 * Plugin Name: 84EM CLI
 * Description: WP-CLI Utility Functions
 * Version:     1.0.0
 * Author:      84EM
 * Author URI:  https://www.84em.com/
 */

namespace EightyFourEM;

defined( 'ABSPATH' ) or die;

if ( class_exists( '\WP_CLI' ) ) {

	if ( ! class_exists( '\EightyFourEM\CLI' ) ) {

		class CLI {

			/**
			 * Manages Slider Revolution plugin
			 *
			 * ## options
			 *
			 * <action>
			 * : list or delete
			 *
			 */
			public function revslider( $args, $assoc_args ) {

				global $wpdb;

				$results = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}revslider_sliders", ARRAY_A );

				switch ( $args[0] ) {

					case 'list':
						if ( count( $results ) === 0 ) {
							\WP_CLI::error( "No sliders found" );
						}
						else {
							\WP_CLI\Utils\format_items( 'table', $results, array_keys( $results[0] ) );
						}
						break;

					case 'delete':
						if ( count( $results ) === 0 ) {
							\WP_CLI::runcommand( 'plugin uninstall --deactivate revslider' );
						}
						else {
							\WP_CLI::error( 'Sliders were found. Refused to delete' );
						}
						break;

					default:
						\WP_CLI::error( 'Command not found' );
						break;
				}
			}

			/**
			 * Gets ManageWP Connection Key
			 */
			public function managewp( $args, $assoc_args ) {

				$mwp_potential_key = get_option( 'mwp_potential_key' );
				if ( $mwp_potential_key ) {
					\WP_CLI::success( sprintf( "Connection Key = %s", $mwp_potential_key ) );
				}
				else {
					\WP_CLI::error( "Unable to find connection key" );
				}
			}


			/**
			 * Imports Wordfence settings
			 *
			 * ## OPTIONS
			 *
			 * <config>
			 * : Import key
			 *
			 * @subcommand wordfence-import
			 */
			public function wordfence_import( $args, $assoc_args ) {

				$config = $args[0];

				if ( ! class_exists( '\wordfence' ) ) {

					\WP_CLI::error( "wordfence class is missing" );
				}

				\wordfence::importSettings( $config );

				\WP_CLI::success( sprintf( "\wordfence::importSettings(%s);", $config ) );
			}

			/**
			 * Sets a Wordfence configuration value
			 *
			 * ## OPTIONS
			 *
			 * <config>
			 * : Configuration key
			 *
			 * <value>
			 * : Configure value
			 *
			 * @subcommand wordfence-config
			 */
			public function wordfence_config( $args, $assoc_args ) {

				$config = $args[0];
				$value  = $args[1];

				if ( ! class_exists( '\wfConfig' ) ) {

					\WP_CLI::error( "wfConfig class is missing" );
				}

				\wfConfig::set( $config, $value );

				\WP_CLI::success( sprintf( "wfConfig::set('%s','%s');", $config, $value ) );
			}

			/**
			 * Activates ACF Pro license
			 *
			 * ## OPTIONS
			 *
			 * <license>
			 * : License key
			 *
			 */
			public function acf( $args, $assoc_args ) {

				$license = $args[0];

				if ( ! function_exists( 'acf_get_setting' ) ) {

					\WP_CLI::error( "ACF does not appear active" );
				}

				include_once( ABSPATH . 'wp-content/plugins/advanced-custom-fields-pro/acf.php' );

				$post = [
					'acf_license' => $license,
					'acf_version' => acf_get_setting( 'version' ),
					'wp_name'     => get_bloginfo( 'name' ),
					'wp_url'      => home_url(),
					'wp_version'  => get_bloginfo( 'version' ),
					'wp_language' => get_bloginfo( 'language' ),
					'wp_timezone' => get_option( 'timezone_string' ),
				];

				$response = acf_updates()->request( 'v2/plugins/activate?p=pro', $post );

				if ( is_string( $response ) ) {

					\WP_CLI::error( sprintf( "%s", wp_strip_all_tags( $response) ) );
				}

				if ( is_wp_error( $response ) ) {

					\WP_CLI::error( sprintf("%s"), $response->get_error_message() );
				}

				if ( $response['status'] == 1 ) {

					\WP_CLI::success( sprintf( "%s", wp_strip_all_tags( $response['message'] ) ) );
					acf_pro_update_license( $response['license'] ); // update license
				}
				else {

					\WP_CLI::error( sprintf( "%s", wp_strip_all_tags( $response['message'] ) ) );
				}
			}

			/**
			 * Removes all default WP themes (that are not active!)
			 *
			 * @subcommand remove-default-themes
			 */
			public function remove_default_themes( $args, $assoc_args ) {

				$current_theme = get_option( 'template' );

				$themes = [
					'twentyten',
					'twentyeleven',
					'twentytwelve',
					'twentythirteen',
					'twentyfourteen',
					'twentyfifteen',
					'twentysixteen',
					'twentyseventeen',
					'twentynineteen',
					'twentytwenty',
					'twentytwentyone',
					'twentytwentytwo',
				];

				foreach ( $themes as $theme ) {

					if ( $theme !== $current_theme ) {

						$theme_to_delete = wp_get_theme( $theme );

						if ( $theme_to_delete->exists() ) {

							WP_CLI::runcommand( 'theme delete ' . $theme );
						}
					}
				}
			}
		}

		\WP_CLI::add_command( '84em', '\EightyFourEM\CLI' );
	}
}
