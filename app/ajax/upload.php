<?php

session_start();
require '../Controllers/FolderController.php';
require '../Controllers/DocumentController.php';
require '../Config/config.php';
global $rootPath;

$data = json_decode(file_get_contents('php://input'), true);
if(isset($data['folderId']) && $data['folderId'] === 'root'){
    $folderId = FolderController::getRoot()['id'];}
else{$folderId = isset($data['folderId']) ? (int)$data['folderId'] : null;}
echo 'folderId : '.$folderId;
$files = $data['files'] ?? null;


$path=[];

$folder = $folderId;
do{
    $currentFolder = FolderController::getById($folder)[0];
    $path[] = $currentFolder['name'];
    $folder = FolderController::getById($currentFolder['id'])[0]['parent_id'];
} while($folder !== null);

$pathWithoutLast = array_reverse(array_slice($path, 0, -1));
$pathString = implode('/', $pathWithoutLast);


$existingName = array_column(DocumentController::listTuplesToPrint(folderId : $folderId), 'name');
echo 'existing names : ';
foreach($files as $file) {
    $newName = pathinfo($file['name'], PATHINFO_FILENAME);
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    if (in_array($file['name'], $existingName)) {
        $i = 1;
        $newName = $file['name'] . ' (' . $i . ')';
        $i++;
        while (in_array($newName, $existingName)) {
            $newName = $file['name'] . ' (' . $i . ')';
            $i++;
        }}
        $newName = $newName.'.'.$ext;
        echo 'name : '.$newName;
        $document['name'] = $newName;
        $document['path'] = $pathString . '/' . $newName;
        $document['folder_id'] = $folderId;
        $document['type'] = $extToType[$file['extension']] ?? 'document';
        $document['size'] = $file['size'];
        $document['preview'] = $typeToPreview[$document['type']] ?? 'file.png';
        $document['owner'] = $_SESSION['user']['user_id'];
        if ($file['content']!= null){
        DocumentController::insert($document);
        $path =$rootPath.'/'.$document['path'];
        echo 'path : '.$path;
        $path = str_replace(["\\", "//"], ["/", "/"], $path);
            if (!file_exists($path)) {
            file_put_contents($path, $file['content']);

        echo $path;
        }}}
