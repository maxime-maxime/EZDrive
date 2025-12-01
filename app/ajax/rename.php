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
    print_r($files);

    if (is_array($files)) {
        foreach ($files as $id => $name) {
            $file_id = (int)$id;
            if ($file_id > 0 && !empty($name)) {
                $file = DocumentController::rename($file_id, $name);
                $filePath = $file['path'];
                $previousName = $file['previousName'];
                $newName = $file['name'];

                $oldpath = $rootPath . dirname($filePath)."\\".$previousName;
                $oldpath =  str_replace("/", "\\", $oldpath);
                $newpath = $rootPath .'/'. dirname($filePath).'\\'. $newName;
                $newpath =  str_replace("/", "\\", $newpath);
                echo 'old path : '.$oldpath;
                echo 'new path : '.$newpath;
                rename( $oldpath,  $newpath);            }
        }
    }


    $folders = isset($_GET['folders']) ? json_decode($_GET['folders'], true) : [];

    if (is_array($folders)) {
        foreach ($folders as $id => $newName) {
            $folder_id = (int)$id;

            if ($folder_id > 0 && !empty($newName)) {
                $folder = FolderController::rename($folder_id, $newName, $parentId);
                $folderPath = $folder['path'];
                $name = $folder['previousName'];
                $newName = $folder['name'];
                $oldpath =$rootPath  . dirname($folderPath).'\\'.$name;
                $oldpath =  str_replace("/", "\\", $oldpath);
                $newpath = $rootPath .$folderPath;
                $newpath =  str_replace("/", "\\", $newpath);
                echo '  new name : '.$newName;
                echo 'previous name : '.$name;
                rename( $oldpath,  $newpath);
            }
        }
    }
}