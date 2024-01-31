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

// Site info
$site_info = [
    "app_name" => 'INVENTORY',
    "url" => 'http://localhost'
];
$container->set('site_info', $site_info);
// Menu
$menu = new Menu($site_info);
$container->set('menu', $menu);
// Database
$db = new Db( 'localhost', 'username', 'password', 'pos' );
$container->set('db', $db);

// Create App
AppFactory::setContainer($container);
$app = AppFactory::create();

// Add Error Handling Middleware
$app->addErrorMiddleware(true, false, false);

// Session middleware
$app->add(function(Request $request, RequestHandler $handler) {
    $db = $this->get('db');

    session_start();

    // Check authentication
    if(!isset($_SESSION['userid']) && $request->getUri()->getPath() !== '/auth') {
        return redirect($request, '/auth');
    }elseif(isset($_SESSION['userid']) && $request->getUri()->getPath() === '/auth') {
        return redirect($request, '/');
    }

    $response = $handler->handle($request);

    // Check authorization
    if( isset($_SESSION['userid']) ) {
        if( !checkAuth($request->getUri()->getPath(), $_SESSION['role'], $this->get('menu')) ) {
            $response = $response
                ->withStatus(302)
                ->withHeader('ContentType', 'text/html')
                ->withBody(new \Slim\Psr7\Stream(fopen('php://temp', 'r+'))); // Empty response body
            
            $response->getBody()->write("Unauthorized");
        }
    }

    session_write_close(); // Save z close the session
    return $response;
});

$app->get('/auth', function(Request $request, Response $response, $args) {
    $renderer = new PhpRenderer('src', ['title' => 'POS']);
    $renderer->setLayout('login_layout.php');
    
    return $renderer->render($response, 'login.php', [
        'title' => 'Login',
        'auth' => true,
        'args' => $args,
        'site_info' => $this->get('site_info')
    ]);
});

$app->post('/auth', function(Request $request, Response $response, $args) {
    $db = $this->get('db');

    $post = $request->getParsedBody();

    $username = $db->escape($post['username']);
    $password = $db->escape($post['password']);

    $result = $db->query( "SELECT * FROM users WHERE username='$username' AND password=MD5('$password')" );
    if($result) {
        $_SESSION['userid'] = $result['ID'];
        $_SESSION['role'] = $result['role'];
        $_SESSION['expiry'] = $result['expiry'];

        $db->fetchUserData();

        $redirect = '/';
        if($result['role'] == 'manager') {
            $redirect = '/stocks';
        }

        return redirect($request, $redirect);
    } else {
        
        $db->log('/auth', 'Login Error', 'User ['.$username.'] attempting to login', $post);
        $renderer = $this->get('renderer');
        return $renderer->render($response, 'login.php', [
            'title' => 'Login Error',
            'auth' => true,
            'args' => $args,
            'site_info' => $this->get('site_info'),
            'error' => 'Invalid login'
        ]);
    }
    
});

$app->get('/logout', function(Request $request, Response $response, $args) {
    $db = $this->get('db');

    $db->clearUserData();
    session_destroy();
    return redirect($request, '/auth');
});

$app->get('/', function(Request $request, Response $response, $args) {
    $db = $this->get('db');

    $renderer = $this->get('renderer');
    return $renderer->render($response, 'pos.php', [
        'title' => 'POS',
        'args' => $args,
        'site_info' => $this->get('site_info'),
        'menu' => $this->get('menu')->getMenu($_SESSION['role'])
    ]);
});

$app->get('/stocks', function(Request $request, Response $response, $args) {
    global $db;
    $results = $db->queryAll("SELECT stocks.ID AS ID, barcode, product, price_buy, price_sell, count, last_stocked FROM products INNER JOIN stocks WHERE stocks.product_id = products.ID");
    $pagination = $db->pagination('products');

    $renderer = $this->get('renderer');
    return $renderer->render($response, 'stocks.php', [
        'title' => 'Stocks',
        'args' => $args,
        'stocks' => $results,
        'pagination' => $pagination,
        'site_info' => $this->get('site_info'),
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

    $results = $db->queryAll("SELECT * FROM products");
    $pagination = $db->pagination('products');

    $renderer = $this->get('renderer');
    return $renderer->render($response, 'products.php', [
        'title' => 'Products',
        'args' => $args,
        'products' => $results,
        'pagination' => $pagination,
        'site_info' => $this->get('site_info'),
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

    $results = $db->queryAll("SELECT * FROM users");
    $pagination = $db->pagination('users');

    $renderer = $this->get('renderer');
    return $renderer->render($response, 'users.php', [
        'title' => 'Users',
        'args' => $args,
        'users' => $results,
        'pagination' => $pagination,
        'site_info' => $this->get('site_info'),
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
        $sql = "INSERT INTO users (username, password, name, phone, role, expiry)
            VALUES ('$username', MD5('$password'), '$name', '$phone', '$role', '$expiry')";
        $result = $db->query($sql);

        $alert = $result > 0 ? 'User added successfully!' : 'Unable to add user > '.$db->error();
        $db->log('/users', 'Add New User ['.$result.']', $alert, $post);
        $response->getBody()->write('<script>alert("'.$alert.'"); document.location = "?";</script>');
    } else { // Update
        $password_update = !empty($password) ? ", password=MD5('$password')" : "";
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

$app->get('/reports', function(Request $request, Response $response, $args) {
    $db = $this->get('db');

    $renderer = $this->get('renderer');
    return $renderer->render($response, 'reports.php', [
        'title' => 'Reports',
        'args' => $args,
        'site_info' => $this->get('site_info'),
        'menu' => $this->get('menu')->getMenu($_SESSION['role'])
    ]);
});

$app->get('/logs', function(Request $request, Response $response, $args) {
    $db = $this->get('db');

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

function redirect($request, $path = '/') {
    $uri = $request->getUri()->withPath($path);
    $response = new \Slim\Psr7\Response();
    return $response->withHeader('Location', (string)$uri)->withStatus(302);
}

function checkAuth( $route, $role, $menu ) {
    $items = $menu->getMenu( $role );
    $routes = array_column( $items, 'route' );

    return in_array($route, $routes);
}

// Run app
$app->run();