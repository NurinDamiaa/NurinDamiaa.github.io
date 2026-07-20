<?php

require_once __DIR__ . '/app/bootstrap.php';

if (!empty($_SESSION['user_id'])) {
    redirect(is_admin() ? url('modules/admin/dashboard.php') : url('dashboard.php'));
}

redirect(url('login.php'));
