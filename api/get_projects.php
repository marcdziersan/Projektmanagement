<?php
require_once 'api_header.php';
require_once '../logik/ProjectManager.php';

if (!Auth::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$pm = new ProjectManager();
echo json_encode($pm->getAllProjects());
