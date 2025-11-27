<?php
// src/config.php

// ?? Mets ici ta vraie clé API Steam entre les quotes
if (!defined('STEAM_API_KEY')) {
    define('STEAM_API_KEY', '540C231ACCAA957360E4AA9DA9549952');
}

/**
 * Petite fonction utilitaire pour faire une requête GET et retourner du JSON.
 */
function api_get(string $url): ?array {
    $options = [
        "http" => [
            "method" => "GET",
            "header" => "Accept: application/json\r\n",
            "timeout" => 10
        ]
    ];

    $context = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);

    if ($response === false) {
        return null;
    }

    $data = json_decode($response, true);
    return is_array($data) ? $data : null;
}
