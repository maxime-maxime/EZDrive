<?php
session_start();
require '../Controllers/FolderController.php';
require '../Controllers/DocumentController.php';
require '../Controllers/OwnerController.php';
require '../Controllers/SecurityController.php';
require '../Database.php';

global $rootPath, $invalidChars, $easteregg, $defaultFolderName;

$pdo = Database::getConnection();
$username = $_SESSION['user']['name'];

if(!SecurityController::checkAjax($pdo)){
    exit;
}

$parentId = isset($_GET['parentId']) && $_GET['parentId'] === 'root'
    ? FolderController::getRoot($pdo)['id']
    : ($_GET['parentId'] ?? null);

if ($parentId !== null) {
    if(array_key_exists($_GET['name'] ,$easteregg)){
        OwnerController::addTheme($pdo, $easteregg[$_GET['name']]);
        exit;
    }
    FolderController::createFolder($pdo, $username, $parentId, $_GET['name'] ?? $defaultFolderName);
}
else{
    exit;
}


