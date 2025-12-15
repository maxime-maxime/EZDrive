<?php
session_start();
require_once '../Controllers/DocumentController.php';
require_once '../Controllers/FolderController.php';
require_once '../Config/config.php';
global $rootPath;

// Vérifier si au moins un des tableaux de données est présent dans l'URL
if (isset($_GET['folders']) || isset($_GET['files']) || isset($_GET['parent_id']) ) {

    $parentId = isset($_GET['parent_id']) ? $_GET['parent_id'] : 'root';
    if($parentId === 'root')$parentId = FolderController::getRoot()['id'];


    $files = json_decode($_GET['files'], true);
    echo('files : ');
    print_r($files);

    if (is_array($files)) {
        foreach ($files as $id => $name) {
            $file_id = (int)$id;
            if ($file_id > 0 && !empty($name)) {
                $file = DocumentController::rename($file_id, $name);
                $filePath = DocumentController::pathToDir($file['path']);
                $previousName = $file['previousName'];
                $newName = $file['name'];
                $path = dirname($filePath) != '.' ? dirname($filePath) : '';
                $oldpath = $rootPath .'\\'. $path.'\\'.$previousName;
                $oldpath =  str_replace("/", "\\", $oldpath);
                $newpath = $rootPath .'\\'. $path.'\\'.$newName;
                $newpath =  str_replace("/", "\\", $newpath);
                echo 'old path : '.$oldpath;
                echo 'new path : '.$newpath;
                rename( $oldpath,  $newpath);}
        }
    }


    $folders = isset($_GET['folders']) ? json_decode($_GET['folders'], true) : [];

    if (is_array($folders)) {
        foreach ($folders as $id => $newName) {
            $folder_id = (int)$id;

            if ($folder_id > 0 && !empty($newName)) {
                $folder = FolderController::rename($folder_id, $newName, $parentId);
                $previousName = $folder['previousName'];
                $newName = $folder['name'];
                $path = dirname($folder['path']) != '.' ? dirname($folder['path']):'';
                $oldpath =$rootPath ."\\" . $path.'\\'.$previousName;
                $newpath = $rootPath ."\\". $path.'\\'.$newName;
                $oldpath =  str_replace(["/",'\\\\'], "\\", $oldpath);
                $newpath =  str_replace(["/",'\\\\'], "\\", $newpath);
                echo 'old path : '.$oldpath;
                echo 'new path : '.$newpath;
                echo 'previous name : '.$previousName;
                echo '  new name : '.$newName;
                rename( $oldpath,  $newpath);
            }
        }
    }
}