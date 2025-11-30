<?php
session_start();
require_once '../Database.php';

$pdo = Database::getConnection();
$lastTheme = $_GET['last_theme']??'default';
$stmt = $pdo->prepare("UPDATE user SET last_theme = :last_theme WHERE user_id = :user_id");
$stmt->execute([":last_theme" => $lastTheme, ":user_id" => $_SESSION['user']['user_id']]);
