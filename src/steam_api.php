<?php
// src/steam_api.php

require_once __DIR__ . '/config.php';

// S�curit� : si la constante n'est toujours pas d�finie, on arr�te tout de suite.
if (!defined('STEAM_API_KEY')) {
    throw new RuntimeException('STEAM_API_KEY n\'est pas d�fini. V�rifie src/config.php.');
}

/**
 * R�cup�re les infos de base d�un jeu (nom, cover, etc.)
 * via l�API du store Steam.
 */
function getSteamAppDetails(int $appid): ?array {
    $url = "https://store.steampowered.com/api/appdetails?appids={$appid}&l=french";

    $data = api_get($url);
    if (!$data || !isset($data[$appid]['success']) || !$data[$appid]['success']) {
        return null;
    }

    return $data[$appid]['data'] ?? null;
}

/**
 * R�cup�re les pourcentages globaux de d�blocage des succ�s
 * pour un jeu donn�.
 */
function getSteamGlobalAchievements(int $appid): ?array {
    $url = "https://api.steampowered.com/ISteamUserStats/GetGlobalAchievementPercentagesForApp/v0002/?gameid={$appid}&format=json";

    $data = api_get($url);
    if (!$data || !isset($data['achievementpercentages']['achievements'])) {
        return null;
    }

    $result = [];
    foreach ($data['achievementpercentages']['achievements'] as $ach) {
        if (isset($ach['name'], $ach['percent'])) {
            $result[$ach['name']] = $ach['percent'];
        }
    }
    return $result;
}

/**
 * R�cup�re le schema du jeu (succ�s, ic�nes, descriptions)
 */
function getSteamGameSchema(int $appid): ?array {
    $url = "https://api.steampowered.com/ISteamUserStats/GetSchemaForGame/v2/?key="
         . urlencode(STEAM_API_KEY)
         . "&appid={$appid}&l=french";

    $data = api_get($url);
    if (!$data || !isset($data['game']['availableGameStats']['achievements'])) {
        return null;
    }

    return $data['game']['availableGameStats']['achievements'];
}

/**
 * Combine le schema (nom, description, ic�ne) et les pourcentages
 * en un seul tableau de succ�s pr�ts � �tre affich�s.
 */
function getSteamAchievementsWithPercent(int $appid): array {
    $schema = getSteamGameSchema($appid);
    $percents = getSteamGlobalAchievements($appid);

    if (!$schema) {
        return [];
    }

    $achievements = [];

    foreach ($schema as $ach) {
        $apiName = $ach['name'] ?? null;
        if (!$apiName) continue;

        $achievements[] = [
            'api_name'    => $apiName,
            'displayName' => $ach['displayName'] ?? $apiName,
            'description' => $ach['description'] ?? '',
            'icon'        => $ach['icon'] ?? null,
            'icon_gray'   => $ach['icongray'] ?? null,
            'percent'     => $percents[$apiName] ?? null,
        ];
    }

    usort($achievements, function($a, $b) {
        $pa = $a['percent'] ?? 0;
        $pb = $b['percent'] ?? 0;
        if ($pa == $pb) return 0;
        return ($pa < $pb) ? -1 : 1;
    });

    return $achievements;
}

/**
 * Si l'utilisateur te donne une SteamID64, on la garde telle quelle.
 * Si c'est un pseudo de profil personnalisé (vanity URL),
 * on le résout via l'API Steam.
 */
function resolveSteamId(string $input): ?string {
    $input = trim($input);

    // Si c'est déjà une SteamID64 (gros nombre)
    if (ctype_digit($input) && strlen($input) >= 16) {
        return $input;
    }

    // Sinon, on suppose que c'est une vanity URL (pseudo)
    $url = "https://api.steampowered.com/ISteamUser/ResolveVanityURL/v1/?"
         . "key=" . urlencode(STEAM_API_KEY)
         . "&vanityurl=" . urlencode($input);

    $data = api_get($url);
    if (!$data || ($data['response']['success'] ?? 0) != 1) {
        return null;
    }

    return $data['response']['steamid'] ?? null;
}

/**
 * Récupère les succès d'un joueur pour un jeu donné
 */
function getPlayerAchievementsRaw(string $steamId, int $appid): ?array {
    $url = "https://api.steampowered.com/ISteamUserStats/GetPlayerAchievements/v1/?"
         . "key=" . urlencode(STEAM_API_KEY)
         . "&steamid=" . urlencode($steamId)
         . "&appid={$appid}"
         . "&l=french";

    $data = api_get($url);
    if (!$data || !isset($data['playerstats']['achievements'])) {
        return null;
    }

    return $data['playerstats']['achievements'];
}

/**
 * Combine :
 *  - schema du jeu (nom, description, icônes)
 *  - pourcentage global
 *  - état du joueur (débloqué ou non)
 */
function getPlayerAchievementsDetailed(string $steamId, int $appid): array {
    $schema   = getSteamGameSchema($appid);
    $percents = getSteamGlobalAchievements($appid);
    $player   = getPlayerAchievementsRaw($steamId, $appid);

    if (!$schema) {
        return [];
    }

    // Indexer les succès du joueur par api name
    $playerByApi = [];
    if ($player) {
        foreach ($player as $a) {
            if (!isset($a['apiname'])) continue;
            $playerByApi[$a['apiname']] = $a;
        }
    }

    $achievements = [];

    foreach ($schema as $ach) {
        $apiName = $ach['name'] ?? null;
        if (!$apiName) continue;

        $playerData = $playerByApi[$apiName] ?? null;

        $unlocked   = $playerData && !empty($playerData['achieved']);
        $unlockTime = $playerData['unlocktime'] ?? null;

        $achievements[] = [
            'api_name'    => $apiName,
            'displayName' => $ach['displayName'] ?? $apiName,
            'description' => $ach['description'] ?? '',
            'icon'        => $ach['icon'] ?? null,
            'icon_gray'   => $ach['icongray'] ?? null,
            'percent'     => $percents[$apiName] ?? null,
            'unlocked'    => $unlocked,
            'unlock_time' => $unlockTime,
        ];
    }

    // Tu peux garder le tri par rareté si tu veux
    usort($achievements, function($a, $b) {
        $pa = $a['percent'] ?? 0;
        $pb = $b['percent'] ?? 0;
        if ($pa == $pb) return 0;
        return ($pa < $pb) ? -1 : 1;
    });

    return $achievements;
}

