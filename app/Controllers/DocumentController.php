<?php
require_once '../Models/Document.php';
require_once '../Config/config.php';

class DocumentController
{
    public static function updateRow($pdo, array $tuple, int $id): void
    {
        Document::updateRow($pdo, $tuple, $id);
    }

    public static function deleteRows($pdo, int|array $id): void
    {
        Document::deleteRows($pdo, $id);
    }

    public static function getByPath($pdo, string $path): array
    {
        return Document::getByPath($pdo, $path);
    }
    public static function uploadFile($pdo, array $files, $path): string
    {
        return Document::uploadFile($pdo, $files, $path);
    }


    public static function insert($pdo, array $data): void
    {
        Document::insert($pdo, $data);
    }


    public static function listTuplesToPrint($pdo, array $criteria = [], string $order = 'id', string $orderType = 'ASC', $folderId = null): array
    {
        $resp = Document::getDataFiltered($pdo, $criteria, getTuples: true, order: $order, orderType: $orderType, folderId : $folderId);

        $documents = [];
        foreach ($resp['rows'] as $row) {
            $documents[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'path' => $row['path'],
                'preview' => $row['preview'],
                'owner' =>$row['owner'],
                'favorite' => $row['favorite'],
            ];
        }
        return $documents;
    }

    public static function getById($pdo,int $id): array{
        return Document::getById($pdo, $id);
    }

    public static function getUniqueName($pdo, string $name, int $folderId): array {
        global $invalidChars;
        $filename = pathinfo($name, PATHINFO_FILENAME);
        $ext = pathinfo($name, PATHINFO_EXTENSION);
        foreach ($invalidChars as $char) {
            if (str_contains($filename, $char)) {
                $filename = str_replace($char, '_', $filename);
            }
        }
        $existingNames = array_column(self::listTuplesToPrint($pdo, folderId: $folderId), 'name');
        $newName = $filename;
        $i = 1;
        while (in_array($newName.'.'.$ext, $existingNames)) {
            $newName = $filename . ' (' . $i . ')';
            $i++;
        }
        return ['name'=>$newName,
                'ext'=>$ext];
    }
    public static function togleFavorite($pdo, int $id): void
    {
        Document::togleFavorite($pdo, $id);
    }

    public static function rename($pdo,int $id, string $newName):array{
        $fileInfo = Document::getById($pdo, $id);
        $newName=$newName.".".pathinfo($fileInfo['name'], PATHINFO_EXTENSION);
        $path = $fileInfo['path'];
        $path = dirname($path).'\\';
        if($path === '.\\') $path = "";
        echo "path : ".$path;
        $newName = implode(".",self::getUniqueName($pdo, $newName, $fileInfo['folder_id']));
        Document::rename($pdo, $id, $newName,$path);
        return [
            "path" => Document::getById($pdo, $id)['path'],
            "name" => $newName,
            "previousName" => $fileInfo['name']
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

    public static function deleteDocuments($pdo, array $ids, array &$paths_to_delete): void{
        $files_info = self::listTuplesToPrint($pdo, ['id' => $ids]);
        foreach ($files_info as $file_info) {
            $paths_to_delete[] = self::pathToDir($pdo, $file_info['path']);
        }
        self::deleteRows($pdo, $ids);

    }

        public static function search($pdo, string $search): array{
            $ids = Document::search($pdo, $search);
            if (empty($ids)) {
                return []; 
            }
            $files = self::listTuplesToPrint($pdo, criteria : ['id' => $ids]);
            return $files;

    }
}