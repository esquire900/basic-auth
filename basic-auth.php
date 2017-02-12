<?php
/**
 * Plugin Name: JSON Basic Authentication
 * Description: Basic Authentication handler for the JSON API, used for development and debugging purposes
 * Author: WordPress API Team
 * Author URI: https://github.com/WP-API
 * Version: 0.1
 * Plugin URI: https://github.com/WP-API/Basic-Auth
 */

function json_basic_authentication_handler( $user ) {

	// Don't authenticate twice
	if ( ! empty( $user ) ) {
		return $user;
	}

	// Check that we're trying to authenticate
	if ( !isset( $_SERVER['PHP_AUTH_USER'] ) ) {
		return $user;
	}

	// Only authenticate for the REST APIs
	$checkfor = home_url(rest_get_url_prefix(), 'relative');
	if (0 !== strpos($_SERVER['REQUEST_URI'], $checkfor))
		return $user;

	$username = $_SERVER['PHP_AUTH_USER'];
	$password = $_SERVER['PHP_AUTH_PW'];

	/**
	 * In multi-site, wp_authenticate_spam_check filter is run on authentication. This filter calls
	 * get_currentuserinfo which in turn calls the determine_current_user filter. This leads to infinite
	 * recursion and a stack overflow unless the current function is removed from the determine_current_user
	 * filter during authentication.
	 */
	remove_filter( 'determine_current_user', 'json_basic_auth_handler', 20 );
	$user = wp_authenticate( $username, $password );
	add_filter( 'determine_current_user', 'json_basic_auth_handler', 20 );

	if ( is_wp_error( $user ) ) {
		return null;
	}
	return $user->ID;
}
add_filter( 'determine_current_user', 'json_basic_authentication_handler', 20 );

function json_basic_authorization_handler( $error ) {
	// Passthrough other errors
	if ( !empty( $error ) ) {
		return $error;
	}
	if ( !is_user_logged_in() )
		return new WP_Error( 'rest_not_logged_in', __( 'You are not currently logged in.' ), array( 'status' => 401 ) );
}
add_filter( 'rest_authentication_errors', 'json_basic_authorization_handler' );
