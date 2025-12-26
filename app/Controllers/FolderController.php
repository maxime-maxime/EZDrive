<?php
require_once '../Models/Folder.php';
require_once '../Config/config.php';
class FolderController
{
    // Récupérer tous les dossiers de l'utilisateur
    public static function listAll($pdo): array
    {
        return Folder::getAll($pdo);
    }

    // Récupérer le dossier racine de l'utilisateur
    public static function getRoot($pdo): ?array
    {
        return Folder::getRoot($pdo);
    }

    // Récupérer un dossier et ses enfants
    public static function getFolderWithChildren($pdo, int $id, bool $getFiles = false): array
    {
        $children = Folder::getChildren($pdo, $id, $getFiles);
        return [
            'folder' => $id,
            'children' => $children
        ];
    }

    // Créer un nouveau dossier
    public static function create($pdo,$data): int
    {
        return Folder::insert($pdo, $data);
    }

    // Mettre à jour un dossier existant
    public static function update($pdo, int $id, array $data): void
    {
        Folder::updateRow($pdo, $data, $id);
    }

    // Supprimer un dossier
    public static function deleteRows($pdo, int|array $id): void
    {
        Folder::deleteRow($pdo, $id);
    }

    // Récupérer uniquement les sous-dossiers et fichiers pour AJAX


    public static function getById($pdo, int|array $id): array
    {
        return Folder::getById($pdo, $id);
    }


    public static function getParent($pdo, int $id):array{
        return Folder::getParent($pdo, $id);
    }

    public static function sanitizeFolderName($pdo, string $name): string {
        global $invalidChars;
        foreach ($invalidChars as $char) {
            if (str_contains($name, $char)) {
                $name = str_replace($char, '_', $name);
            }
        }
        return $name;
    }

    public static function getUniqueFolderName($pdo, string $name, int $parentId): string {
        $existingNames = array_column(FolderController::getFolderWithChildren($pdo, $parentId)['children']['folders'], 'name');
        $newName = self::sanitizeFolderName($pdo, $name);
        $i = 1;
        while (in_array($newName, $existingNames)) {
            $newName = $name . ' (' . $i . ')';
            $i++;
        }
        return $newName;
    }

    public static function buildFolderPath($pdo, int $parentId): string {
        $path = [$parentId];
        $parentId = FolderController::getById($pdo, $parentId)[0]['parent_id'];
        while ($parentId !== null) {
            $folder = FolderController::getById($pdo, $parentId)[0];
            $path[] = $parentId;
            $parentId = $folder['parent_id'];
        }
        array_pop($path);
        return implode('\\',  array_reverse($path));
    }

    public static function createFolder($pdo, string $username, int $parentId, string $name, bool $verify = true ): array {
        global $rootPath;
        $sanitizedName = self::sanitizeFolderName($pdo, $name);
        $uniqueName = $verify ? self::getUniqueFolderName($pdo, $sanitizedName, $parentId) : $sanitizedName;
        $relative =str_replace("\\\\","\\", self::buildFolderPath($pdo, $parentId).'\\'.$uniqueName);
        $dir = $rootPath . '\\'.$username.'\\'. self::pathToDir($pdo, $relative);
        $dir = str_replace(["/", "\\\\"], "\\", $dir);

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
            $path = $relative;
            if (isset($path[0]) && $path[0] === '\\') {
                $path = substr($path, 1);
            }
            $data = [
                'name' => $uniqueName,
                'parent_id' => $parentId,
                'path' => $path
            ];
            FolderController::create($pdo, $data);

        }
        else $data=[];
        return Folder::getByPath($pdo, $data['path']?? '');
    }

    public static function getFoldersToDel($pdo, array $folderIds, array $files = [], array $folders = []): array {
            foreach ($folderIds as $id) {

                $children = self::getFolderWithChildren($pdo, $id, getFiles: true)['children'];

                $childFolders = array_column($children['folders'], 'id');
                $childFiles   = array_column($children['files'], 'id');

                $folders = array_merge($folders, $childFolders);
                $files   = array_merge($files, $childFiles);

                if (!empty($childFolders)) {
                    $result = self::getFoldersToDel($pdo, $childFolders, $files, $folders);
                    $files   = $result['files'];
                    $folders = $result['folders'];
                }
            }
            return [
                'files'   => array_values(array_unique($files)),
                'folders' => array_values(array_unique($folders)),
            ];
        }
    public static function getByPath($pdo, string $path): array
    {
        return Folder::getByPath($pdo, $path);
    }

    public static function createAllFolders($pdo, string $username, string $webdir, int $parentId, array &$created)
    {
        $parts = explode('/', trim($webdir, '/'));
        array_pop($parts); // retirer le fichier final
        if (empty($parts)) {
            return $parentId;
        }
        foreach ($parts as $name) {
            $key = $parentId . '|' . $name;
            if (!isset($created[$key])) {
                $folderInf = self::createFolder($pdo, $username, $parentId, $name);
                $folderId = $folderInf['id'];
                $created[$key] = $folderId;
            } else {
                $folderId = $created[$key];
                $folderInf = Folder::getByKey($pdo, $parentId, $name)[0];
            }
            $parentId = $folderId;
        }
        return $folderInf;
    }

    public static function togleFavorite($pdo, int $id): void
    {
        Folder::togleFavorite($pdo, $id);
    }

    public static function rename($pdo, int $id, string $name, int $parentId) :array
    {
        $previousName = Folder::getById($pdo, $id)[0]['name'];
        $newName = self::getUniqueFolderName($pdo, $name, $parentId);
        Folder::rename($pdo, $id, $newName);
        $path = self::pathToDir($pdo, Folder::getById($pdo, $id)[0]['path']);

        return [
            'path' => $path,
            'name' => $newName,
            'previousName' =>$previousName
        ];
    }


        public static function pathToDir($pdo, $p) :string{
            $name = basename($p);
            $oldPath = explode('\\',dirname($p));
            $path='';
            foreach ($oldPath as $cPath) {
                if($cPath === '' || $cPath === null || $cPath === '.') continue;
                $path .= FolderController::getById($pdo, (int)$cPath)[0]["name"] . "\\";
            }
            return $path.$name;
        }

        public static function deleteFolders($pdo, array $ids, array &$paths_to_delete): void{

            $folders_info = FolderController::getById($pdo, $ids);

            foreach ($folders_info as $folder_info) {
                $paths_to_delete[] = self::pathToDir($pdo, $folder_info['path']);
            }

            self::deleteRows($pdo, $ids);
        }

    public static function search($pdo, string $search): array {
        $ids = Folder::search($pdo, $search);
        if (empty($ids)) {
            return []; 
        }
        $resp = self::getById($pdo, $ids);
        foreach ($resp as $row) {
            $folders[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'path' => $row['path'],
                'owner' =>$row['owner'],
                'favorite' => $row['favorite'],
            ];
        }
        return $folders;
    }
}



