<?php

declare(strict_types=1);

use App\Core\App;

require_once __DIR__.'/../src/Core/bootstrap.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Workspace-Id');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$app = new App();
$app->run();
