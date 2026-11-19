<?php

declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
if (isset($_GET['page'])) {
    redirect(url('admin/') . '?' . http_build_query($_GET));
}
require __DIR__ . '/includes/site.php';
require __DIR__ . '/pages/home.php';
