<?php
session_start();
require_once '../Database.php';
if(isset($_SESSION['user']['user_id'])) {
    $pdo = Database::getConnection();
    $stmt = $pdo->prepare("SELECT name FROM user WHERE user_id = :id");
    $stmt->execute([":id" => $_SESSION['user']['user_id']]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($userData['name'] != $_SESSION['user']['name']){
        echo $userData['name'];
        echo $_SESSION['user']['name'];
        header("Location: login.php");
    }}
else {
    header("Location: login.php");
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Drive</title>
    <link rel="stylesheet" href="../css/drive.css">
    <link rel="stylesheet" href="../css/drive.css">
    <script src="../script/drive.js" defer></script>
    <link
</head>
<body>
<!-- Header -->
<header class="header">
    <div class="left">
        <div class="logo">PornDrive</div>
    </div>

    <div class="center">
        <div class="search-bar">
            <input type="text" placeholder="Rechercher...">
            <button class="search">Rechercher</button>
        </div>
    </div>

    <div class="right user-actions">
        <button class="delete"><img src="../ressources/delete.png" alt="Supprimer" /></button>
        <button class="create_folder"><img src="../ressources/create_folder.png" alt="Créer" /></button>
        <button class="import"><img src="../ressources/download.png" alt="Importer" /></button>
        <button class="export"><img src="../ressources/upload.png" alt="Exporter" /></button>
        <button class="profil">Profil</button>
        <button class="logout">Déconnexion</button>
    </div>
</header>



<!-- Conteneur principal -->
<div class="page-container">
    <!-- Sidebar fixe à gauche -->
    <aside class="sidebar">
        <h3>Types de fichiers</h3>
        <ul>
            <li>
                <input type="checkbox" id="filter-images" class="filter">
                <label for="filter-images">Images</label>
            </li>
            <li>
                <input type="checkbox" id="filter-videos" class="filter">
                <label for="filter-videos">Vidéos</label>
            </li>
            <li>
                <input type="checkbox" id="filter-documents" class="filter">
                <label for="filter-documents">Documents</label>
            </li>
            <li>
                <input type="checkbox" id="filter-audio" class="filter">
                <label for="filter-audio">Audio</label>
            </li>
        </ul>

        <h3>Personnalisé</h3>
        <ul>
            <li>
                <input type="checkbox" id="filter-favorites" class="filter">
                <label for="filter-favorites">Favoris</label>
            </li>
            <li>
                <input type="checkbox" id="filter-shared" class="filter">
                <label for="filter-shared">Partagés avec moi</label>
            </li>
            <li>
                <input type="checkbox" id="filter-recent" class="filter">
                <label for="filter-recent">Récents</label>
            </li>
        </ul>

    </aside>

    <!-- Contenu principal -->
    <main class="main-content">
    </main>

</div>
</body>
</html>
