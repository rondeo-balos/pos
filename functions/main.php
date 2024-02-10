<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\PhpRenderer;

$app->post('/delete/{table}', function(Request $request, Response $response, $args) {
    $db = $this->get('db');

    $post = $request->getParsedBody();
    $table = $db->escape($args['table']);
    $ID = $db->escape($post['ID']);
    $route = $post['route'];

    if(empty($db->getUser())) {
        $db->log('/delete/{'.$table.'}', 'Delete '.$table, 'Stopped: Attempting to delete '.$table.' ['.$ID.']', $table);
        $response->getBody()->write('<script>alert("Oops!"); document.location = "?";</script>');
    }

    $sql = "DELETE FROM $table WHERE ID=$ID";
    $result = $db->query($sql);
    
    $alert = $result > 0 ? 'Deleted successfully!' : 'Unable to delete data > '.$db->error();
    $db->log('/delete/{'.$table.'}', 'Delete '.$table.' ['.$ID.']', $alert, $table);
    $response->getBody()->write('<script>alert("'.$alert.'"); document.location = "'.$route.'";</script>');
    return $response;
});

$app->get('/scanner', function(Request $request, Response $response, $args) {
    $renderer = new PhpRenderer('src', ['title' => 'POS']);
    $renderer->setLayout('unauthorized.php');
    
    return $renderer->render($response, 'scanner.php', [
        'title' => 'Scanner',
        'args' => $args,
        'site_info' => $this->get('site_info')
    ]);
});

$app->post('/fetchCart', function(Request $request, Response $response, $args) {
    $db = $this->get('db');

    $sql = "SELECT * FROM temp INNER JOIN products WHERE temp.product_id = products.ID AND temp.session=''";
    $result = $db->queryAll($sql);

    $payload = json_encode($result);
    $response->getBody()->write($payload);
    return $response->withHeader('Content-Type', 'application/json');
});

$app->post('/fetch/{barcode}', function(Request $request, Response $response, $args) {
    $db = $this->get('db');
    
    $barcode = $db->escape($args['barcode']);

    switch($db->getRoute()['current']) {
        case '/':
            posFetch($db, $barcode, $response);
            break;
        case '/products':
            productFetch($db, $barcode, $response);
            break;
        case '/stocks':
            stockFetch($db, $barcode, $response);
            break;
    }

    return $response;
});

function posFetch($db, $barcode, &$response) {
    $sql = "SELECT * FROM products
        INNER JOIN stocks ON products.ID = stocks.product_id 
        WHERE products.barcode = '$barcode' AND stocks.count > 0";
    $result = $db->query($sql);
    if($result > 0) {
        $product_id = $result['product_id'];
        $sql = "INSERT INTO temp (session, product_id, quantity) 
            VALUES ('', $product_id, 1)
            ON DUPLICATE KEY UPDATE quantity = quantity + 1";
        $db->query($sql);

        $result = 1;
    } else {
        $result = 0;
    }

    $response->getBody()->write($result.'');
    return $response;
}

function productFetch($db, $barcode, &$response) {

    $payload = [
        'route' => '/products',
        'barcode' => $barcode
    ];

    $db->setAlert(serialize($payload));
    $payload = json_encode($payload);

    $response->getBody()->write($payload);
    $response = $response->withHeader('Content-Type', 'application/json');
    return $response;
}

function stockFetch($db, $barcode, &$response) {

    $payload = [
        'route' => '/stocks',
        'barcode' => $barcode
    ];

    $db->setAlert(serialize($payload));
    $payload = json_encode($payload);

    $response->getBody()->write($payload);
    $response = $response->withHeader('Content-Type', 'application/json');
    return $response;
}

$app->post('/getAlert', function(Request $request, Response $response, $args) {
    $db = $this->get('db');

    $result = unserialize($db->getAlert());

    $payload = json_encode($result);
    $response->getBody()->write($payload);
    return $response->withHeader('Content-Type', 'application/json');
});

$app->post('/verifyPurchase', function(Request $request, Response $response, $args) {
    $db = $this->get('db');

    $post = $request->getParsedBody();
    $cash = $db->escape($post['cash']);
    $customer_name = $db->escape($post['customer_name']);
    $customer_number = $db->escape($post['customer_number']);

    $payload = [
        'message' => 'INSUFFICIENT FUNDS',
        'change' => 0,
        'order' => 0
    ];

    $sql = "SELECT *,temp.ID AS tempID FROM temp INNER JOIN products WHERE temp.product_id = products.ID AND temp.session=''";
    $result = $db->queryAll($sql);
    
    $total = 0;
    $items = [];

    foreach($result as $item) {
        $items[] = $item['tempID'];
        $total += (floatval($item['price_sell']) * floatval($item['quantity']));
    }

    if($cash > $total) {
        $change = $cash - $total;

        $sql = "INSERT INTO orders (items, customer_name, customer_number, total, cash, changed, status)
            VALUES ('".serialize($items)."', '$customer_name', '$customer_number', $total, $cash, $change, 'created')";
        $order = $db->query($sql);
        if($order > 0) {
            $payload['message'] = 'CHANGE';
            $payload['change'] = $change;
            $payload['order'] = $order;

            foreach($items as $ID) {
                $sql = "SELECT * FROM temp INNER JOIN products WHERE temp.ID=$ID AND temp.product_id = products.ID";
                $row = $db->query($sql);

                $insert = "INSERT INTO cart (tempID, session, product_id, quantity, price_buy, price_sell, subtotal, date_added) 
                    VALUES ($ID, '".getSession($db)."',$row[product_id], $row[quantity], $row[price_buy], $row[price_sell], ".($row['price_sell']*$row['quantity']).", '$row[date_added]')";
                if($db->query($insert)) {
                    $update = "UPDATE stocks SET count = count - $row[quantity] WHERE product_id = $row[product_id]";
                    $db->query($update);
                    $delete = "DELETE FROM temp WHERE ID=$ID";
                    $db->query($delete);
                } else {
                    $db->log('/verifyPurchase', 'Unable to move cart items', $db->error(), $post);
                    $payload['message'] = 'UNABLE TO OPERATE';
                    break;
                }
            }
        } else {
            $db->log('/verifyPurchase', 'Unable to save order', $db->error(), $post);
            $payload['message'] = 'UNABLE TO SAVE ORDER';
        }
    }

    $payload = json_encode($payload);
    $response->getBody()->write($payload);
    return $response->withHeader('Content-Type', 'application/json');
});

$app->get('/print/{order}', function(Request $request, Response $response, $args) {
    $db = $this->get('db');
    $site_info = $this->get('site_info');
    $order = $db->escape($args['order']);
    $cart = [];
    $cashier = $db->getUser()['name'];

    $sql = "SELECT * FROM orders WHERE ID = $order";
    $result = $db->query($sql);
    if($result > 0) {
        $items = unserialize($result['items']);
        foreach($items as $item) {
            $sql = "SELECT * FROM cart INNER JOIN products WHERE cart.tempID = $item AND products.ID = cart.product_id";
            $cart[] = $db->query($sql);
        }
    }

    $renderer = new PhpRenderer('src', ['title' => 'POS']);
    $renderer->setLayout('unauthorized.php');
    return $renderer->render($response, 'receipt.php', [
        'title' => 'Receipt',
        'args' => $args,
        'site_info' => $site_info,
        'menu' => $this->get('menu')->getMenu($_SESSION['role']),
        'order_id' => $order,
        'orders' => $result,
        'cart' => $cart,
        'cashier' => $cashier
    ]);
});