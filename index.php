<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Factory\AppFactory;
use Slim\Views\PhpRenderer;
use DI\ContainerBuilder;

require __DIR__ . './vendor/autoload.php';
require __DIR__ . './database.php';
require __DIR__ . './menu.php';

date_default_timezone_set('Asia/Manila');

// Define a custom renderer using the custom view template
$renderer = new PhpRenderer('src', ['title' => 'POS']);
$renderer->setLayout('layout.php');

$containerBuilder = new ContainerBuilder();
$container = $containerBuilder->build();
$container->set('renderer', $renderer );

// Database
$db = new Db( 'localhost', 'username', 'password', 'pos' );
$container->set('db', $db);
// Site info
$site_info = $db->getOptions();
$container->set('site_info', $site_info);
// Menu
$menu = new Menu($site_info);
$container->set('menu', $menu);

// Barcode generator
$generator = new Picqer\Barcode\BarcodeGeneratorHTML();
$container->set('generator', $generator);

// Create App
AppFactory::setContainer($container);
$app = AppFactory::create();

// Add Error Handling Middleware
$app->addErrorMiddleware(true, false, false);

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

$app->get('/auth', function(Request $request, Response $response, $args) {
    $renderer = new PhpRenderer('src', ['title' => 'POS']);
    $renderer->setLayout('unauthorized.php');
    
    return $renderer->render($response, 'login.php', [
        'title' => 'Login',
        'args' => $args,
        'site_info' => $this->get('site_info')
    ]);
});

$app->post('/auth', function(Request $request, Response $response, $args) {
    $renderer = new PhpRenderer('src', ['title' => 'POS']);
    $renderer->setLayout('unauthorized.php');

    $db = $this->get('db');

    $post = $request->getParsedBody();

    $username = $db->escape($post['username']);
    $password = $db->escape($post['password']);

    $result = $db->query( "SELECT * FROM users WHERE username='$username'" );
    if($result) {

        $password = $result['password'];
        if(password_verify($post['password'], $password)) {
            $_SESSION['userid'] = $result['ID'];
            $_SESSION['role'] = $result['role'];
            $_SESSION['expiry'] = $result['expiry'];

            $db->fetchUserData();
            
            $redirects = $this->get('menu')->redirects;
            $redirect = $redirects['cashier'];
            if(isset($redirects[$_SESSION['role']])) {
                $redirect = $redirects[$_SESSION['role']];
            }

            return redirect($request, $redirect);
        } else {
            $error = "Invalid credentials";
        }
    } else {
        $error = "User not found";
    }
    
    $db->log('/auth', $error, 'User ['.$username.'] attempting to login', $post);
    return $renderer->render($response, 'login.php', [
        'title' => 'Login Error',
        'args' => $args,
        'site_info' => $this->get('site_info'),
        'error' => $error
    ]);
});

$app->get('/logout', function(Request $request, Response $response, $args) {
    $db = $this->get('db');
    $db->setRoute('/logout');

    $db->clearUserData();
    session_destroy();
    return redirect($request, '/auth');
});

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

$app->get('/users', function(Request $request, Response $response, $args) {
    $db = $this->get('db');
    $db->setRoute('/users');

    $results = $db->queryAll("SELECT * FROM users");
    $pagination = $db->pagination('users');

    $renderer = $this->get('renderer');
    return $renderer->render($response, 'users.php', [
        'title' => 'Users',
        'args' => $args,
        'users' => $results,
        'pagination' => $pagination,
        'site_info' => $this->get('site_info'),
        'low_stocks' => $request->getAttribute('low_stocks'),
        'menu' => $this->get('menu')->getMenu($_SESSION['role'])
    ]);
});

$app->post('/users', function(Request $request, Response $response, $args) {
    $db = $this->get('db');

    $post = $request->getParsedBody();
    $ID = $db->escape($post['ID']);
    $username = $db->escape($post['username']);
    $password = $db->escape($post['password']);
    $name = $db->escape($post['name']);
    $phone = $db->escape($post['phone']);
    $role = $db->escape($post['role']);
    $expiry = base64_encode(strtotime(date('Y-m-d h:i:s'). ' + 10 days'));

    if($ID == '-1') { // Insert
        $password = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (username, password, name, phone, role, expiry)
            VALUES ('$username', '$password', '$name', '$phone', '$role', '$expiry')";
        $result = $db->query($sql);

        $alert = $result > 0 ? 'User added successfully!' : 'Unable to add user > '.$db->error();
        $db->log('/users', 'Add New User ['.$result.']', $alert, $post);
        $response->getBody()->write('<script>alert("'.$alert.'"); document.location = "?";</script>');
    } else { // Update
        $password_update = "";
        if(!empty($password)){
            $password = password_hash($password, PASSWORD_DEFAULT);
            $password_update = ", password='$password'";
        }
        $sql = "UPDATE users
            SET username='$username', name='$name', phone='$phone', role='$role', expiry='$expiry'$password_update
            WHERE ID=$ID";
        $result = $db->query($sql);

        $alert = $result > 0 ? 'User updated successfully!' : 'Unable to update user > '.$db->error();
        $db->log('/users', 'Update User ['.$ID.']', $alert, $post);
        $redirect = "?";
        if($ID == $db->getUser()['ID'] && !empty($password)) {
            $redirect = $this->get('site_info')['url']."/logout";
        }
        $response->getBody()->write('<script>alert("'.$alert.'"); document.location = "'.$redirect.'";</script>');
    }
    return $response;
});

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
        'menu' => $this->get('menu')->getMenu($_SESSION['role'])
    ]);
});

$app->get('/settings', function(Request $request, Response $response, $args) {
    $db = $this->get('db');
    $db->setRoute('/settings');

    $results = $db->queryAll("SELECT * FROM options");

    $renderer = $this->get('renderer');
    return $renderer->render($response, 'settings.php', [
        'title' => 'Settings',
        'args' => $args,
        'options' => $results,
        'site_info' => $this->get('site_info'),
        'low_stocks' => $request->getAttribute('low_stocks'),
        'menu' => $this->get('menu')->getMenu($_SESSION['role'])
    ]);
});

$app->post('/settings', function(Request $request, Response $response, $args) {
    $db = $this->get('db');
    $post = $request->getParsedBody();

    $result = false;
    foreach($post as $name => $value) {
        $name = $db->escape($name);
        $value = $db->escape($value);

        $sql = "UPDATE options SET value='$value' WHERE name='$name'";
        $result = $db->query($sql);
    }
    $alert = 'Settings updated! ' . $db->error();
    $db->log('/settings', 'Update Settings', $alert, $post);
    $redirect = "?";
    $response->getBody()->write('<script>alert("'.$alert.'"); document.location = "'.$redirect.'";</script>');
    return $response;
});

$app->get('/analytics', function(Request $request, Response $response, $args) {
    $db = $this->get('db');
    $db->setRoute('/analytics');

    $today_sales = $db->query("SELECT SUM(total) AS today_sales FROM orders WHERE DAY(date_ordered) = DAY(CURRENT_DATE)");
    $this_month_sales = $db->query("SELECT SUM(total) AS this_month_sales FROM orders WHERE YEAR(date_ordered) = YEAR(CURRENT_DATE) AND MONTH(date_ordered) = MONTH(CURRENT_DATE)");
    
    $monthly_sales = [];
    $results = $db->queryAll("SELECT MONTH(date_ordered) AS month, SUM(total) AS monthly_sales FROM  orders WHERE  YEAR(date_ordered) = YEAR(CURRENT_DATE) GROUP BY  MONTH(date_ordered)");
    foreach($results as $row) {
        $monthly_sales[] = $row;
    }

    $renderer = $this->get('renderer');
    return $renderer->render($response, 'analytics.php', [
        'title' => 'Analytics',
        'args' => $args,
        'today_sales' => $today_sales['today_sales'],
        'this_month_sales' => $this_month_sales['this_month_sales'],
        'monthly_sales' => $monthly_sales,
        'site_info' => $this->get('site_info'),
        'low_stocks' => $request->getAttribute('low_stocks'),
        'menu' => $this->get('menu')->getMenu($_SESSION['role'])
    ]);
});

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

    $result = unserialize($db->getAlert()['scan_alert']);

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

function getSession( $db ) {
    $date_ordered = date('Y-m-d h:i:s');

    $user = $db->getUser();
    $session = [
        'user' => $user,
        'date_ordered' => $date_ordered
    ];
    $session = serialize($session);

    return $session;
}

function redirect($request, $path = '/') {
    $uri = $request->getUri()->withPath($path);
    $response = new \Slim\Psr7\Response();
    return $response->withHeader('Location', (string)$uri)->withStatus(302);
}

function checkAuth( $route, $role, $menu ) {
    // Define public routes that don't require authorization
    $publicRoutes = ['/auth', '/logout', '/unauthorized', '/fetch/*', '/print/*'];

    // If the route is public, no need for authorization check
    //if (in_array($route, $publicRoutes)) {
    //    return true;
    //}
    // If the route is public or matches a wildcard route, no need for authorization check
    $isPublic = array_reduce($publicRoutes, function ($carry, $publicRoute) use ($route) {
        return $carry || ($publicRoute === $route || (strpos($publicRoute, '/*') !== false && strpos($route, rtrim($publicRoute, '/*')) === 0));
    }, false);

    if ($isPublic) {
        return true;
    }

    $items = $menu->getMenu( $role );
    $routes = array_column( $items, 'route' );

    return in_array($route, $routes);
}

// Run app
$app->run();