<?php
session_start();

if (!isset($_SESSION['user']['user_id']) || !isset($_POST['themes'])) {
    die("Erreur: ID utilisateur ou donnée de thème manquante.");
}

$pdo = Database::getConnection();
$userId = $_SESSION['user']['user_id'];
$newTheme = trim($_POST['themes']);

$stmt = $pdo->prepare("SELECT themes FROM user WHERE user_id = :id");
$stmt->execute([":id" => $userId]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

$jsonThemeString = $result['themes'] ?? '[]';

$currentThemes = json_decode($jsonThemeString, true);

if (!is_array($currentThemes)) {
    $currentThemes = [];
}

if (!empty($newTheme)) {
    $currentThemes[] = $newTheme;

    $currentThemes = array_unique($currentThemes);

    $updatedThemesJson = json_encode(array_values($currentThemes));

    $stmt = $pdo->prepare("UPDATE user SET themes = :themes WHERE user_id = :id");
    $stmt->execute([
        ":themes" => $updatedThemesJson,
        ":id"     => $userId
    ]);
}