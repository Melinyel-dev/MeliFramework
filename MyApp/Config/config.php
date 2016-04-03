<?php
$config = [
	'environment' => 'testing',
    'maintenance' => false,

    'display_name' => 'MeliFramework',
    'app_name' => 'MyApp',

	'default_format' => 'php',
    'encryption_key' => '243A0C282C381E282E20BA9E0FEC582X',

	'hash' => [
    	'cost' => 8,
    	'salt_method' => 'default_hash_salt_method'
    ],

	'memcache' => [
    	'host' => '127.0.0.1',
    	'port' => 11211
    ],

	'auth' => [
    	'class' => 'Compte',
    	'login' => 'mail',
    	'password' => 'password'
    ],

	'cancan' => [
    	'enabled' => false,
    	'login_page' => '/account/login'
    ],

    'enable_hooks' => false,
    'external_libs_handler' => false,
    'user_agent_dispatcher' => true
];