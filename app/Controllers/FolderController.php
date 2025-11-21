<?php
require '../Models/Folder.php';
class FolderController
{
    // Récupérer tous les dossiers de l'utilisateur
    public static function listAll(): array
    {
        return Folder::getAll();
    }

    // Récupérer le dossier racine de l'utilisateur
    public static function getRoot(): ?array
    {
        return Folder::getRoot();
    }

    // Récupérer un dossier et ses enfants
    public static function getFolderWithChildren(int $id, bool $getFiles = false): array
    {
        $folder = $id;
        if (!$folder) {
            throw new Exception("Dossier introuvable.");
        }
        $children = Folder::getChildren($id, $getFiles);
        return [
            'folder' => $folder,
            'children' => $children
        ];
    }

    // Créer un nouveau dossier
    public static function create($data): int
    {
        return Folder::insert($data);
    }

    // Mettre à jour un dossier existant
    public static function update(int $id, array $data): void
    {
        Folder::updateRow($data, $id);
    }

    // Supprimer un dossier
    public static function deleteRows(int|array $id): void
    {
        Folder::deleteRow($id);
    }

    // Récupérer uniquement les sous-dossiers et fichiers pour AJAX


    public static function getById(int|array $id): array
    {
        return Folder::getById($id);
    }


    public static function getParent(int $id):array{
        return Folder::getParent($id);
    }
}