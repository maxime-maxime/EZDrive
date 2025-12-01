<?php
session_start();
require_once '../Controllers/DocumentController.php';
require_once '../Controllers/FolderController.php';

if (isset($_GET['folders']) && isset($_GET['files'])) {
    $folders = $_GET['folders'] !== '' ? explode(',', $_GET['folders']) : [];
    $files   = $_GET['files']   !== '' ? explode(',', $_GET['files'])   : [];

    foreach ($folders as $id) {
        $id = (int)$id;
        if ($id > 0) {

            FolderController::togleFavorite($id);
        }
    }

    foreach ($files as $id) {
        $id = (int)$id;
        if ($id > 0) {
            DocumentController::togleFavorite($id);
        }
    }

    echo 'Favoris mis à jour';
} else {
    echo 'Aucun dossier ou fichier sélectionné';
}
