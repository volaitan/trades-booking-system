<?php
declare(strict_types=1);

use App\App;
use App\Controllers\AdminController;
use App\Controllers\PublicController;
use App\Router;
use App\View;

// Let PHP's built-in dev server serve static files directly.
if (PHP_SAPI === 'cli-server' && is_file(__DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH))) {
    return false;
}

$config = require dirname(__DIR__) . '/bootstrap.php';

// Hardened session cookie
session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
    'secure'   => ($_SERVER['HTTPS'] ?? '') === 'on',
]);
session_start();

// Basic security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');
header("Content-Security-Policy: default-src 'self'; style-src 'self'; script-src 'self'; img-src 'self' data:");

$app = App::fromConfig($config);
$view = new View(dirname(__DIR__) . '/templates', ['business' => $config['business']]);
$public = new PublicController($app, $view);
$admin = new AdminController($app, $view);

$router = new Router();
$router->get('/', [$public, 'bookingForm']);
$router->get('/slots', [$public, 'slots']);
$router->post('/book', [$public, 'submitBooking']);
$router->get('/booked', [$public, 'booked']);

$router->get('/admin/login', [$admin, 'loginForm']);
$router->post('/admin/login', [$admin, 'login']);
$router->post('/admin/logout', [$admin, 'logout']);
$router->get('/admin', [$admin, 'dashboard']);
$router->get('/admin/bookings', [$admin, 'bookings']);
$router->post('/admin/bookings/{id}/status', [$admin, 'bookingStatus']);
$router->get('/admin/quotes', [$admin, 'quotes']);
$router->get('/admin/quotes/new', [$admin, 'newQuote']);
$router->post('/admin/quotes', [$admin, 'createQuote']);
$router->get('/admin/quotes/{id}', [$admin, 'showQuote']);
$router->post('/admin/quotes/{id}/status', [$admin, 'quoteStatus']);
$router->post('/admin/quotes/{id}/invoice', [$admin, 'invoiceQuote']);
$router->get('/admin/invoices', [$admin, 'invoices']);
$router->get('/admin/invoices/{id}', [$admin, 'showInvoice']);
$router->post('/admin/invoices/{id}/pay', [$admin, 'payInvoice']);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri = $_SERVER['REQUEST_URI'] ?? '/';
$match = $router->match($method, $uri);

if ($match === null) {
    http_response_code($router->allowsOtherMethod($method, $uri) ? 405 : 404);
    echo $view->render('public/not_found', ['title' => 'Not found']);
    return;
}

[$handler, $params] = $match;
echo $handler(...array_values($params));
