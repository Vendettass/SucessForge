<?php
require_once '/var/www/src/auth.php';
session_start();

if (!isset($_SESSION['user'])) {
    header('Location: /login.php');
    exit;
}

$steamid = trim($_POST['steamid'] ?? '');

if ($steamid !== '') {
    $uid = (int) $_SESSION['user']['id'];

    if (update_user_steamid($uid, $steamid)) {
        // mettre à jour en session aussi
        $_SESSION['user']['steamid'] = $steamid;
    }
}

// On revient à la page d'accueil
header('Location: /');
exit;