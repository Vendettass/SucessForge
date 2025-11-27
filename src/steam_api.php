<?php
// src/steam_api.php

require_once __DIR__ . '/config.php';

// Sécurité : si la constante n'est toujours pas définie, on arrête tout de suite.
if (!defined('STEAM_API_KEY')) {
    throw new RuntimeException('STEAM_API_KEY n\'est pas défini. Vérifie src/config.php.');
}

/**
 * Récupère les infos de base d’un jeu (nom, cover, etc.)
 * via l’API du store Steam.
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
 * Récupère les pourcentages globaux de déblocage des succès
 * pour un jeu donné.
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
 * Récupère le schema du jeu (succès, icônes, descriptions)
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
 * Combine le schema (nom, description, icône) et les pourcentages
 * en un seul tableau de succès prêts à être affichés.
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
