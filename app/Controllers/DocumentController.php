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


}