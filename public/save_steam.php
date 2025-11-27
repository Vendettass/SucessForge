<?php
require_once '/var/www/src/auth.php';
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: /login.php'); exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $steamid = trim($_POST['steamid'] ?? '');
    $uid = (int)$_SESSION['user']['id'];
    if ($steamid !== '') {
        update_user_steamid($uid, $steamid);
    }
}
header('Location: /?appid=' . urlencode($_GET['appid'] ?? ''));
exit;