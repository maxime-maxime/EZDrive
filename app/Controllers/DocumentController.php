<?php
require '../Models/Document.php';

class DocumentController
{
    public static function updateRow(array $tuple, int $id): void
    {
        Document::updateRow($tuple, $id);
    }

    public static function deleteRows(int|array $id): void
    {
        Document::deleteRows($id);
    }

    public static function getByPath(string $path): array
    {
        return Document::getByPath($path);
    }
    public static function uploadFile(array $files, $path): string
    {
        return Document::uploadFile($files, $path);
    }


    public static function insert(array $data): void
    {
        Document::insert($data);
    }


    public static function listTuplesToPrint(array $criteria = [], string $order = 'id', string $orderType = 'ASC', $folderId = null): array
    {
        $resp = Document::getDataFiltered($criteria, getTuples: true, order: $order, orderType: $orderType, folderId : $folderId);

        $documents = [];
        foreach ($resp['rows'] as $row) {
            $documents[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'path' => $row['path'],
                'preview' => $row['preview'],
                'owner' =>$row['owner']
            ];
        }
        return $documents;
    }

    public static function getUniqueName(string $name, int $folderId): array {
        global $invalidChars;
        $filename = pathinfo($name, PATHINFO_FILENAME);
        $ext = pathinfo($name, PATHINFO_EXTENSION);

        foreach ($invalidChars as $char) {
            if (str_contains($filename, $char)) {
                $filename = str_replace($char, '_', $filename);
            }
        }
        $existingNames = array_column(self::listTuplesToPrint(folderId: $folderId), 'name');
        $newName = $name;
        $i = 1;
        while (in_array($newName, $existingNames)) {
            $newName = $filename . ' (' . $i . ')';
            $i++;
        }
        return ['name'=>$newName,
                'ext'=>$ext];
    }

}