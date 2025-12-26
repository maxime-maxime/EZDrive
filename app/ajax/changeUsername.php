<?php
session_start();
require_once '../Database.php';
require_once '../Controllers/SecurityController.php';
global $invalidChars;

    $pdo = Database::getConnection();

    if(SecurityController::checkAjax($pdo)){
    $username = $_GET['username']??'';
    if($username == '' || $username == 'undefined'){
        echo"Veuillez entrer un nom d'utilisateur !";
        exit;
    }
    if (strpbrk($username, implode('', $invalidChars)) !== false || strlen($username) > 10) {
        echo"Ce nom d'utilisateur est problématique pour nous !";
        $username = $_SESSION['user']['name'];
    }
    if(!OwnerController::changeUsername($pdo, $username)){
        echo"Ce nom d'utilisateur est déjà utilisé, peut être même par vous...";
        exit;
    };


    $_SESSION['user']['name'] = $username;
    echo"success";
}