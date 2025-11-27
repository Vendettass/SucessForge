<?php
require_once '/var/www/src/config.php';
require_once '/var/www/src/steam_api.php';

$appid        = null;
$appDetails   = null;
$achievements = [];
$error        = null;

$steamInput = $_GET['steam'] ?? '';  // ce que l'utilisateur tape
$steamId    = null;                  // SteamID64 résolue
$forPlayer  = false;

if (!empty($_GET['appid'])) {
    $appid = (int) $_GET['appid'];

    if ($appid > 0) {
        // Infos du jeu
        $appDetails = getSteamAppDetails($appid);
        if (!$appDetails) {
            $error = "Impossible de récupérer les informations du jeu. Vérifie l'AppID.";
        } else {
            // Si un joueur est indiqué, on passe en mode 'profil'
            if (!empty($steamInput)) {
                $steamId = resolveSteamId($steamInput);
                if (!$steamId) {
                    $error = "Impossible de résoudre ce profil Steam (SteamID64 ou URL perso).";
                } else {
                    $forPlayer    = true;
                    $achievements = getPlayerAchievementsDetailed($steamId, $appid);
                    if (empty($achievements)) {
                        $error = "Aucun succès trouvé pour ce joueur sur ce jeu.";
                    }
                }
            } else {
                // Mode global (comme avant)
                $achievements = getSteamAchievementsWithPercent($appid);
                if (empty($achievements)) {
                    $error = "Aucun succès disponible ou impossible de récupérer les succès pour ce jeu.";
                }
            }
        }
    } else {
        $error = "AppID invalide.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>SuccesForge</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-900 text-slate-100 min-h-screen">
<header class="border-b border-slate-800 bg-slate-950/80 backdrop-blur sticky top-0">
    <div class="max-w-5xl mx-auto px-4 py-4 flex items-center justify-between">
        <h1 class="text-2xl font-bold tracking-tight">
            <span class="text-indigo-400">Succes</span>Forge
        </h1>
        <p class="text-sm text-slate-400">Explore les succès Steam</p>
    </div>
</header>

<main class="max-w-5xl mx-auto px-4 py-8 space-y-6">
    <!-- FORMULAIRE -->
    <section>
        <h2 class="text-xl font-semibold mb-4">Rechercher un jeu par AppID Steam</h2>

        <form method="GET" class="flex flex-col gap-3">
            <div class="flex flex-col sm:flex-row gap-3 items-stretch sm:items-center">
                <input
                    type="text"
                    name="appid"
                    placeholder="Ex : 730 pour CS2, 570 pour Dota 2"
                    class="flex-1 px-3 py-2 rounded-lg bg-slate-800 border border-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    value="<?php echo $appid ? htmlspecialchars($appid) : ''; ?>"
                    required
                >
                <button
                    type="submit"
                    class="px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-500 font-semibold"
                >
                    Charger les succès
                </button>
            </div>

            <div class="flex flex-col sm:flex-row gap-3">
                <input
                    type="text"
                    name="steam"
                    placeholder="SteamID64 ou URL personnalisée du profil (optionnel)"
                    class="flex-1 px-3 py-2 rounded-lg bg-slate-800 border border-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    value="<?php echo htmlspecialchars($steamInput); ?>"
                >
                <p class="text-xs text-slate-500 sm:self-center">
                    Laisse vide pour afficher les statistiques globales du jeu.
                </p>
            </div>
        </form>
    </section>

    <!-- MESSAGE D'ERREUR -->
    <?php if ($error): ?>
        <section>
            <div class="p-4 rounded-xl bg-red-900/40 border border-red-700 text-red-100">
                <?php echo htmlspecialchars($error); ?>
            </div>
        </section>
    <?php endif; ?>

    <!-- INFOS DU JEU -->
    <?php if ($appDetails): ?>
        <section class="mt-4">
            <div class="flex flex-col md:flex-row gap-4 items-start">
                <?php if (!empty($appDetails['header_image'])): ?>
                    <img
                        src="<?php echo htmlspecialchars($appDetails['header_image']); ?>"
                        alt="Cover du jeu"
                        class="w-full md:w-72 rounded-xl shadow-lg border border-slate-700"
                    >
                <?php endif; ?>

                <div class="flex-1 space-y-2">
                    <h2 class="text-2xl font-bold">
                        <?php echo htmlspecialchars($appDetails['name'] ?? "Jeu inconnu"); ?>
                    </h2>

                    <?php if (!empty($appDetails['short_description'])): ?>
                        <p class="text-slate-300">
                            <?php echo htmlspecialchars($appDetails['short_description']); ?>
                        </p>
                    <?php endif; ?>

                    <?php if (!empty($appDetails['genres'])): ?>
                        <div class="flex flex-wrap gap-2 mt-2">
                            <?php foreach ($appDetails['genres'] as $genre): ?>
                                <span class="px-2 py-1 rounded-full text-xs bg-slate-800 border border-slate-700">
                                    <?php echo htmlspecialchars($genre['description']); ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <p class="text-xs text-slate-500 mt-2">
                        AppID : <span class="font-mono"><?php echo htmlspecialchars($appid); ?></span>
                    </p>

                    <?php if ($forPlayer && $steamId): ?>
                        <p class="text-xs text-emerald-300 mt-1">
                            Mode profil : succès pour le joueur <span class="font-mono"><?php echo htmlspecialchars($steamId); ?></span>
                        </p>
                    <?php elseif ($appid): ?>
                        <p class="text-xs text-slate-400 mt-1">
                            Mode global : pourcentages basés sur tous les joueurs.
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <!-- LISTE DES SUCCÈS -->
    <?php if ($appDetails && !empty($achievements)): ?>
        <section class="mt-6">
            <h3 class="text-xl font-semibold mb-3">Succès du jeu</h3>
            <p class="text-sm text-slate-400 mb-4">
                Triés par rareté (les plus rares en premier).
            </p>

            <div class="grid gap-4 md:grid-cols-2">
                <?php foreach ($achievements as $ach): ?>
                    <?php
                        $unlocked = $ach['unlocked'] ?? null;
                        $cardClasses = $unlocked
                            ? "flex gap-3 p-3 rounded-xl bg-emerald-900/30 border border-emerald-500/60"
                            : "flex gap-3 p-3 rounded-xl bg-slate-800 border border-slate-700 opacity-90";
                    ?>
                    <article class="<?php echo $cardClasses; ?>">
                        <?php if (!empty($ach['icon'])): ?>
                            <img
                                src="<?php echo htmlspecialchars($unlocked ? $ach['icon'] : ($ach['icon_gray'] ?? $ach['icon'])); ?>"
                                alt="Icône succès"
                                class="w-12 h-12 rounded-md border border-slate-600 flex-shrink-0"
                            >
                        <?php else: ?>
                            <div class="w-12 h-12 rounded-md border border-slate-600 flex items-center justify-center text-xs text-slate-500">
                                N/A
                            </div>
                        <?php endif; ?>

                        <div class="flex-1">
                            <h4 class="font-semibold flex items-center gap-2">
                                <?php echo htmlspecialchars($ach['displayName']); ?>
                                <?php if ($unlocked !== null): ?>
                                    <?php if ($unlocked): ?>
                                        <span class="text-xs px-2 py-0.5 rounded-full bg-emerald-600 text-white">
                                            Débloqué
                                        </span>
                                    <?php else: ?>
                                        <span class="text-xs px-2 py-0.5 rounded-full bg-slate-700 text-slate-200">
                                            Non débloqué
                                        </span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </h4>

                            <?php if (!empty($ach['description'])): ?>
                                <p class="text-sm text-slate-300">
                                    <?php echo htmlspecialchars($ach['description']); ?>
                                </p>
                            <?php else: ?>
                                <p class="text-sm text-slate-500 italic">
                                    Aucune description disponible.
                                </p>
                            <?php endif; ?>

                            <p class="text-xs text-slate-400 mt-1">
                                Rareté :
                                <?php if ($ach['percent'] !== null): ?>
                                    <span class="font-mono">
                                        <?php echo number_format($ach['percent'], 2, ',', ' '); ?> %
                                    </span>
                                <?php else: ?>
                                    <span class="italic">Inconnue</span>
                                <?php endif; ?>
                            </p>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php elseif ($appid && !$error): ?>
        <section class="mt-6">
            <p class="text-slate-400 text-sm">
                Aucun succès à afficher pour ce jeu.
            </p>
        </section>
    <?php endif; ?>
</main>
</body>
</html>