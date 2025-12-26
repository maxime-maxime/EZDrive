<?php

require '../Config/config.php';

class OwnerController
{
    public static function logout($pdo): void{
        session_destroy();
        exit;
    }
    public static function getOwnerById($pdo, $userId): array{
        $stmt = $pdo->prepare("SELECT * FROM user WHERE user_id = :userId");
        $stmt->execute(['userId' => $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function addTheme($pdo, $theme): void{
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

    public static function changeUsername($pdo, $username) :bool{
        $stmt = $pdo->prepare("SELECT* FROM user WHERE name = :user");
        $stmt->execute([":user" => $username]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($userData) {
            return false;
        }
        else {

            $stmt = $pdo->prepare("UPDATE user SET name = :name WHERE user_id = :id");

            $stmt->execute([
                'id' => $_SESSION['user']['user_id'],
                'name' => $username
            ]);
        }
        return true;
    }

    public static function setLastTheme($pdo, $lastTheme): void{
        $stmt = $pdo->prepare("UPDATE user SET last_theme = :last_theme WHERE user_id = :user_id");
        $stmt->execute([":last_theme" => $lastTheme, ":user_id" => $_SESSION['user']['user_id']]);
    }
}