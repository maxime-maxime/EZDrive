<?php
session_start();
require '../Controllers/FolderController.php';
require '../Controllers/DocumentController.php';
require '../Config/config.php';
global $rootPath;

$err = [
    '/', '\\', '.', '+', '-', '*', '(', ')', '?', '[', ']', '{', '}', ':', ';', '!', ',', ' ',
    '@', '#', '$', '%', '^', '&', '_', '`', '~', '|',
    "\n", "\t", "\r", "\f", "\v", "\0"
];
$name = $_GET['name'] ?? 'coucou';
if (isset($_GET['parentId']) && $_GET['parentId'] === 'root') {
    $parentId = FolderController::getRoot()['id'];
} else {
    $parentId = isset($_GET['parentId']) ? (int) $_GET['parentId'] : null;
}


foreach($err as $error){
    if(str_contains($name, $error)){
        $name = str_replace($error, '_', $name);
    }
}
$newName = $name ;
$existingName = array_column(FolderController::getFolderWithChildren($parentId)['children']['folders'], 'name');
if (in_array($name, $existingName)) {
    $i = 1;
    $newName = $name . ' (' . $i . ')';
    $i++;
    while (in_array($newName, $existingName)) {
        $newName = $name . ' (' . $i . ')';
        $i++;
    }}


if ($parentId !== null) {
    $data = [
        'name' => $newName,
        'parent_id' => $parentId,
    ];
    print_r(FolderController::getById($parentId));
    $path = [FolderController::getById($parentId)[0]['name']];
    $parentId =FolderController::getById($parentId)[0]['parent_id'];

    while (end($path) !== 'root') {
        $folder = FolderController::getById($parentId)[0];
        $parentId = $folder['parent_id'];
        $path [] = $folder['name'];
    }


    $pathWithoutLast = array_reverse(array_slice($path, 0, -1));
    $pathString = implode('\\', $pathWithoutLast).'\\'.$newName;

    $dir = $rootPath. '\\' . $pathString;
    $dir = str_replace(["\\", "//"], ["/", "/"], $dir);

    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
        $data['path'] = $pathString;
        FolderController::create($data);
    }}


