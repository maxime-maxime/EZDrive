<?php
class OwnerController
{
    public static function getOwnerById($userId): array{
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM user WHERE user_id = :userId");
        $stmt->execute(['userId' => $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}