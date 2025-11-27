<?php
require_once '/var/www/src/auth.php';
session_start();

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $user = register_user($username, $password);

    if ($user) {
        // Auto-login
        $_SESSION['user'] = [
            'id'       => $user['id'],
            'username' => $user['username'],
            'steamid'  => $user['steamid'],
        ];
        header('Location: /');
        exit;
    } else {
        $msg = 'Nom déjà pris ou données invalides.';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Créer un compte - SuccesForge</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-900 text-slate-100 min-h-screen flex items-center justify-center">
<div class="w-full max-w-md bg-slate-800 border border-slate-700 rounded-2xl p-6 shadow-xl">
    <h1 class="text-2xl font-bold mb-4 text-center">Créer un compte</h1>

    <?php if ($msg): ?>
        <p class="mb-3 text-sm text-red-400"><?php echo htmlspecialchars($msg); ?></p>
    <?php endif; ?>

    <form method="post" class="space-y-3">
        <div>
            <label class="block text-sm mb-1">Nom d'utilisateur</label>
            <input name="username" class="w-full px-3 py-2 rounded-lg bg-slate-900 border border-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>
        <div>
            <label class="block text-sm mb-1">Mot de passe</label>
            <input type="password" name="password" class="w-full px-3 py-2 rounded-lg bg-slate-900 border border-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>
        <button class="w-full mt-2 px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-500 font-semibold">
            S'inscrire
        </button>
    </form>

    <p class="mt-4 text-xs text-slate-400 text-center">
        Déjà un compte ?
        <a href="/login.php" class="text-indigo-400 underline">Se connecter</a>
    </p>
</div>
</body>
</html>