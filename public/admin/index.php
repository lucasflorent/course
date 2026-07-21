<?php

declare(strict_types=1);

use App\Auth\AdminAuth;

require __DIR__ . '/../../config/bootstrap.php';

AdminAuth::requireLogin();

header('Location: /admin/classes/index.php');
exit;
