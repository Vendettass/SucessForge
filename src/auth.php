<?php
// src/auth.php

define('USERS_FILE', '/var/www/data/users.json');

/** Lit et renvoie le tableau d'utilisateurs (vide si aucun) */
function read_users(): array {
    if (!file_exists(USERS_FILE)) {
        return [];
    }
    $json = @file_get_contents(USERS_FILE);
    $data = json_decode($json, true);
    return is_array($data) ? $data : [];
}

/** Sauvegarde le tableau d'utilisateurs (atomique avec lock) */
function save_users(array $users): bool {
    $dir = dirname(USERS_FILE);
    if (!is_dir($dir)) mkdir($dir, 0770, true);

    $fp = fopen(USERS_FILE, 'c+');
    if (!$fp) return false;
    flock($fp, LOCK_EX);
    ftruncate($fp, 0);
    rewind($fp);
    $written = fwrite($fp, json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
    return $written !== false;
}

/** Crée un utilisateur, retourne user array ou null si existant */
function register_user(string $username, string $password, ?string $steamid = null): ?array {
    $username = trim($username);
    if ($username === '' || $password === '') return null;

    $users = read_users();
    // check exist
    foreach ($users as $u) {
        if (strcasecmp($u['username'], $username) === 0) return null;
    }

    $id = 1;
    if (!empty($users)) {
        $ids = array_column($users, 'id');
        $id = max($ids) + 1;
    }

    $user = [
        'id' => $id,
        'username' => $username,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'steamid' => $steamid ?? '',
        'created_at' => gmdate('c')
    ];

    $users[] = $user;
    if (save_users($users)) return $user;
    return null;
}

/** Authentifie un utilisateur (username + password) */
function authenticate_user(string $username, string $password): ?array {
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

/** Met à jour le steamid d'un utilisateur par id */
function update_user_steamid(int $id, string $steamid): bool {
    $users = read_users();
    foreach ($users as &$u) {
        if ($u['id'] == $id) {
            $u['steamid'] = $steamid;
            return save_users($users);
        }
    }
    return false;
}