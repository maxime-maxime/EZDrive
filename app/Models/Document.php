<?php
class Document
{
    // Récupérer des données filtrées avec colonnes/types/tuples
public static function getDataFiltered(
    $pdo,
    array $criteria = [],
    bool $getColNames = false,
    bool $getColTypes = false,
    bool $getTuples = true,
    string $order = 'id',
    string $orderType = 'ASC',
    ?int $folderId = null // Ajout du "?" ici pour accepter null
): array {
    $result = [];
    $conditions = [];
    $params = [];

    // Gestion de l'owner (déplacé en haut pour clarté)
    $params[':owner'] = $_SESSION['user']['user_id'];

    foreach ($criteria as $column => $value) {
        if ($value !== null && $value !== '') {
            if (is_array($value) && count($value) > 0) {
                $placeholders = [];
                foreach ($value as $i => $v) {
                    $ph = ":{$column}_$i";
                    $placeholders[] = $ph;
                    $params[$ph] = $v;
                }
                // Correction potentielle : vérifiez si vous voulez vraiment OR ou AND ici
                $conditions[] = "$column IN (" . implode(',', $placeholders) . ")";
            } else {
                $conditions[] = "$column = :$column";
                $params[":$column"] = $value;
            }
        }
    }

    $sql = "SELECT * FROM document WHERE owner = :owner";

    // Gestion du folderId
    if ($folderId !== null) {
         $sql .= " AND folder_id = :folder_id ";
         $params[':folder_id'] = $folderId;
    }

    // Ajout des conditions dynamiques
    if (!empty($conditions)) {
        // Utilisation de AND pour lier les critères au reste de la requête
        $sql .= " AND (" . implode(" OR ", $conditions) . ")";
    }
    
    $sql .= " ORDER BY $order $orderType";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    // Métadonnées (Colonnes et types)
    if ($getColNames || $getColTypes) {
        $columns = [];
        $types = [];
        $metaCount = $stmt->columnCount();
        for ($i = 0; $i < $metaCount; $i++) {
            $meta = $stmt->getColumnMeta($i);
            if ($getColNames) $columns[] = $meta['name'];
            if ($getColTypes) $types[] = $meta['native_type'];
        }
        if ($getColNames) $result['columns'] = $columns;
        if ($getColTypes) $result['types'] = $types;
    }

    // Tuples
    if ($getTuples) {
        $result['rows'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    return $result;
}


    // Valeurs distinctes pour une colonne, filtrées par owner
    public static function getDistinctValues($pdo, string $column): array {
        $stmt = $pdo->prepare("SELECT DISTINCT $column FROM document WHERE owner = :owner");
        $stmt->execute([":owner" => $_SESSION['user']['user_id']]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    // Récupérer un document par ID
    public static function getById($pdo, int $id): ?array {
        $stmt = $pdo->prepare("SELECT * FROM document WHERE id = :id AND owner = :owner");
        $stmt->execute([
            ":id" => $id,
            ":owner" => $_SESSION['user']['user_id']
        ]);
        $doc = $stmt->fetch(PDO::FETCH_ASSOC);
        return $doc ?: [];
    }

    // Insertion sécurisée
    public static function insert($pdo, array $data): int {
        $data['owner'] = $_SESSION['user']['user_id'];

        $columns = array_keys($data);
        $placeholders = array_map(fn($c) => ":$c", $columns);

        $sql = "INSERT INTO document (" . implode(", ", $columns) . ") VALUES (" . implode(", ", $placeholders) . ")";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($data);

        return (int)$pdo->lastInsertId();
    }

    // Mise à jour sécurisée
    public static function updateRow($pdo, array $tuple, int $id): void {

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
    public static function deleteRows($pdo, int|array $ids): void
    {
        $ids = (array)$ids;

        $in = implode(',', array_fill(0, count($ids), '?'));

        $stmt = $pdo->prepare("DELETE FROM document WHERE id IN ($in) AND owner = ?");
        $stmt->execute([...$ids, $_SESSION['user']['user_id']]);
    }


    // Upload sécurisé
    public static function uploadFile($pdo, array $file, string $path): string {
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

    public static function getByPath($pdo, string $path): array {
        $stmt = $pdo->prepare("SELECT * FROM document WHERE path = :path AND owner = :owner");
        $stmt->execute([
            ":path" => $path,
            ":owner" => $_SESSION['user']['user_id']
        ]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public Static function togleFavorite($pdo, int $id): void
    {
        $stmt = $pdo->prepare("SELECT favorite FROM document WHERE id = :id");
        $stmt->execute([":id" => $id]);
        $favorite = $stmt->fetchColumn();
        $favorite = ($favorite==1) ? 0 : 1;

        $stmt = $pdo->prepare("UPDATE document SET favorite = :favorite WHERE id = :id");
        $stmt->execute([
            ":favorite" => $favorite,
            ":id" => $id
        ]);
    }

    public static function rename($pdo, int $id, string $newName, string $path): void
    {
        $newPath = $path . $newName;
        $stmt = $pdo->prepare("UPDATE document SET name = :newname, path = :newpath WHERE id = :id");
        $stmt->execute([
            ":newpath" => $newPath,
            ":newname" => $newName,
            ":id" => $id
        ]);
    }


    public static function search($pdo, ?string $search): array
    {
        // 1. On prépare la base de la requête
        $sql = "SELECT id FROM document WHERE owner = :owner";
        $params = [":owner" => $_SESSION['user']['user_id']];

        // 2. Si une recherche est fournie, on ajoute le filtre LIKE
        if (!empty($search)) {
            $sql .= " AND name LIKE :search";
            $params[":search"] = "%" . $search . "%";
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        // 3. On extrait uniquement la colonne 'id' dans un tableau simple
        return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'id');
    }

}
