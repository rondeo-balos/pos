<?php
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

// Load all functions
require __DIR__ . './middleware.php';
$dir = __DIR__ . './functions/';
$files = scandir($dir);

foreach( $files as $file ) {
    if( $file != '.' && $file != '..' ) {
        if( pathinfo( $dir . $file, PATHINFO_EXTENSION ) == 'php' ) {
            if( file_exists( $dir . $file ) ) {
                require $dir . $file;
            }
        }
    }
}

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