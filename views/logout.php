<?php
/**
 * ADHD Dashboard - Logout Handler
 */

session_start();
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/config.php';

// Logout user
Auth::logout();

// Redirect to login
header('Location: ' . Config::redirectUrl('/views/login.php'));
exit;
