<?php
session_start();
require '../Controllers/FolderController.php';
require '../Controllers/DocumentController.php';

$criteria = $_GET ?? null;
$order = $_GET['order'] ?? 'id';
$orderType = $_GET['orderType'] ?? 'ASC';
$folderId = $_GET['folderId'] ?? null;
if ($folderId == 'root'){
    $folderId = FolderController::getRoot()['id'];
}
$filterCriteria = $criteria;

unset($filterCriteria['order']);
unset($filterCriteria['orderType']);
unset($filterCriteria['folderId']);


$children = FolderController::getFolderWithChildren($folderId)['children']['folders'];

$filteredDocs = DocumentController::listTuplesToPrint(
    criteria: $filterCriteria,
    order: $order,
    orderType: $orderType,
    folderId: $folderId
);

$result = [
    'Folders' => $children,
    'Files'   => $filteredDocs
];

echo json_encode($result);
