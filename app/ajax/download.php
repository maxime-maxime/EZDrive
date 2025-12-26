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
$filesArray = getFolders($foldersArray, $filesArray);
$rows = DocumentController::listTuplesToPrint($pdo,['id' => $filesArray]);
$files = array_map(
    fn($r) => DocumentController::pathToDir($pdo, $r['path'])
    ,
    $rows
);
echo json_encode($files);


function getFolders($folderId, $filesArray):array{
    foreach($folderId as $folder){
        $children = FolderController::getFolderWithChildren($folder, getFiles: true)['children'];
        $childFolders = array_column($children['folders'], 'id');
        $childFiles = array_column($children['files'], 'id');
        $filesArray = array_merge($filesArray, $childFiles);
        $filesArray = getFolders($childFolders, $filesArray);
    }
    return $filesArray;
}