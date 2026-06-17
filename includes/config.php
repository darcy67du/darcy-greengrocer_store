<?php

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'GreenGrocer');

define('SITE_URL', 'http://localhost/greengrocer');

define('SITE_NAME', 'Darcy GreenGrocer');

define('CURRENCY_SYMBOL', 'RWF ');
define('CURRENCY_DECIMALS', 0);



// Session config
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Connect to DB
function getDB()
{
    static $conn = null;
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
        $conn->set_charset("utf8mb4");
    }
    return $conn;
}

// Sanitize input
function sanitize($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Check if logged in
function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

// Check if admin
function isAdmin()
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Redirect helper
function redirect($url)
{
    header("Location: $url");
    exit();
}

// Flash messages
function setFlash($type, $message)
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash()
{
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}
