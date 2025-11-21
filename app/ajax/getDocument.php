<?php

require '../models/Document.php'; // ou ton autoloader

$id = $_GET['id'] ?? null; // récupère l'id du document depuis l'URL
if (!$id) {
echo json_encode(['error' => 'ID manquant']);
exit;
}

$doc = Document::getById($id);
$doc = array_filter($doc, fn($value) => $value !== "empty");
echo json_encode($doc);
