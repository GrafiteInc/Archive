<?php

/*
|--------------------------------------------------------------------------
| Archive Config
|--------------------------------------------------------------------------
*/

return [
	'disk' => env('ARCHIVE_DISK', env('FILESYSTEM_DISK', 'local')),

	'path' => env('ARCHIVE_PATH', 'archives'),

	'models' => [],

	'encryption' => [
		'enabled' => true,
		'driver' => 'crypt',
	],

	'query' => [
		'chunk_size' => 500,
		'date_column' => 'created_at',
		'scopes' => [],
		'relationships' => [],
	],

	'fixtures' => [
		'path' => 'fixtures',
		'seeders' => false,
	],
];
