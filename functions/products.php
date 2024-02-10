<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

$app->get('/products', function(Request $request, Response $response, $args) {
    $db = $this->get('db');
    $db->setRoute('/products');

    $order = $_GET['order'] ?? '';
    $sorting_options = [
        'st_low_high' => 'ORDER BY count ASC',
        'st_high_low' => 'ORDER BY count DESC',
        'na_a_z' => 'ORDER BY product ASC',
        'na_z_a' => 'ORDER BY product DESC'
    ];
    $order_clause = $sorting_options[$order] ?? $sorting_options['na_a_z'];

    $results = $db->queryAll("SELECT * FROM products $order_clause");
    $pagination = $db->pagination('products');

    $renderer = $this->get('renderer');
    return $renderer->render($response, 'products.php', [
        'title' => 'Products',
        'args' => $args,
        'products' => $results,
        'pagination' => $pagination,
        'site_info' => $this->get('site_info'),
        'low_stocks' => $request->getAttribute('low_stocks'),
        'generator' => $this->get('generator'),
        'menu' => $this->get('menu')->getMenu($_SESSION['role'])
    ]);
});

$app->post('/products', function(Request $request, Response $response, $args) {
    $db = $this->get('db');

    $post = $request->getParsedBody();
    $ID = $db->escape($post['ID']);
    $barcode = $db->escape($post['barcode']);
    $product = $db->escape($post['product']);
    $price_buy = $db->escape($post['price_buy']);
    $price_sell = $db->escape($post['price_sell']);

    if($ID == '-1') {
        $sql = "INSERT INTO products (barcode, product, price_buy, price_sell) 
            VALUES ('$barcode', '$product', '$price_buy', '$price_sell')";
        $result = $db->query($sql);

        if($result > 0) {
            $ID = $result;
            $sql = "INSERT INTO stocks (product_id) VALUES ($ID)";
            $result = $db->query($sql);
            $db->log('/products', 'Inserting new Stock from ['.$ID.']', '', $post);
            if($result<=0) {
                $sql = "DELETE FROM products WHERE ID=$ID";
                $db->log('/products', 'Error Inserting - Deleting Product ['.$ID.']', $db->error(), $post);
                $db->query($sql);
            }
        }

        $alert = $result > 0 ? 'Product added successfully!' : 'Unable to add product > '.$db->error();
        $db->log('/products', 'Add New Product ['.$ID.']', $alert, $post);
        $response->getBody()->write('<script>alert("'.$alert.'"); document.location = "?";</script>');
    } else {
        $sql = "UPDATE products
            SET barcode='$barcode', product='$product', price_buy='$price_buy', price_sell='$price_sell'
            WHERE ID=$ID";
        $result = $db->query($sql);

        $alert = $result > 0 ? 'Product updated successfully!' : 'Unable to update product > '.$db->error();
        $db->log('/products', 'Update Product ['.$ID.']', $alert, $post);
        $response->getBody()->write('<script>alert("'.$alert.'"); document.location = "?";</script>');
    }
    return $response;
});