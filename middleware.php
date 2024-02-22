<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Views\PhpRenderer;

// Session middleware
$app->add(function(Request $request, RequestHandler $handler) {
    $db = $this->get('db');
    $site_info = $this->get('site_info');

    session_start();

    // Check authentication
    if(!isset($_SESSION['userid']) && $request->getUri()->getPath() !== '/auth') {
        return redirect($request, '/auth');
    }elseif(isset($_SESSION['userid']) && $request->getUri()->getPath() === '/auth') {
        return redirect($request, '/');
    }

    // Check authorization
    if( isset($_SESSION['userid']) ) {
        if( !checkAuth($request->getUri()->getPath(), $_SESSION['role'], $this->get('menu')) ) {
            $response = new \Slim\Psr7\Response();
            $response = $response->withStatus(401)
                ->withHeader('ContentType', 'text/html')
                ->withBody(new \Slim\Psr7\Stream(fopen('php://temp', 'r+'))); // Empty response body

            $renderer = new PhpRenderer('src', ['title' => 'POS']);
            $renderer->setLayout('unauthorized.php');
            $renderer->render($response, '401.php', [
                'title' => 'Unauthorized'
            ]);

            session_write_close(); // Save and close the session
            return $response;
        } else {
            $low_stocks = checkStock($db, $site_info['low_stock']);
            $request = $request->withAttribute('low_stocks', $low_stocks);
        }
    }

    // Continue with the middleware stack only if the authorization check passes
    $response = $handler->handle($request);

    session_write_close(); // Save and close the session
    return $response;
});

function checkStock($db, $low_stock) {
    $sql = "SELECT product, count FROM stocks INNER JOIN products WHERE stocks.product_id = products.ID AND stocks.count < $low_stock";
    $results = $db->queryAll($sql);

    $low_stocks = [];
    foreach($results as $product) {
        $low_stocks[] = [
            'product' => $product['product'],
            'message' => 'Low Stock! '.$product['count'].' Left.'
        ];
    }

    return $low_stocks;
}