<?php
session_start();
require_once '../Database.php';
require_once '../Controllers/SecurityController.php';

$pdo = Database::getConnection();

if(!SecurityController::checkAjax($pdo)){
    exit;
}

$lastTheme = $_GET['last_theme']??'default';

OwnerController::setLastTheme($pdo, $lastTheme);

