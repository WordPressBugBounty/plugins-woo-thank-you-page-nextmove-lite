<?php
defined( 'ABSPATH' ) || exit;

$config             = array();
$config['slug']     = '_xlwcty_recently_viewed_product';
$config['title']    = __( 'Recently Viewed', 'woo-thank-you-page-nextmove-lite' );
$config['instance'] = require __DIR__ . '/instance.php';
$config['fields']   = array(
	'id'                     => $config['slug'],
	'position'               => 45,
	'xlwcty_accordion_head'  => __( 'Premium Components', 'woo-thank-you-page-nextmove-lite' ),
	'xlwcty_accordion_title' => $config['title'],
	'xlwcty_icon'            => 'xlwcty-fa xlwcty-fa-eye',
	'xlwcty_disabled'        => 'yes',
	'fields'                 => array(),
);
$config['default']  = array();

return $config;
