<?php
// /api/achievements.php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../config.php';

$appid = isset($_GET['appid']) ? intval($_GET['appid']) : 0;

if ($appid <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing or invalid appid']);
    exit;
}

$schema   = getAchievementSchema($appid, 'french');
$percents = getGlobalAchievementPercentages($appid);

$result = [];
foreach ($schema as $internalName => $info) {
    $result[] = [
        'internal_name'   => $internalName,
        'name'            => $info['display_name'],
        'description'     => $info['description'],
        'icon'            => $info['icon'],
        'icon_gray'       => $info['icon_gray'],
        'percent_global'  => $percents[$internalName] ?? null
    ];
}

echo json_encode($result);