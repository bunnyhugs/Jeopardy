<?php

require 'vendor/autoload.php';

$gameId = filter_input(INPUT_GET, 'game', FILTER_SANITIZE_STRING);

if (isset($gameId)) {
	echo $gameId;
}

$question_filename = isset($argv[1]) ? $argv[1] : "questions";

$server = new \Depotwarehouse\Jeopardy\Server(\React\EventLoop\Factory::create());

try {
    $boardFactory = new \Depotwarehouse\Jeopardy\Board\BoardFactory($question_filename);
    $server->run($boardFactory);
} catch (\React\Socket\ConnectionException $exception) {
    echo "Error occurred: " . get_class($exception) . "\n";
    echo $exception->getMessage();
    die();
}

