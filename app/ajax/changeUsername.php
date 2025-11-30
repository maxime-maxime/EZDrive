<?php
session_start();
require_once '../Database.php';
require_once '../Config/config.php';
global $invalidChars;

if(!isset($_SESSION['user']['user_id'])){
    header('Location: ../login.php');
}
echo $_GET['username'];
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $username = $_GET['username']??'';
    if($username == '' || $username == 'undefined'){
        echo"Veuillez entrer un nom d'utilisateur !";
        exit;
    }
    echo $username;

    if (strpbrk($username, implode('', $invalidChars)) !== false) {
        echo"Ce nom d'utilisateur est problématique pour nous !";
    }
    else{
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT* FROM user WHERE name = :user");
        $stmt->execute([":user" => $username]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($userData) {
            echo"Ce nom d'utilisateur est déjà utilisé, peut être même par vous...";
        }
        else {

            $stmt = $pdo->prepare("UPDATE user SET name = :name WHERE user_id = :id");

            $stmt->execute([
                'id' => $_SESSION['user']['user_id'],
                'name' => $username
            ]);
        }
    }
    $_SESSION['user']['name'] = $username;
    echo"success";
}