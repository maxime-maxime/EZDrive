<?php
session_start();
require_once '../Controllers/DocumentController.php';
require_once '../Controllers/FolderController.php';
require_once '../Controllers/SecurityController.php';
require_once '../Database.php';

global $rootPath;

$pdo = Database::getConnection();

if(!SecurityController::checkAjax($pdo)){
    exit;
}

if (isset($_GET['folders']) || isset($_GET['files']) || isset($_GET['parent_id']) ) {

    $parentId = $_GET['parent_id'] ?? FolderController::getRoot($pdo)['id'];

    $files = json_decode($_GET['files'], true);

    if (is_array($files)) {
        foreach ($files as $id => $name) {
            $file_id = (int)$id;
            if ($file_id > 0 && !empty($name)) {
                $file = DocumentController::rename($pdo, $file_id, $name);
                $filePath = DocumentController::pathToDir($pdo, $file['path']);
                $previousName = $file['previousName'];
                $newName = $file['name'];
                $path = dirname($filePath) != '.' ? dirname($filePath) : '';
                $oldpath = $rootPath .'\\'. $path.'\\'.$previousName;
                $oldpath =  str_replace("/", "\\", $oldpath);
                $newpath = $rootPath .'\\'. $path.'\\'.$newName;
                $newpath =  str_replace("/", "\\", $newpath);
                rename( $oldpath,  $newpath);}
        }
    }


    $folders = isset($_GET['folders']) ? json_decode($_GET['folders'], true) : [];

    if (is_array($folders)) {
        foreach ($folders as $id => $newName) {
            $folder_id = (int)$id;

            if ($folder_id > 0 && !empty($newName)) {
                $folder = FolderController::rename($pdo,(int) $folder_id, $newName, (int) $parentId);
                $previousName = $folder['previousName'];
                $newName = $folder['name'];
                $path = dirname($folder['path']) != '.' ? dirname($folder['path']):'';
                $oldpath =$rootPath ."\\" . $path.'\\'.$previousName;
                $newpath = $rootPath ."\\". $path.'\\'.$newName;
                $oldpath =  str_replace(["/",'\\\\'], "\\", $oldpath);
                $newpath =  str_replace(["/",'\\\\'], "\\", $newpath);
                rename( $oldpath,  $newpath);
            }
        }
    }
}