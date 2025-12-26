<?php

class Folder
{
    // Récupérer tous les dossiers de l'utilisateur
    public static function getAll($pdo): array {

        $stmt = $pdo->prepare("SELECT * FROM folder WHERE owner = :owner");
        $stmt->execute([":owner" => $_SESSION['user']['user_id']]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Récupérer un dossier par ID
    public static function getById($pdo, int|array $ids): array|false
    {
        if (empty($ids)) {
        return []; 
    }
        $ids = (array)$ids;
        $in = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("SELECT * FROM folder WHERE id IN ($in) AND owner = ?");
        $stmt->execute([...$ids, $_SESSION['user']['user_id']]);
        $folders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $folders ?: [];
    }


    // Récupérer enfants (sous-dossiers + documents)
    public static function getChildren($pdo, int $id, bool $getFiles = false): array {

        $stmtFolder = $pdo->prepare("SELECT id,name, created_at, owner, favorite FROM folder WHERE parent_id = :id AND owner = :owner");
        $stmtFolder->execute([
            ":id" => $id,
            ":owner" => $_SESSION['user']['user_id']
        ]);
        $folders = $stmtFolder->fetchAll(PDO::FETCH_ASSOC);

        if($getFiles){
            $stmtFolder = $pdo->prepare("SELECT id FROM document WHERE folder_id = :id AND owner = :owner");
            $stmtFolder->execute([
                ":id" => $id,
                ":owner" => $_SESSION['user']['user_id']
            ]);
            $files = $stmtFolder->fetchAll(PDO::FETCH_ASSOC);
        }
        return [
            'folders' => $folders,
            'files' => $files ?? []
        ];
    }

    // Insertion sécurisée
    public static function insert($pdo, $data): int {
        $data['owner'] = $_SESSION['user']['user_id'];
        $columns = array_keys($data);
        $placeholders = array_map(fn($c) => ":$c", $columns);
        $sql = "INSERT INTO folder (" . implode(", ", $columns) . ") VALUES (" . implode(", ", $placeholders) . ")";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($data);
        return (int)$pdo->lastInsertId();
    }

    // Mise à jour sécurisée
    public static function updateRow($pdo, array $tuple, int $id): void {

        $stmt = $pdo->prepare("SELECT owner FROM folder WHERE id = :id");
        $stmt->execute([":id" => $id]);
        $owner = $stmt->fetchColumn();

        if ($owner === false) throw new Exception("Dossier introuvable.");
        if ((int)$owner !== (int)$_SESSION['user']['user_id']) throw new Exception("Accès refusé.");

        $sets = [];
        $params = [];
        foreach ($tuple as $col => $val) {
            if ($val !== null && $val !== "") {
                $sets[] = "$col = :$col";
                $params[":$col"] = $val;
            }
        }
        if (empty($sets)) return;

        $params[":id"] = $id;
        $sql = "UPDATE folder SET " . implode(", ", $sets) . " WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    }

    // Suppression sécurisée
    public static function deleteRow($pdo, array|int $ids): void
    {
        $ids = (array)$ids;

        // Vérifie propriété
        $in = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("SELECT id FROM folder WHERE id IN ($in) AND owner = ?");
        $stmt->execute([...$ids, $_SESSION['user']['user_id']]);

        // Suppression
        $stmt = $pdo->prepare("DELETE FROM folder WHERE id IN ($in)");
        $stmt->execute($ids);
    }

    public static function getRoot($pdo): ?array {
        $stmt = $pdo->prepare("SELECT * FROM folder WHERE owner = :owner AND parent_id IS NULL");
        $stmt->execute([":owner" => $_SESSION['user']['user_id']]);
        $root = $stmt->fetch(PDO::FETCH_ASSOC);
        return $root ?: null;
    }

    public static function getParent($pdo, $id, $share = false): ?array {
        $stmt = $pdo->prepare("SELECT name, id FROM folder WHERE parent_id = :parent_id AND owner = :owner");
        if($share){
            $stmt = $pdo->prepare("SELECT name, id FROM folder WHERE parent_id = :parent_id AND share = :share");
        }
        $stmt->execute([":owner" => $_SESSION['user']['user_id'], ":parent_id" => $id]);
        $root = $stmt->fetch(PDO::FETCH_ASSOC);
        return $root ?: null;
    }

    public static function getByPath($pdo, string $path): array {

        $stmt = $pdo->prepare("SELECT * FROM folder WHERE path = :path AND owner = :owner");
        $stmt->execute([
            ":path" => $path,
            ":owner" => $_SESSION['user']['user_id']
        ]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function togleFavorite($pdo, int $id): void
    {
        $stmt = $pdo->prepare("SELECT favorite FROM folder WHERE id = :id AND owner = :owner");
        $stmt->execute([":id" => $id,
            ":owner" => $_SESSION['user']['user_id']]);
        $favorite = $stmt->fetchColumn();
        $favorite = ($favorite==1) ? 0 : 1;
        $stmt = $pdo->prepare("UPDATE folder SET favorite = :favorite WHERE id = :id");
        $stmt->execute([
            ":favorite" => $favorite,
            ":id" => $id
        ]);
    }
    public static function rename($pdo, int $id, string $newName ):void{
        $path = Folder::getById($pdo, $id)[0]['path'];
        $path = dirname($path) != '.' ? dirname($path).'\\'.$newName : $newName;
        $stmt = $pdo->prepare("UPDATE folder SET name = :newname, path = :path WHERE id = :id AND owner = :owner");
        $stmt->execute([
            ":newname" => $newName,
            ":id" => $id,
            ":path" => $path,
            ":owner" => $_SESSION['user']['user_id']
        ]);
    }

    public static function getByKey($pdo, int $parentId, string $name): array|false
    {
        $stmt = $pdo->prepare("SELECT * FROM folder WHERE parent_id = :parentId AND name = :name AND owner = :owner");
        $stmt->execute([":owner" => $_SESSION['user']['user_id'],
            ":parentId" => $parentId,
            ":name" => $name]);
        $folders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $folders ?: [];
    }

    
    public static function search($pdo, ?string $search): array
    {

        $sql = "SELECT id FROM folder WHERE name LIKE :search AND owner = :owner";
        
        $stmt = $pdo->prepare($sql);
        
        $searchTerm = "%" . ($search ?? "") . "%";
        
        $stmt->execute([
            ":search" => $searchTerm,
            ":owner"  => $_SESSION['user']['user_id']
        ]);

        return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'id');
    }
}
