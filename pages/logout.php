<?php
require_once __DIR__ . '/../includes/site.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    unset($_SESSION['borrower']);
    session_regenerate_id(true);
    flash('You have been logged out.');
}
redirect(url('index.php'));
