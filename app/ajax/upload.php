<?php

session_start();
require '../Controllers/FolderController.php';
require '../Controllers/DocumentController.php';
global $rootPath, $invalidChars, $extToType, $typeToPreview;

$meta = json_decode($_POST['meta'] ?? '{}', true);
$file = $_FILES['file'] ?? null;
print_r($meta);
print_r($file);



if (!$file || !$meta) {
    echo "Fichier ou métadonnées manquants";
    exit;
}

// Récupération du folderId
if (isset($meta['folderId']) && $meta['folderId'] === 'root') {
    $folderId = FolderController::getRoot()['id'];
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

$path = [];
$folder = $folderId;
$currentFolder = FolderController::getById($folder)[0];

while ($currentFolder['name']!== 'root')
{
    $path[] = $currentFolder['parent_id'];
    $currentFolder = FolderController::getById($currentFolder['parent_id'])[0];
}


$pathWithoutLast = array_reverse(array_slice($path, 0, -1));
$pathString = !empty($pathWithoutLast) ? implode('/', $pathWithoutLast) : '';


if(isset($meta['webdir']) && $meta['webdir'] !== ''){
    $folderInf = FolderController::createAllFolders($meta['webdir'], $folderId, $created);
    $_SESSION['upload']['created_folders'] = $created;
    $folderId = $folderInf['id'];
    $pathString = $folderInf['name'];
    if(dirname($folderInf['path']) != '.'){
        $pathString = dirname($folderInf['path']).'\\'.$pathString;
    }

}
else{
    $folderInf = FolderController::getById($folderId)[0];
}


    try {
        $new = DocumentController::getUniqueName($meta['name'], $folderId);
    }
    catch (Exception $e) {
            echo "Erreur : " . $e->getMessage();
            exit;
    }
    $newName = $new['name'] ?? [];
    $ext = $new['ext'] ?? [];

    if($folderInf["name"]==="root"){
        $rpath = $newName . '.' . $ext;
        $rdir = $rpath;
    }
    else {
        $rpath = (dirname($folderInf['path']) !== '.' || '' ? dirname($folderInf['path']) . '\\' : '') . $folderInf["id"] . '\\' . $newName . '.' . $ext;
        $rdir = (dirname($folderInf['path']) !== '.' || '' ? DocumentController::pathToDir($folderInf['path']) : $folderInf["name"]) . '\\' . $newName . '.' . $ext;
    }

    $document = [
        'name' => $newName.'.'.$ext,
        'path' => $rpath,
        'folder_id' => $folderId,
        'type' => $extToType[$ext] ?? 'document',
        'size' => $meta['size'],
        'preview' => $typeToPreview[$extToType[$ext] ?? 'document'] ?? 'file.png',
        'owner' => $_SESSION['user']['user_id']
    ];


        $dirPath = $rootPath . '/' . $rdir;
        $dirPath = str_replace(["\\", "//"], ["/", "/"], $dirPath);
        echo $dirPath;
        if (!file_exists($dirPath)) {
            if(move_uploaded_file($_FILES['file']['tmp_name'], $dirPath)){
                DocumentController::insert($document);
                echo 'file created at root
                ' . $rdir;
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
