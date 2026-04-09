<?php
// pages/login.php — Redirect to combined auth page
require_once __DIR__ . '/../config/db.php';

header('Location: ' . APP_URL . '/pages/auth.php?tab=login');
exit;
