<?php

declare(strict_types=1);

use App\Auth\SiteAuth;

require __DIR__ . '/../config/bootstrap.php';

SiteAuth::logout();
header('Location: /index.php');
exit;
