<?php
require_once __DIR__ . '/api_header.php';
require_once __DIR__ . '/../logik/ProjectManager.php';

requireLogin();

$includeDeleted = isset($_GET['include_deleted']) && $_GET['include_deleted'] === '1';
$pm = new ProjectManager();
apiJson($pm->getAllProjects($includeDeleted));
