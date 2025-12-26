<?php
session_start();
require_once '../Database.php';
require_once '../Controllers/SecurityController.php';
global $invalidChars;

if (!isset($_SESSION['user']['user_id']) || !isset($_POST['themes'])) {
    die("Erreur: ID utilisateur ou donnée de thème manquante.");
}

$pdo = Database::getConnection();

$stmt = $pdo->prepare("SELECT themes FROM user WHERE user_id = :id");
$stmt->execute([":id" => $_SESSION['user']['user_id']]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

$jsonThemeString = $result['themes'] ?? '[]';

$currentThemes = json_decode($jsonThemeString, true);

if (!is_array($currentThemes)) {
    $currentThemes = [];
}

if (!empty($newTheme)) {
    $currentThemes = array_unique($newTheme);
    $updatedThemesJson = json_encode(array_values($currentThemes));
    $stmt = $pdo->prepare("UPDATE user SET themes = :themes WHERE user_id = :id");
    $stmt->execute([
        ":themes" => $updatedThemesJson,
        ":id"     => $_SESSION['user']['user_id']
    ]);
}