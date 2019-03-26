<?php

$settings = [
	['version', '0.0.1'],
	['community_name', 'K-Load'],
	['backgrounds', '{"duration":8000,"fade":750,"enable":0,"random":0}'],
	['description', 'Sample description'],
	['messages', '[]'],
	['rules', '[]'],
	['staff', '[]'],
	['youtube', '{"enable":0,"random":0,"volume":15,"list":[]}'],
	['test', '{{ user_id }}']
];

Database::conn()->insert("INSERT INTO `kload_settings` (`name`, `value`)")->values($settings)->execute();

?>
