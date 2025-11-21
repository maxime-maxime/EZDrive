<?php
session_start();
$_SESSION['user'] = ['user_id' => 0];

require '../Controllers/DocumentController.php';
require '../Controllers/FolderController.php';
$docs = DocumentController::listTuplesToPrint(['type' => 'fileType']);
print_r($docs);
$folders = FolderController::getFolderWithChildren(3);
//print_r($folders['children']['Files'][0]);

$root = FolderController::getRoot();
//print_r($root);

$_SESSION['user'] = ['user_id' => 1];

$root = FolderController::getRoot();
//print_r($root);

print_r(FolderController::getFolderWithChildren(10, getFiles:true));

