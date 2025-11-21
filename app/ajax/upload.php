<?php

session_start();
require '../Controllers/FolderController.php';
require '../Controllers/DocumentController.php';
require '../Config/config.php';
global $rootPath;

$data = json_decode(file_get_contents('php://input'), true);

// Récupération du folderId
if (isset($data['folderId']) && $data['folderId'] === 'root') {
    $folderId = FolderController::getRoot()['id'];
} else {
    $folderId = isset($data['folderId']) ? (int)$data['folderId'] : null;
}

// Fichiers reçus
$files = $data['files'] ?? [];

// Construction du chemin
$path = [];
$folder = $folderId;
do {
    $currentFolder = FolderController::getById($folder)[0];
    $path[] = $currentFolder['name'];
    $folder = $currentFolder['parent_id'];
} while ($folder !== null);

// Exclusion du dernier dossier et inversion
$pathWithoutLast = array_reverse(array_slice($path, 0, -1));
$pathString = !empty($pathWithoutLast) ? implode('/', $pathWithoutLast) : '';
// Liste des noms existants
$err = ['/', '\\', '?', '%', '*', ':', '|', '"', '<', '>', "\0", "\n", "\r"];

$existingName = array_column(DocumentController::listTuplesToPrint(folderId: $folderId), 'name');

foreach ($files as $file) {
    $baseName = pathinfo($file['name'], PATHINFO_FILENAME);
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);

    // Vérifier les caractères interdits
    if (strpbrk($baseName, implode('', $err)) !== false) {
        continue; // ignorer ce fichier
    }

    $newName = $baseName;
    $i = 1;

    // Gestion des doublons
    print_r($existingName);
    echo $newName . '.' . $ext;
    while (in_array($newName . '.' . $ext, $existingName)) {
        $newName = $baseName . ' (' . $i . ')';
        $i++;
    }
    $newName .= '.' . $ext;
    $existingName[] = $newName; // mettre à jour pour les prochains fichiers

    $document = [
        'name' => $newName,
        'path' => ($pathString !== '' ? $pathString . '/' : '') . $newName,
        'folder_id' => $folderId,
        'type' => $extToType[$ext] ?? 'document',
        'size' => $file['size'],
        'preview' => $typeToPreview[$extToType[$ext] ?? 'document'] ?? 'file.png',
        'owner' => $_SESSION['user']['user_id']
    ];

    if (!empty($file['content'])) {
        DocumentController::insert($document);

        $path = $rootPath . '/' . $document['path'];
        $path = str_replace(["\\", "//"], ["/", "/"], $path);

        if (!file_exists($path)) {
            $base64 = preg_replace('#^data:.*;base64,#', '', $file['content']);
            file_put_contents($path, base64_decode($base64));        }
    }
}
