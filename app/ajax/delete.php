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

$del = getFoldersToDel($foldersArray, $filesArray,$foldersArray);

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
function deleteDir($dir) {
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



function getFoldersToDel(array $folderIds, array $files = [], array $folders = []): array {
    foreach ($folderIds as $id) {

        $children = FolderController::getFolderWithChildren($id, getFiles: true)['children'];

        $childFolders = array_column($children['folders'], 'id');
        $childFiles   = array_column($children['files'], 'id');

        $folders = array_merge($folders, $childFolders);
        $files   = array_merge($files, $childFiles);

        if (!empty($childFolders)) {
            $result = getFoldersToDel($childFolders, $files, $folders);
            $files   = $result['files'];
            $folders = $result['folders'];
        }
    }

    return [
        'files'   => array_values(array_unique($files)),
        'folders' => array_values(array_unique($folders)),
    ];
}
