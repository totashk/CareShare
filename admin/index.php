<?php

declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
$page = $_GET['page'] ?? (logged_in() ? 'dashboard' : 'login');
$routes = ['login','logout','dashboard','equipment','equipment-form','equipment-view','equipment-offers','borrowers','borrower-form','borrower-view','requests','request-form','request-view','issue','loans','return','overdue','reports','export','users','user-form','messages','profile','not-found','denied'];
if (!in_array($page, $routes, true)) $page = 'not-found';
if ($page === 'login' && logged_in()) redirect('?page=dashboard');
if ($page !== 'login' && $page !== 'not-found' && !logged_in()) redirect('?page=login');
if (in_array($page, ['users','user-form'], true)) require_role('Administrator');
if ($page === 'logout') {

    unset($_SESSION['user']);
    session_regenerate_id(true);
    redirect('?page=login');
}
require __DIR__ . '/../app/' . $page . '.php';
