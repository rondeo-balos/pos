<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

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