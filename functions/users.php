<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

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