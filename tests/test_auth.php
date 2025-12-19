<?php
require_once __DIR__ . '/../logik/Auth.php';
require_once __DIR__ . '/../logik/Database.php';

// Mock session start for CLI
if (session_status() == PHP_SESSION_NONE) {
    // We can't really start a session in CLI easily without warnings, 
    // but Auth.php checks session_status.
    // We will suppress warnings or just let it be.
    @session_start();
}

function assertTest($name, $result, $expected) {
    echo "Test: $name ... ";
    if ($result === $expected) {
        echo "\033[32mPASSED\033[0m\n";
    } else {
        echo "\033[31mFAILED\033[0m (Expected: " . json_encode($expected) . ", Got: " . json_encode($result) . ")\n";
    }
}

echo "--- Starting Auth Tests ---\n";

// Test 1: Non-existent user
echo "\n[Scenario 1] Login with non-existent user\n";
$loginNonExist = Auth::login('gibtsnicht', 'passwort');
assertTest("Login Non-Existent", $loginNonExist, false);

// Test 2: Wrong password
echo "\n[Scenario 2] Login with User 'admin' and WRONG password\n";
$loginWrong = Auth::login('admin', 'falschesPW');
assertTest("Login Wrong Pass", $loginWrong, false);

// Test 3: Correct Credentials
echo "\n[Scenario 3] Login with User 'admin' and CORRECT password\n";
$loginSuccess = Auth::login('admin', 'admin123');
assertTest("Login Success", $loginSuccess, true);

// Verify Session
if ($loginSuccess) {
    assertTest("Session User ID Set", isset($_SESSION['user_id']), true);
    assertTest("Session Username is admin", $_SESSION['username'], 'admin');
}

echo "\n--- Tests Completed ---\n";
