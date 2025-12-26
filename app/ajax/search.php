<?php
session_start();
require '../Controllers/FolderController.php';
require '../Controllers/DocumentController.php';
require_once '../Controllers/SecurityController.php';
require '../Database.php';

$pdo = Database::getConnection();

if(!SecurityController::checkAjax($pdo)){
    exit;
}

$searchText = $_GET['searchText'] ?? null;


$files = DocumentController::search($pdo, $searchText);
$folders = FolderController::search($pdo, $searchText);


$result = [
    'Folders' => $folders,
    'Files'   => $files
];

echo json_encode($result);



