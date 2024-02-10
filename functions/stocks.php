<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

$app->get('/stocks', function(Request $request, Response $response, $args) {
    $db = $this->get('db');
    $db->setRoute('/stocks');

    $order = $_GET['order'] ?? '';
    $sorting_options = [
        'st_low_high' => 'ORDER BY count ASC',
        'st_high_low' => 'ORDER BY count DESC',
        'na_a_z' => 'ORDER BY product ASC',
        'na_z_a' => 'ORDER BY product DESC'
    ];
    $order_clause = $sorting_options[$order] ?? $sorting_options['st_low_high'];

    $results = $db->queryAll("SELECT stocks.ID AS ID, barcode, product, price_buy, price_sell, count, last_stocked FROM products INNER JOIN stocks WHERE stocks.product_id = products.ID $order_clause");
    $pagination = $db->pagination('products');

    $renderer = $this->get('renderer');
    return $renderer->render($response, 'stocks.php', [
        'title' => 'Stocks',
        'args' => $args,
        'stocks' => $results,
        'pagination' => $pagination,
        'site_info' => $this->get('site_info'),
        'low_stocks' => $request->getAttribute('low_stocks'),
        'generator' => $this->get('generator'),
        'menu' => $this->get('menu')->getMenu($_SESSION['role'])
    ]);
});

$app->post('/stocks', function(Request $request, Response $response, $args) {
    $db = $this->get('db');

    $post = $request->getParsedBody();

    $ID = $db->escape($post['ID']);
    $quantity = $db->escape($post['quantity']);
    if($ID == '-1') {
        $response->getBody()->write('<script>alert("Unable to restock product > '.$db->error().'"); document.location = "?";</script>');
        $db->log('/stocks', 'Error Product ['.$ID.'] Restock', $db->error(), $post);
        return $response;
    }
    $sql = "UPDATE stocks SET count = (count + $quantity), last_stocked = CURRENT_TIMESTAMP WHERE ID=$ID";
    $result = $db->query($sql);

    $alert = $result > 0 ? 'Product restocked successfully!' : 'Unable to restock product > '.$db->error();
    $db->log('/stocks', 'Restocked ['.$ID.']', $alert, $post);
    $response->getBody()->write('<script>alert("'.$alert.'"); document.location = "?";</script>');
    return $response;
});