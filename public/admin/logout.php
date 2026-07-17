<?php

declare(strict_types=1);

use App\Auth\AdminAuth;

require __DIR__ . '/../../config/bootstrap.php';

AdminAuth::logout();
header('Location: /admin/login.php');
exit;
