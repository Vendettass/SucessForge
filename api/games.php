<?php
// /api/games.php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once DIR . '/../config.php';

$steamId = $_GET['steamid'] ?? null;

if (!$steamId) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing steamid parameter']);
    exit;
}

$games = getOwnedGames($steamId);

$result = [];
foreach ($games as $g) {
    $appid = $g['appid'];
    $result[] = [
        'appid' => $appid,
        'name'  => $g['name'],
        'playtime_forever' => $g['playtime_forever'] ?? 0,
        'cover' => getGameCoverUrl($appid),
    ];
}

echo json_encode($result);
