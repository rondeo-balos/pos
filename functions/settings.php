<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

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