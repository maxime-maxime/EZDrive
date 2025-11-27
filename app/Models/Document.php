<?php
require '../Database.php';
class Document
{
    // Récupérer des données filtrées avec colonnes/types/tuples
    public static function getDataFiltered(
        array $criteria = [],
        bool $getColNames = false,
        bool $getColTypes = false,
        bool $getTuples = true,        // par défaut on veut tous les tuples
        string $order = 'id',
        string $orderType = 'ASC',
        int $folderId = null
    ): array {
        $pdo = Database::getConnection();
        $result = [];
        $conditions = [];
        $params = [];
        foreach ($criteria as $column => $value) {
            if ($value !== null && $value !== '') {
                if (is_array($value) && count($value) > 0) {
                    $placeholders = [];
                    foreach ($value as $i => $v) {
                        $ph = ":{$column}_$i";
                        $placeholders[] = $ph;
                        $params[$ph] = $v;
                    }
                    $conditions[] = "$column IN (" . implode(',', $placeholders) . ")";
                } else {
                    $conditions[] = "$column = :$column";
                    $params[":$column"] = $value;
                }
            }
        }
        $sql = "SELECT * FROM document WHERE owner = :owner";

        if($folderId !== null){
             $sql .= " AND folder_id = :folder_id ";
             $params[':folder_id'] = $folderId;
        }

        if (!empty($conditions)) {
            $sql .= " AND (" . implode(" OR ", $conditions) . ")";
        }
        
        $sql.= " ORDER BY $order $orderType";
        $stmt = $pdo->prepare($sql);
        $params[':owner'] = $_SESSION['user']['user_id'];
        $stmt->execute($params);


        // Colonnes et types
        if ($getColNames || $getColTypes) {
            $columns = [];
            $types = [];
            $metaCount = $stmt->columnCount();
            for ($i = 0; $i < $metaCount; $i++) {
                $meta = $stmt->getColumnMeta($i);
                if ($getColNames) $columns[] = $meta['name'];         // noms SQL exacts
                if ($getColTypes) $types[] = $meta['native_type'];
            }
            if ($getColNames) $result['columns'] = $columns;
            if ($getColTypes) $result['types'] = $types;
        }

        // Tuples
        if ($getTuples) {
            // fetchAll retourne les clés exactement comme dans SQL
            $result['rows'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        return $result;
    }


    // Valeurs distinctes pour une colonne, filtrées par owner
    public static function getDistinctValues(string $column): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT DISTINCT $column FROM document WHERE owner = :owner");
        $stmt->execute([":owner" => $_SESSION['user']['user_id']]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    // Récupérer un document par ID
    public static function getById(int $id): ?array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM document WHERE id = :id AND owner = :owner");
        $stmt->execute([
            ":id" => $id,
            ":owner" => $_SESSION['user']['user_id']
        ]);
        $doc = $stmt->fetch(PDO::FETCH_ASSOC);
        return $doc ?: [];
    }

    // Insertion sécurisée
    public static function insert(array $data): int {
        $pdo = Database::getConnection();
        $data['owner'] = $_SESSION['user']['user_id'];

        $columns = array_keys($data);
        $placeholders = array_map(fn($c) => ":$c", $columns);

        $sql = "INSERT INTO document (" . implode(", ", $columns) . ") VALUES (" . implode(", ", $placeholders) . ")";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($data);

        return (int)$pdo->lastInsertId();
    }

    // Mise à jour sécurisée
    public static function updateRow(array $tuple, int $id): void {
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("SELECT owner FROM document WHERE id = :id");
        $stmt->execute([":id" => $id]);
        $owner = $stmt->fetchColumn();

        if ($owner === false) throw new Exception("Document introuvable.");

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
        $sql = "UPDATE document SET " . implode(", ", $sets) . " WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    }

    // Suppression sécurisée
    public static function deleteRows(int|array $ids): void
    {
        $pdo = Database::getConnection();
        $ids = (array)$ids;

        $in = implode(',', array_fill(0, count($ids), '?'));

        $stmt = $pdo->prepare("DELETE FROM document WHERE id IN ($in) AND owner = ?");
        $stmt->execute([...$ids, $_SESSION['user']['user_id']]);
    }


    // Upload sécurisé
    public static function uploadFile(array $file, string $path): string {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException("Erreur de téléchargement : " . $file['error']);
        }

        $filename = basename($file['name']);
        $filepath = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;

        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            throw new RuntimeException("Impossible de déplacer le fichier vers $filepath");
        }

        return $filename;
    }

    public static function getByPath(string $path): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM document WHERE path = :path AND owner = :owner");
        $stmt->execute([
            ":path" => $path,
            ":owner" => $_SESSION['user']['user_id']
        ]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
