<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

$app->get('/orders', function(Request $request, Response $response, $args) {
    $db = $this->get('db');
    $db->setRoute('/orders');

    $results = $db->queryAll("SELECT * FROM orders");
    $pagination = $db->pagination('orders');

    $renderer = $this->get('renderer');
    return $renderer->render($response, 'orders.php', [
        'title' => 'Past Orders',
        'args' => $args,
        'orders' => $results,
        'pagination' => $pagination,
        'site_info' => $this->get('site_info'),
        'low_stocks' => $request->getAttribute('low_stocks'),
        'menu' => $this->get('menu')->getMenu($_SESSION['role'])
    ]);
});