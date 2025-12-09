<?php

session_start();
require '../Controllers/FolderController.php';
require '../Controllers/DocumentController.php';
global $rootPath, $invalidChars, $extToType, $typeToPreview;

$meta = json_decode($_POST['meta'] ?? '{}', true);
$file = $_FILES['file'] ?? null;



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
do {
    $currentFolder = FolderController::getById($folder)[0]['parent_id'];
    $path[] = $currentFolder;
} while ($currentFolder !== null);

$pathWithoutLast = array_reverse(array_slice($path, 0, -1));
$pathString = !empty($pathWithoutLast) ? implode('/', $pathWithoutLast) : '';


if(isset($meta['webdir'])){
    $folderId = FolderController::createAllFolders($meta['webdir'], $folderId, $created);
    $pathString = dirname(FolderController::getById($folderId)[0]['path']).'\\'.$folderId;
    if($pathString[0]=="."){
        $pathString = substr($pathString, 2);
    }
    $_SESSION['upload']['created_folders'] = $created;
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


    $rpath = ($pathString !== '' ? $pathString . '\\' : '') . $newName . '.' . $ext;


    $document = [
        'name' => $newName.'.'.$ext,
        'path' => $rpath,
        'folder_id' => $folderId,
        'type' => $extToType[$ext] ?? 'document',
        'size' => $meta['size'],
        'preview' => $typeToPreview[$extToType[$ext] ?? 'document'] ?? 'file.png',
        'owner' => $_SESSION['user']['user_id']
    ];


        $dirPath = DocumentController::pathToDir($rpath);
        $dirPath = $rootPath . '/' . $dirPath;
        $dirPath = str_replace(["\\", "//"], ["/", "/"], $dirPath).$document['name'];
        if (!file_exists($dirPath)) {
            if(move_uploaded_file($_FILES['file']['tmp_name'], $dirPath)){
                DocumentController::insert($document);
                echo 'file created at ' . $dirPath;
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
