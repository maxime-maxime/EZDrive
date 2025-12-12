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

$file=[];
if(!empty($del['files'])) {
    $files = DocumentController::listTuplesToPrint(['id'=>$del['files']]);
    foreach($files as $tmp){
        $file []= DocumentController::pathToDir($tmp['path']);
    }
    DocumentController::deleteRows($del['files']);
}

$folder=[];
if(!empty($del['folders'])) {
    print_r($del['folders']);
    $folders = FolderController::getById($del['folders']);
    print_r($folders);
    foreach($folders as $tmp){
        echo FolderController::pathToDir($tmp['path']);
        $folder []= FolderController::pathToDir($tmp['path']);
        FolderController::deleteRows($del['folders']);
    }}
    print_r($folder);
    print_r($file);
    $folders = array_merge($folder, $file);
    $folders = array_unique($folders);

    foreach($folders as $path){
        $delpath = $rootPath .'/'. $path;
        echo 'delpath : '. $delpath;
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
