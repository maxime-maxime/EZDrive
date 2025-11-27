<?php
session_start();
require '../Controllers/FolderController.php';
require '../Controllers/DocumentController.php';
require '../Config/config.php';
global $rootPath;

$files = $_GET['files'] ?? null;
$folders = $_GET['folders'] ?? null;
$foldersArray = $folders ? explode(',', $folders) : [];
$filesArray = $files ? array_map('intval', explode(',', $files)) : [];

$del = FolderController::getFoldersToDel($foldersArray, $filesArray,$foldersArray);

$files = [];
$folders = [];
if(!empty($del['folders'])) {
    $folders = array_column(FolderController::getById($del['folders']), "path");
    FolderController::deleteRows($del['folders']);
}
if(!empty($del['files'])) {
    $files = array_column(DocumentController::listTuplesToPrint(['id'=>$del['files']]), 'path');
    DocumentController::deleteRows($del['files']);
}

$folders = array_merge($folders, $files);

foreach($folders as $path){
    $delpath = $rootPath .'/'. $path;
    $delpath = str_replace(["\\", "//"], ["/", "/"], $delpath);
    if(is_file($delpath)){
        unlink($delpath);
    }
    if(is_dir($delpath)){
        deleteDir($delpath);
    }
}
function deleteDir($dir):void {
    if (!is_dir($dir)) return;

    foreach (scandir($dir) as $item) {
        if ($item === '.' || $item === '..') continue;

        $path = $dir . '/' . $item;

        if (is_dir($path)) {
            deleteDir($path);
        } else {
            unlink($path);
        }
    }
    rmdir($dir);

}
