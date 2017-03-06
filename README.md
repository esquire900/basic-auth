# Basic Authentication handler
This plugin adds Basic Authentication over HTTPS to a WordPress site.
It will only function over an HTTPS connection by design.
This plugin is better suited for development and test scenarios.
An [OAuth 1.0a][oauth] authentication handler is recommended for production.

## Installing
1. Download the plugin into your plugins directory
2. Enable in the WordPress admin

## Using
This plugin adds support for Basic Authentication [RFC2617][] over HTTPS.
This plugin requires HTTPS because this authentication method sends your
username and password in cleartext with every request. Requiring
an encrypted HTTPS connection is a bare minimum effort to hide the cleartext
within it.

Most HTTP clients will allow you to use this authentication natively. Some
examples are listed below.

### cURL

```sh
curl --user admin:password http://example.com/wp-json/
```

### WP_Http

```php
$args = array(
	'headers' => array(
		'Authorization' => 'Basic ' . base64_encode( $username . ':' . $password ),
	),
);
```

[oauth]: https://github.com/WP-API/OAuth1
[RFC2617]: https://tools.ietf.org/html/rfc2617
