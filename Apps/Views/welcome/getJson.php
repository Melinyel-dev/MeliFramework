<?php
	header('Content-Type: application/json; charset=UTF-8');

	$map = new \stdClass();
	$map->message = "Hello, world !";

	$output = json_encode($map);


	header('Content-Length: '.mb_strlen($output));

	echo $output;