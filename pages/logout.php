<?php
// pages/logout.php — Logout
require_once __DIR__ . '/../config/auth.php';

logoutUser();
header('Location: ' . APP_URL . '/pages/index.php');
exit;
