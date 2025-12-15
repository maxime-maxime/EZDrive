<?php
class OwnerController
{
    public static function getOwnerById($userId): array{
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM user WHERE user_id = :userId");
        $stmt->execute(['userId' => $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function addTheme($theme): void{
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("SELECT themes FROM user WHERE user_id = :id");
        $stmt->execute([":id" => $_SESSION['user']['user_id']]);
        $themeResult = $stmt->fetch(PDO::FETCH_ASSOC);
        $jsonThemeString = $themeResult['themes'] ?? '[]';
        $decodedThemes = json_decode($jsonThemeString, true);

        echo $theme;

        if (is_array($decodedThemes)) {
            $userThemes = $decodedThemes;
            print_r($userThemes);

        if(!in_array($theme, $userThemes)){
            $userThemes[] = $theme;
            $jsonThemeString = json_encode($userThemes);
            $stmt = $pdo->prepare("UPDATE user SET themes = :themes WHERE user_id = :id");
            $stmt->execute([":themes" => $jsonThemeString, ":id" => $_SESSION['user']['user_id']]);
        }
    }}
}