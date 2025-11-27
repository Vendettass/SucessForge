<?php
// public/register.php
require_once '/var/www/src/auth.php';
session_start();

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $steamid  = trim($_POST['steamid'] ?? '');

    if ($username === "" || $password === "") {
        $error = "Tous les champs obligatoires doivent être remplis.";
    } else {
        $user = register_user($username, $password, $steamid);
        if ($user === null) {
            $error = "Nom d’utilisateur déjà pris ou erreur interne.";
        } else {
            // stocke l'objet user en session (id, username, steamid, created_at...)
            $_SESSION['user'] = $user;
            header("Location: /index.php");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Créer un compte</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-900 text-slate-100 flex items-center justify-center min-h-screen">
<div class="bg-slate-800 p-6 rounded-xl w-full max-w-md border border-slate-700">
    <h1 class="text-2xl font-bold mb-4">Créer un compte</h1>

    <?php if ($error): ?>
        <p class="mb-3 p-3 bg-red-800/40 border border-red-600 rounded-lg"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <form method="POST" class="flex flex-col gap-4">
        <input type="text" name="username" placeholder="Nom d’utilisateur" class="px-3 py-2 bg-slate-700 rounded-lg" required>
        <input type="password" name="password" placeholder="Mot de passe" class="px-3 py-2 bg-slate-700 rounded-lg" required>
        <input type="text" name="steamid" placeholder="SteamID64 (optionnel)" class="px-3 py-2 bg-slate-700 rounded-lg">
        <button class="px-4 py-2 bg-indigo-600 rounded-lg font-bold">Créer mon compte</button>
    </form>

    <p class="mt-3 text-sm text-slate-400">Déjà un compte ? <a href="/login.php" class="text-indigo-400">Connexion</a></p>
</div>
</body>
</html>
