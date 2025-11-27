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

// Fichiers reçus
print_r($_FILES);
// Construction du chemin
$path = [];
$folder = $folderId;
do {
    $currentFolder = FolderController::getById($folder)[0];
    $path[] = $currentFolder['name'];
    $folder = $currentFolder['parent_id'];
} while ($folder !== null);

$pathWithoutLast = array_reverse(array_slice($path, 0, -1));
$pathString = !empty($pathWithoutLast) ? implode('/', $pathWithoutLast) : '';

if(isset($meta['webdir'])){
    $folderId = FolderController::createAllFolders($meta['webdir'], $folderId, $created);
    $pathString = FolderController::getById($folderId)[0]['path'];
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


    $rpath = ($pathString !== '' ? $pathString . '/' : '') . $newName;


    $document = [
        'name' => $newName,
        'path' => $rpath,
        'folder_id' => $folderId,
        'type' => $extToType[$ext] ?? 'document',
        'size' => $meta['size'],
        'preview' => $typeToPreview[$extToType[$ext] ?? 'document'] ?? 'file.png',
        'owner' => $_SESSION['user']['user_id']
    ];

        $basePath = $rootPath;

            $path = $basePath . '/' . $rpath;
            $path = str_replace(["\\", "//"], ["/", "/"], $path);

        DocumentController::insert($document);

        if (!file_exists($path)) {
            if(move_uploaded_file($_FILES['file']['tmp_name'], $path)){
                echo 'file created at ' . $path;
            }
            else{
                $error = error_get_last();
                error_log('Échec move_uploaded_file vers ' . $path . '. Erreur : ' . print_r($error, true));
            }
        }
        else{
            error_log('Fichier existant : ' . $path);
        }


    json_encode("SUCCESS");
