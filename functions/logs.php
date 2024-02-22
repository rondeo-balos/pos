<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

$app->get('/logs', function(Request $request, Response $response, $args) {
    $db = $this->get('db');
    $db->setRoute('/logs');

    $results = $db->queryAll("SELECT * FROM logs ORDER BY date_logged DESC");
    $pagination = $db->pagination('logs');

    $renderer = $this->get('renderer');
    return $renderer->render($response, 'logs.php', [
        'title' => 'Logs',
        'args' => $args,
        'logs' => $results,
        'pagination' => $pagination,
        'site_info' => $this->get('site_info'),
        'low_stocks' => $request->getAttribute('low_stocks'),
        'menu' => $this->get('menu')->getMenu($_SESSION['role'])
    ]);
});