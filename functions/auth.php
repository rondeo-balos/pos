<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\PhpRenderer;

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
            $_SESSION['user'] = $result['name'];
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