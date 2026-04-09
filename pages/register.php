<?php
// pages/register.php — Redirect to combined auth page
require_once __DIR__ . '/../config/db.php';

$type = ($_GET['type'] ?? '') === 'expert' ? '&type=expert' : '';
header('Location: ' . APP_URL . '/pages/auth.php?tab=signup' . $type);
exit;
