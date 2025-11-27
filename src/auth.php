<?php
// src/auth.php

// Fichier JSON où sont stockés les comptes
define('USERS_FILE', '/var/www/data/users.json');

/** Lit et renvoie le tableau d'utilisateurs ([] si vide/inexistant) */
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

/** Crée un utilisateur, retourne son tableau ou null si échec / déjà pris */
function register_user(string $username, string $password, ?string $steamid = null): ?array {
    $username = trim($username);
    if ($username === '' || $password === '') {
        return null;
    }

    $users = read_users();

    // Vérifier si le nom existe déjà
    foreach ($users as $u) {
        if (strcasecmp($u['username'], $username) === 0) {
            return null;
        }
    }

    $id = 1;
    if (!empty($users)) {
        $ids = array_column($users, 'id');
        $id = max($ids) + 1;
    }

    $user = [
        'id'           => $id,
        'username'     => $username,
        'password_hash'=> password_hash($password, PASSWORD_DEFAULT),
        'steamid'      => $steamid ?? '',
        'created_at'   => gmdate('c'),
    ];

    $users[] = $user;

    if (!save_users($users)) {
        return null;
    }

    return $user;
}

/** Authentifie un utilisateur (username + password), retourne le user complet ou null */
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

/** Met à jour le steamid pour un utilisateur donné */
function update_user_steamid(int $id, string $steamid): bool {
    $users = read_users();
    $updated = false;

    foreach ($users as &$u) {
        if ($u['id'] === $id) {
            $u['steamid'] = $steamid;
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