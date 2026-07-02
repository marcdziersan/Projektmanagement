<?php
require_once __DIR__ . '/api_header.php';

$user = Auth::getCurrentUser();
apiJson([
    'logged_in' => $user !== null,
    'user' => $user,
]);
