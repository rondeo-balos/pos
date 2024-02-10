<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

$app->get('/', function(Request $request, Response $response, $args) {
    $db = $this->get('db');
    $db->setRoute('/');

    $renderer = $this->get('renderer');
    return $renderer->render($response, 'pos.php', [
        'title' => 'POS',
        'args' => $args,
        'site_info' => $this->get('site_info'),
        'low_stocks' => $request->getAttribute('low_stocks'),
        'menu' => $this->get('menu')->getMenu($_SESSION['role'])
    ]);
});