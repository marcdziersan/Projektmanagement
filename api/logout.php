<?php
require_once 'api_header.php';
Auth::logout();
echo json_encode(['success' => true]);
