<?php
session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function requireAdmin() {
    requireLogin();
    if ($_SESSION['user_role'] !== 'admin') {
        header('Location: index.php');
        exit;
    }
}

function currentUser() {
    return [
        'id'   => $_SESSION['user_id']   ?? null,
        'nama' => $_SESSION['user_nama']  ?? null,
        'role' => $_SESSION['user_role']  ?? null,
    ];
}

function isAdmin() {
    return ($_SESSION['user_role'] ?? '') === 'admin';
}
