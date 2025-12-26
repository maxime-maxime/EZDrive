<?php
session_start();
require '../Controllers/FolderController.php';
require '../Controllers/DocumentController.php';
require '../Controllers/SecurityController.php';
require '../Database.php';
global $rootPath;

$pdo = Database::getConnection();

if(!SecurityController::checkAjax($pdo)){
    exit;
}

$files = $_GET['files'] ?? null;
$folders = $_GET['folders'] ?? null;
$foldersArray = $folders ? explode(',', $folders) : [];
$filesArray = $files ? array_map('intval', explode(',', $files)) : [];

$del = FolderController::getFoldersToDel($pdo, $foldersArray, $filesArray, $foldersArray);

$paths_to_delete = [];

if (!empty($del['files'])) DocumentController::deleteDocuments($pdo, $del['files'], $paths_to_delete);

if (!empty($del['folders'])) FolderController::deleteFolders($pdo, $del['folders'], $paths_to_delete);

$paths_to_delete = array_unique($paths_to_delete);

foreach ($paths_to_delete as $path) {
    $delpath = $rootPath . '/' . $path;
    $delpath = str_replace(["\\", "//"], ["/", "/"], $delpath);

    if (is_file($delpath)) {
        unlink($delpath);
    } elseif (is_dir($delpath)) {
        deleteDir($delpath);
    }
}

function deleteDir(string $dir): void {
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
