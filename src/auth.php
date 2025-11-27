<?php
// src/auth.php

// Fichier JSON où sont stockés les comptes
define('USERS_FILE', '/var/www/data/users.json');

/** Lit et renvoie le tableau d'utilisateurs ([] si vide ou inexistant) */
function read_users(): array {
    if (!file_exists(USERS_FILE)) {
        return [];
    }
    $json = @file_get_contents(USERS_FILE);
    $data = json_decode($json, true);
    return is_array($data) ? $data : [];
}

/** Sauvegarde le tableau d'utilisateurs (avec verrouillage fichier) */
function save_users(array $users): bool {
    $dir = dirname(USERS_FILE);
    if (!is_dir($dir)) {
        if (!@mkdir($dir, 0777, true) && !is_dir($dir)) {
            return false;
        }
    }

    $fp = @fopen(USERS_FILE, 'c+');
    if (!$fp) {
        return false;
    }

    if (!flock($fp, LOCK_EX)) {
        fclose($fp);
        return false;
    }

    ftruncate($fp, 0);
    rewind($fp);

    $json = json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    $written = fwrite($fp, $json);

    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);

    return $written !== false;
}

/**
 * Crée un utilisateur (username, password, steamid)
 * Retourne le user complet ou null si échec
 */
function register_user(string $username, string $password, ?string $steamid = null): ?array {
    $username = trim($username);
    $steamid = trim($steamid ?? "");

    if ($username === '' || $password === '') {
        return null;
    }

    $users = read_users();

    // Vérifier si le nom existe déjà (insensible à la casse)
    foreach ($users as $u) {
        if (strcasecmp($u['username'], $username) === 0) {
            return null;
        }
    }

    // Calcul d’un nouvel ID auto
    $id = empty($users) ? 1 : (max(array_column($users, 'id')) + 1);

    $user = [
        'id'            => $id,
        'username'      => $username,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'steamid'       => $steamid, // incrusté directement ici
        'created_at'    => gmdate('c'),
    ];

    $users[] = $user;

    if (!save_users($users)) {
        return null;
    }

    return $user;
}

/**
 * Authentifie un utilisateur
 * Retourne le user complet ou null si incorrect
 */
function authenticate_user(string $username, string $password): ?array {
    $username = trim($username);
    if ($username === '' || $password === '') {
        return null;
    }

    $users = read_users();
    foreach ($users as $u) {
        if (strcasecmp($u['username'], $username) === 0) {
            if (password_verify($password, $u['password_hash'])) {
                return $u;
            }
            return null;
        }
    }
    return null;
}

/**
 * Met à jour le SteamID d’un utilisateur
 * (utile si tu veux plus tard modifier le SteamID)
 */
function update_user_steamid(int $id, string $steamid): bool {
    $users = read_users();
    $updated = false;

    foreach ($users as &$u) {
        if ($u['id'] === $id) {
            $u['steamid'] = trim($steamid);
            $updated = true;
            break;
        }
    }
    unset($u);

    if ($updated) {
        return save_users($users);
    }
    return false;
}
