<?php
session_start();
require_once '../Controllers/DocumentController.php';
require_once '../Controllers/FolderController.php';
require_once '../Controllers/OwnerController.php';
require_once '../Controllers/SecurityController.php';
require_once '../Database.php';

$pdo = Database::getConnection();

if(!SecurityController::checkAjax($pdo)){
    exit;
}

if(isset($_GET['folderId']) && $_GET['folderId'] != 'null'){
    $folder = FolderController::getById($pdo, (int) $_GET['folderId'])[0];
    $owner = OwnerController::getOwnerById($pdo, $folder['owner']);
    $folderInfos=[
        'Nom' => $folder["name"],
        "Type" => 'dossier',
        'Modifié le' => $folder['updated_at'],
        'Créé le' => $folder['created_at'],
        'Propriétaire' => $owner["name"],
        'Chemin' =>$owner["name"] .'/'. str_replace("\\","/",FolderController::pathToDir($pdo, $folder["path"]))
        ];
    echo json_encode($folderInfos);
    exit;
}


if(isset($_GET['fileId']) && $_GET['fileId'] != 'null'){
    $file = DocumentController::getById($pdo, (int) $_GET['fileId']);
    $owner = OwnerController::getOwnerById($pdo, $file['owner']);
    $folderInfos=[
        'Nom' => $file["name"],
        'Type' => $file['type'],
        'Modifié le' => $file['updated_at'],
        'Créé le' => $file['created_at'],
        'Propriétaire' => $owner["name"],
        'Chemin' =>$owner["name"] . "/".str_replace("\\","/",DocumentController::pathToDir($pdo, $file["path"]))
    ];
    echo json_encode($folderInfos);
}
