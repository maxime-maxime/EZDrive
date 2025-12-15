<?php
session_start();
require '../Controllers/FolderController.php';
require '../Controllers/DocumentController.php';
require '../Controllers/OwnerController.php';
require '../Config/config.php';
global $rootPath, $invalidChars, $easteregg;

$parentId = isset($_GET['parentId']) && $_GET['parentId'] === 'root'
    ? FolderController::getRoot()['id']
    : ($_GET['parentId'] ?? null);

if ($parentId !== null) {
    print_r($easteregg);
    if(array_key_exists($_GET['name'] ,$easteregg)){
        echo $_GET['name'];
        OwnerController::addTheme($easteregg[$_GET['name']]);
        exit;
    }
    FolderController::createFolder($parentId, $_GET['name'] ?? 'coucou');
}


