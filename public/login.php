<?php
require_once '/var/www/src/auth.php';
session_start();

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $u = authenticate_user($username, $password);
    if ($u) {
        session_regenerate_id(true);
        $_SESSION['user'] = ['id' => $u['id'], 'username' => $u['username']];
        header('Location: /');
        exit;
    } else {
        $msg = 'Identifiants incorrects.';
    }
}
?>
<!doctype html><html><head><meta charset="utf-8"><title>Connexion</title></head><body>
<h1>Connexion</h1>
<?php if($msg) echo '<p style="color:red">'.htmlspecialchars($msg).'</p>'; ?>
<form method="post">
  <input name="username" placeholder="Nom"><br>
  <input name="password" type="password" placeholder="Mot de passe"><br>
  <button>Se connecter</button>
</form>
</body></html>