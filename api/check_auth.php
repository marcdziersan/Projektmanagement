<?php
require_once 'api_header.php';

if (Auth::isLoggedIn()) {
    echo json_encode([
        'logged_in' => true,
        'user_id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'role' => $_SESSION['role']
    ]);
} else {
    echo json_encode(['logged_in' => false]);
}
