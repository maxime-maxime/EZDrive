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

$files = $_GET['files'] ?? null;
$folders = $_GET['folders'] ?? null;
$foldersArray = $folders ? explode(',', $folders) : [];
$filesArray = $files ? array_map('intval', explode(',', $files)) : [];
$filesArray = getFolders($pdo, $foldersArray, $filesArray);
$rows = DocumentController::listTuplesToPrint($pdo,['id' => $filesArray]);
$files = array_map(
    fn($r) => DocumentController::pathToDir($pdo, $r['path'])
    ,
    $rows
);
echo json_encode($files);


function getFolders($pdo, $folderId, $filesArray):array{
    foreach($folderId as $folder){
        $children = FolderController::getFolderWithChildren($pdo, $folder, getFiles: true)['children'];
        $childFolders = array_column($children['folders'], 'id');
        $childFiles = array_column($children['files'], 'id');
        $filesArray = array_merge($filesArray, $childFiles);
        $filesArray = getFolders($pdo, $childFolders, $filesArray);
    }
    return $filesArray;
}