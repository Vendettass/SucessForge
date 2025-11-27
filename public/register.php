<?php
require_once '/var/www/src/auth.php';
session_start();

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $user = register_user($username, $password);
    if ($user) {
        // auto-login
        $_SESSION['user'] = ['id' => $user['id'], 'username' => $user['username']];
        header('Location: /');
        exit;
    } else {
        $msg = 'Nom déjà pris ou données invalides.';
    }
}
?>
<!doctype html><html><head><meta charset="utf-8"><title>Inscription</title></head><body>
<h1>Créer un compte</h1>
<?php if($msg) echo '<p style="color:red">'.htmlspecialchars($msg).'</p>'; ?>
<form method="post">
  <input name="username" placeholder="Nom"><br>
  <input name="password" type="password" placeholder="Mot de passe"><br>
  <button>S'inscrire</button>
</form>
</body></html>