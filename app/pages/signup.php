<?php
session_start();
require_once '../Database.php';
require_once '../Config/config.php';
global $invalidChars;
$logout=false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username']??'';
    $password = $_POST['password']??'';

    if (strpbrk($username, implode('', $invalidChars)) !== false) {
        $error = "Ce nom d'utilisateur est problématique pour nous !";
    }
        else{
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT* FROM user WHERE name = :user");
        $stmt->execute([":user" => $username]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($userData) {
            $error = "Ce nom d'utilisateur est déjà utilisé, peut être même par vous...";
        }
        else{
            $hashedpsw = password_hash($password, PASSWORD_DEFAULT);


            $stmt = $pdo->prepare("INSERT INTO user (name, psw)  VALUES (:name, :hashedpsw)");

            $stmt->execute([
                'name' => $username,
                'hashedpsw' => $hashedpsw
            ]);

            $stmt = $pdo->prepare("SELECT psw, user_id FROM user WHERE name = :user");
            $stmt->execute([":user" => $username]);
            $userData = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($userData && password_verify($password, $userData['psw'])) {
                $_SESSION['user'] = [
                    'user_id' => $userData['user_id'],
                    'name' => $username,
                ];
                header('Location: index.php?folderId=root');
                exit;

            } else {
                $error = "OUPS, une erreur s'est produite...";
            }
        }
    }}
    else {
    $message = 'Inscrivez-vous';}
?>

<script>
    window.addEventListener('DOMContentLoaded', () => {
        <?php if (isset($_GET['ciao']) && $_GET['ciao'] == 'ciao') { ?>
        const notification = document.getElementById('notification');
        notification.classList.add('show');   // déclenche la transition
        setTimeout(() => {
            notification.classList.remove('show'); // disparaît après 3s
        }, 3000);
        <?php } ?>
    });
</script>




<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/login.css">
    <title>Connexion</title>
</head>
<div class="page">
    <div class="login-box">
        <h1>Inscription</h1>
        <form method="post">
            <label for="username">Nom d'utilisateur:</label>
            <input type="text" id="username" name="username" required>

            <label for="password">Mot de passe:</label>
            <input type="password" id="password" name="password" required>

            <button type="submit">Se connecter</button>
            <p><a href="login.php">J'ai déjà un compte</a></button></p>
        </form>
        <div class="message">
            <?php if (isset($error)) echo "<p style='color: red;'>$error</p>"; ?>
            <?php if (isset($message)) echo "<p>$message</p>"; ?>
        </div>
        <div id="notification">
            Vous avez été déconnecté
        </div>
    </div>


