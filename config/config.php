<?php
// config.php

const STEAM_API_KEY = 'TA_CLE_API_STEAM';

// Récupère les jeux possédés
function getOwnedGames(string $steamId): array {
    $url = "https://api.steampowered.com/IPlayerService/GetOwnedGames/v0001/";
    $params = [
        'key' => STEAM_API_KEY,
        'steamid' => $steamId,
        'include_appinfo' => 1,
        'format' => 'json'
    ];

    $query = http_build_query($params);
    $response = file_get_contents($url . '?' . $query);
    if ($response === false) {
        return [];
    }

    $data = json_decode($response, true);
    return $data['response']['games'] ?? [];
}

// URL cover (header) d'un jeu
function getGameCoverUrl(int $appid): string {
    return "https://cdn.cloudflare.steamstatic.com/steam/apps/%7B$appid%7D/header.jpg";
}

// Schéma des succès d'un jeu (nom, desc, icône)
function getAchievementSchema(int $appid, string $lang = 'french'): array {
    $url = "https://api.steampowered.com/ISteamUserStats/GetSchemaForGame/v2/";
    $params = [
        'key' => STEAM_API_KEY,
        'appid' => $appid,
        'l' => $lang,
        'format' => 'json'
    ];

    $query = http_build_query($params);
    $response = file_get_contents($url . '?' . $query);
    if ($response === false) {
        return [];
    }

    $data = json_decode($response, true);
    $game = $data['game'] ?? [];
    $achievements = $game['availableGameStats']['achievements'] ?? [];

    $schema = [];
    foreach ($achievements as $ach) {
        $name = $ach['name'];
        $schema[$name] = [
            'internal_name' => $name,
            'display_name'  => $ach['displayName'] ?? $name,
            'description'   => $ach['description'] ?? '',
            'icon'          => $ach['icon'] ?? '',
            'icon_gray'     => $ach['icongray'] ?? ''
        ];
    }
    return $schema;
}

// Pourcentage global des succès d'un jeu
function getGlobalAchievementPercentages(int $appid): array {
    $url = "https://api.steampowered.com/ISteamUserStats/GetGlobalAchievementPercentagesForApp/v2/";
    $params = [
        'gameid' => $appid,
        'format' => 'json'
    ];

    $query = http_build_query($params);
    $response = file_get_contents($url . '?' . $query);
    if ($response === false) {
        return [];
    }

    $data = json_decode($response, true);
    $achievements = $data['achievementpercentages']['achievements'] ?? [];

    $result = [];
    foreach ($achievements as $ach) {
        $result[$ach['name']] = $ach['percent'];
    }
    return $result;
    }
?>