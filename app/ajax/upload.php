<?php

session_start();
require '../Controllers/FolderController.php';
require '../Controllers/DocumentController.php';
require_once  '../Controllers/SecurityController.php';
require_once '../Database.php';
global $rootPath, $invalidChars, $extToType, $typeToPreview, $maxUploadSize;

$pdo = Database::getConnection();

if(!SecurityController::checkAjax($pdo, methode:['POST'])){
    exit;
}

$meta = json_decode($_POST['meta'] ?? '{}', true);
$file = $_FILES['file'] ?? null;
$token = $meta['token'] ?? null;

if (!$file || !$meta) {
    echo "Fichier ou métadonnées manquants";
    exit;
}

echo $meta["size"];
echo $maxUploadSize;
if (($meta["size"] ?? 0) > $maxUploadSize) {
    echo "Fichier trop volumineux";
    exit;
}



if (isset($meta['folderId']) && $meta['folderId'] === 'root') {
    $folderId = FolderController::getRoot($pdo)['id'];
} else {
    $folderId = isset($meta['folderId']) ? (int)$meta['folderId'] : null;
}

if (isset($meta['token'])){
    if(!isset($_SESSION['upload']['token'])){
        $_SESSION['upload']['token'] = $meta['token'];
    }
    if($_SESSION['upload']['token'] !== $meta['token']){
        $_SESSION['upload']['created_folders'] = [];
        $_SESSION['upload']['token'] = $meta['token'];
    }
    if(!isset($_SESSION['upload']['created_folders'])){
        $_SESSION['upload']['created_folders'] = [];
    }
    $created = $_SESSION['upload']['created_folders'];
}

else {
echo'absent token';
exit;
}

$username = $_SESSION['user']['name'];
$path = [];
$folder = $folderId;
$currentFolder = FolderController::getById($pdo, $folder)[0];

while ($currentFolder['name']!== 'root')
{
    $path[] = $currentFolder['parent_id'];
    $currentFolder = FolderController::getById($pdo, $currentFolder['parent_id'])[0];
}


$pathWithoutLast = array_reverse(array_slice($path, 0, -1));
$pathString = !empty($pathWithoutLast) ? implode('/', $pathWithoutLast) : '';


echo 'hey';
if(isset($meta['webdir']) && $meta['webdir'] !== ''){
    $folderInf = FolderController::createAllFolders($pdo, $username, $meta['webdir'], $folderId, $created);
    $_SESSION['upload']['created_folders'] = $created;
    $folderId = $folderInf['id'];
    $pathString = $folderInf['name'];
    if(dirname($folderInf['path']) != '.'){
        $pathString = dirname($folderInf['path']).'\\'.$pathString;
    }

}
else{
    $folderInf = FolderController::getById($pdo, $folderId)[0];
}


    $new = DocumentController::getUniqueName($pdo, $meta['name'], $folderId);

    $newName = $new['name'] ?? [];
    $ext = $new['ext'] ?? [];

    if($folderInf["name"]==="root"){
        $rpath = $newName . '.' . $ext;
        $rdir = $rpath;
    }
    else {
        $rpath = (dirname($folderInf['path']) !== '.' || '' ? dirname($folderInf['path']) . '\\' : '') . $folderInf["id"] . '\\' . $newName . '.' . $ext;
        $rdir = (dirname($folderInf['path']) !== '.' || '' ? DocumentController::pathToDir($pdo, $folderInf['path']) : $folderInf["name"]) . '\\' . $newName . '.' . $ext;
    }

    $document = [
        'name' => $newName.'.'.$ext,
        'path' => $rpath,
        'folder_id' => $folderId,
        'type' => $extToType[$ext] ?? 'diver',
        'size' => $meta['size'],
        'preview' => $typeToPreview[$ext ?? '.xxx'] ?? 'file.png',
        'owner' => $_SESSION['user']['user_id']
    ];


        $dirPath = $rootPath . '/'.$username.'/' . $rdir;
        $dirPath = str_replace(["\\", "//"], ["/", "/"], $dirPath);
        if (!file_exists($dirPath)) {
            if(move_uploaded_file($_FILES['file']['tmp_name'], $dirPath)){
                DocumentController::insert($pdo, $document);
            }
            else{
                $error = error_get_last();
                error_log('Échec move_uploaded_file vers ' . $dirPath . '. Erreur : ' . print_r($error, true));
            }
        }
        else{
            error_log('Fichier existant : ' . $dirPath);
        }


    json_encode("SUCCESS");
