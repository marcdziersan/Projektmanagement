<?php
require_once __DIR__ . '/api_header.php';
Auth::logout();
apiJson(['success' => true]);
