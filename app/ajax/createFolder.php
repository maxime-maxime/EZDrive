<?php
session_start();
require '../Controllers/FolderController.php';
require '../Controllers/DocumentController.php';
require '../Config/config.php';
global $rootPath, $invalidChars;

$parentId = isset($_GET['parentId']) && $_GET['parentId'] === 'root'
    ? FolderController::getRoot()['id']
    : ($_GET['parentId'] ?? null);

if ($parentId !== null) {
    FolderController::createFolder($parentId, $_GET['name'] ?? 'coucou');
}


