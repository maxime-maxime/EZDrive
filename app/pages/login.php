<?php
session_start();
require_once '../Database.php';
$logout=false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $pdo = Database::getConnection();
    $stmt = $pdo->prepare("SELECT psw, user_id, last_theme FROM user WHERE name = :user");
    $stmt->execute([":user" => $username]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    $lastTheme = $userData['last_theme']??'default';
    if ($userData && password_verify($password, $userData['psw'])) {
        $_SESSION['user'] = [
            'user_id' => $userData['user_id'],
            'name' => $username,
        ];
        echo $lastTheme;
        header('Location: '.$lastTheme.'/index.php?folderId=root');
        exit;
    } else {
        $error = 'Nom d\'utilisateur ou mot de passe incorrect.';
    }
} else {
    $message = 'Veuillez vous connecter.';
}
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
        <h1>Connexion</h1>
        <form method="post">
            <label for="username">Nom d'utilisateur:</label>
            <input type="text" id="username" name="username" required>

            <label for="password">Mot de passe:</label>
            <input type="password" id="password" name="password" required>

            <button type="submit">Se connecter</button>
            <p><a href="signup.php">S'inscrire</a></button></p>
        </form>
        <div class="message">
        <?php if (isset($error)) echo "<p style='color: red;'>$error</p>"; ?>
        <?php if (isset($message)) echo "<p>$message</p>"; ?>
        </div>
        <div id="notification">
        Vous avez été déconnecté
        </div>
</div>


