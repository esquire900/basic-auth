<?php
/**
 * Plugin Name: REST API Basic Authentication
 * Description: Basic Authentication handler for the Wordpress REST API. Recommended only for development and debugging purposes
 * Author: Dale Phurrough
 * Author URI: https://hidale.com/
 * Version: 0.2
 * Plugin URI: https://github.com/diablodale/Basic-Auth
 */

function rest_basic_authentication_register_routes( $response_object ) {
	if ( empty( $response_object->data['authentication'] ) ) {
		$response_object->data['authentication'] = array();
	}
	$response_object->data['authentication']['Basic'] = array(
		'version' => '0.2',
	);
	return $response_object;
}
add_filter( 'rest_index', 'rest_basic_authentication_register_routes' );

function rest_basic_authentication_handler( $user ) {

	// Don't authenticate twice
	if ( ! empty( $user ) ) {
		return $user;
	}

	// Check that we're trying to authenticate
	if ( !isset( $_SERVER['PHP_AUTH_USER'] ) || !isset( $_SERVER['PHP_AUTH_PW'] )) {
		return $user;
	}

	// Only authenticate for the REST APIs
	$checkfor = home_url(rest_get_url_prefix(), 'relative');
	if (0 !== strpos($_SERVER['REQUEST_URI'], $checkfor))
		return $user;

	// Check that we're over HTTPS
	if ( (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
		 (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
		 (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on') ) {
		// using https; continue
	}
	else {
		return $user;
	}

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
add_filter( 'determine_current_user', 'rest_basic_authentication_handler', 20 );

function rest_basic_authorization_handler( $error ) {
	// Passthrough other errors
	if ( !empty( $error ) ) {
		return $error;
	}
	if ( !is_user_logged_in() )
		return new WP_Error( 'rest_not_logged_in', __( 'You are not currently logged in.' ), array( 'status' => 401 ) );
}
add_filter( 'rest_authentication_errors', 'rest_basic_authorization_handler' );
