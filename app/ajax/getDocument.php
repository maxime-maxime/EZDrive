<?php
require '../Controllers/DocumentController.php';
require_once '../Controllers/SecurityController.php';
require_once '../Database.php';

$pdo = Database::getConnection();

if(!SecurityController::checkAjax($pdo)){
    exit;
}

$id = $_GET['id'] ?? null;
if (!$id) {
exit;
}

$doc = DocumentController::getById($pdo, $id);
$doc = array_filter($doc, fn($value) => $value !== "empty");
echo json_encode($doc);
