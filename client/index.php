<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

require_once '../vendor/autoload.php';

$urlPath = dirname($_SERVER['PHP_SELF']);
if ($urlPath != '/') {
    // add trailing slash
    $urlPath = $urlPath . "/";
}

$pos = strrpos($urlPath, "client/");
if ($pos !== false) {
    $urlPath = substr($urlPath, 0, $pos);
    $urlPath .= "client/";
}

$contestantsJson = json_decode(file_get_contents('../game_data/contestants.json'), true);

$config = [];
$config['players'] = array_map(function(array $contestant_info) {
	//    return ucfirst(strtolower($contestant_info['name']));
	return ($contestant_info['name']);

}, $contestantsJson['contestants']);

$config['display_host'] = false;
$config['path'] = $urlPath;

$loader = new Twig_Loader_Filesystem('views');

$twig = new Twig_Environment($loader);

$router = new \League\Route\RouteCollection();

$router->get('/', function (Request $request, Response $response) use ($twig, $config) {
    $response->setContent($twig->render('index.html.twig', [ 'players' => $config['players'], 'path' => $config['path'] ]));
    return $response;
});

$router->get('/play', function (Request $request, Response $response, array $args) use ($twig, $config) {
    return new \Symfony\Component\HttpFoundation\RedirectResponse('/');
});

$router->get('/server', function (Request $request, Response $response, array $args) use ($twig, $config) {
	$gameId = filter_input(INPUT_GET, 'game', FILTER_SANITIZE_STRING);
    $content = 'hi';
	if (isset($gameId)) {
		$content = 'gameId ' . $gameId . '<br>';
		$processes = null;
		exec("wmic process where \"caption='php.exe' and commandline like '%server.php%'\" get processid", $processes);
		
		foreach ($processes as $pid) {
			if (($pid == "ProcessId") || ($pid == "")) {
				continue;
			}
			$content .= "taskkill /pid " . $pid . "<br>";
			$result = null;
			exec("taskkill /f /pid  " . $pid, $result);
			$content .= $result[0] . '<br>';
		}

		if (is_numeric($gameId)) {
			$cmd = 'C:\apache24\php\php.exe ..\parser\parser.php ' . escapeshellarg($gameId);
			if (substr(php_uname(), 0, 7) == "Windows") {
				$content .= ' running ' . $cmd . '<br>';
				$parser = popen($cmd, "r");
				while (!feof($parser)) {
					$content .= fread($parser, 8192) . '<br>';
				}
				pclose($parser);
			} else {
				exec($cmd . " > /dev/null &");  
			}
			  $cmd = 'C:\apache24\php\php.exe ..\server.php ' . escapeshellarg($gameId) . ' >NUL 2>NUL';
			if (substr(php_uname(), 0, 7) == "Windows") {
				$content .= ' running ' . $cmd . '<br>';
				pclose(popen("start /B " . $cmd, "r"));
			} else {
				exec($cmd . " > /dev/null &");  
			}
		}
    }
	
    $response->setContent(
        $content
    );

    return $response;
});

$router->get('/obs', function (Request $request, Response $response, array $args) use ($twig, $config) {
    $response->setContent(
        $twig->render(
            'obs.html.twig',
            [ 'players' => $config['players'], 'display_host' => $config['display_host'], 'path' => $config['path'] ]
        )
    );
    return $response;
});

$router->get('/board', function (Request $request, Response $response, array $args) use ($twig, $config) {

    $response->setContent(
        $twig->render(
            'board.html.twig', [ 'path' => $config['path'] ]
        )
    );

    return $response;
});

$router->get('/contestants', function (Request $request, Response $response, array $args) use ($twig, $config) {
    $response->setContent(
        $twig->render(
            'contestants.html.twig',
            [ 'players' => $config['players'], 'path' => $config['path'] ]
        )
    );

    return $response;
});

$router->get('/play/{player}', function (Request $request, Response $response, array $args) use ($twig, $config) {
	//    $player = urldecode(ucfirst(strtolower($args['player'])));
    $player = urldecode($args['player']);

    if (!in_array($player, $config['players'])) {
	// TODO: handle unknown players
        // return new \Symfony\Component\HttpFoundation\RedirectResponse('/');
    }
    $response->setContent($twig->render('play.html.twig', [ 'players' => $config['players'], 'user' => $player, 'path' => $config['path'] ]));
    return $response;
});

$router->addRoute('GET', '/admin', function (Request $request, Response $response) use ($twig, $config) {
    $response->setContent($twig->render('admin.html.twig', [ 'players' => $config['players'], 'path' => $config['path'] ]));
    return $response;
});

$dispatcher = $router->getDispatcher();
$request = Request::createFromGlobals();

$response = $dispatcher->dispatch($request->getMethod(), $request->getPathInfo());

$response->send();
