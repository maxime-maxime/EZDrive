<?php
require '../Models/Folder.php';
require '../Config/config.php';
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

    public static function sanitizeFolderName(string $name): string {
        global $invalidChars;
        foreach ($invalidChars as $char) {
            if (str_contains($name, $char)) {
                $name = str_replace($char, '_', $name);
            }
        }
        return $name;
    }

    public static function getUniqueFolderName(string $name, int $parentId): string {
        $existingNames = array_column(FolderController::getFolderWithChildren($parentId)['children']['folders'], 'name');
        $newName = $name;
        $i = 1;
        while (in_array($newName, $existingNames)) {
            $newName = $name . ' (' . $i . ')';
            $i++;
        }
        return $newName;
    }

    public static function buildFolderPath(int $parentId, string $newName): string {
        global $rootPath;

        $path = [FolderController::getById($parentId)[0]['name']];
        $parentId = FolderController::getById($parentId)[0]['parent_id'];

        while (end($path) !== 'root') {
            $folder = FolderController::getById($parentId)[0];
            $parentId = $folder['parent_id'];
            $path[] = $folder['name'];
        }

        $pathWithoutLast = array_reverse(array_slice($path, 0, -1));
        $pathString = implode('\\', $pathWithoutLast) . '\\' . $newName;
        return str_replace(["\\", "//"], ["/", "/"], $rootPath . '\\' . $pathString);
    }

    public static function createFolder(int $parentId, string $name, bool $verify = true ): array {
        global $rootPath;
        $sanitizedName = self::sanitizeFolderName($name);
        $uniqueName = $verify ? self::getUniqueFolderName($sanitizedName, $parentId) : $sanitizedName;
        $dir = self::buildFolderPath($parentId, $uniqueName);

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);

            $dirNormalized = str_replace("/", "\\", $dir);
            $rootNormalized = str_replace("/", "\\", $rootPath);

            $relative = str_replace($rootNormalized, '', $dirNormalized);
            $data = [
                'name' => $uniqueName,
                'parent_id' => $parentId,
                'path' => $relative
            ];
            FolderController::create($data);
        }
        else $data=[];
        return Folder::getByPath($data['path']?? null);
    }

    public static function getFoldersToDel(array $folderIds, array $files = [], array $folders = []): array {
            foreach ($folderIds as $id) {

                $children = self::getFolderWithChildren($id, getFiles: true)['children'];

                $childFolders = array_column($children['folders'], 'id');
                $childFiles   = array_column($children['files'], 'id');

                $folders = array_merge($folders, $childFolders);
                $files   = array_merge($files, $childFiles);

                if (!empty($childFolders)) {
                    $result = self::getFoldersToDel($childFolders, $files, $folders);
                    $files   = $result['files'];
                    $folders = $result['folders'];
                }
            }
            return [
                'files'   => array_values(array_unique($files)),
                'folders' => array_values(array_unique($folders)),
            ];
        }
    public static function getByPath(string $path): array
    {
        return Folder::getByPath($path);
    }

    public static function createAllFolders(string $webdir, int $parentId, array &$created)
    {
        $parts = explode('/', trim($webdir, '/'));
        array_pop($parts); // retirer le fichier final
        if (empty($parts)) {
            return $parentId;
        }

        foreach ($parts as $name) {

            $key = $parentId . '|' . $name;
            if (!isset($created[$key])) {
                $folderId = self::createFolder($parentId, $name)['id'];
                $created[$key] = $folderId;
            } else {
                $folderId = $created[$key];
            }
            $parentId = $folderId;
        }

        return $parentId;
    }

    public static function togleFavorite(int $id): void
    {
        Folder::togleFavorite($id);
    }

    public static function rename(int $id, string $name, int $parentId) :array
    {
        $previousName = Folder::getById($id)[0]['name'];
        $newName = self::getUniqueFolderName($name, $parentId);
        Folder::rename($id, $newName);


        return [
            'path' => Folder::getById($id)[0]['path'],
            'name' => $newName,
            'previousName' =>$previousName
        ];
    }


}



