<?php
session_start();
require '../Controllers/FolderController.php';
require '../Controllers/DocumentController.php';

$files = $_GET['files'] ?? null;
$folders = $_GET['folders'] ?? null;
$foldersArray = $folders ? explode(',', $folders) : [];
$filesArray = $files ? array_map('intval', explode(',', $files)) : [];
$filesArray = getFolders($foldersArray, $filesArray);


$file = array_column(DocumentController::listTuplesToPrint(['id'=>$filesArray]), 'path');
echo json_encode($file);


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