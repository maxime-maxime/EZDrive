<?php
session_start();
require_once '../../Database.php';

$userThemes = [];

if(isset($_SESSION['user']['user_id'])) {
    $pdo = Database::getConnection();
    $userId = $_SESSION['user']['user_id']; // Variable locale pour la clarté et la réutilisation

    // 1. Vérification du nom d'utilisateur (Logique anti-dédoublement/fraude)
    $stmt = $pdo->prepare("SELECT name FROM user WHERE user_id = :id");
    $stmt->execute([":id" => $userId]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($userData['name'] != $_SESSION['user']['name']){
        echo $userData['name'];
        echo $_SESSION['user']['name'];
        header("Location: ../login.php");
        exit; // Arrêter l'exécution après la redirection
    }

    // 2. Récupération des thèmes de l'utilisateur
    $stmt = $pdo->prepare("SELECT themes FROM user WHERE user_id = :id");
    $stmt->execute([":id" => $userId]);

    // CORRECTION MAJEURE: Récupérer le résultat dans une variable dédiée
    $themeResult = $stmt->fetch(PDO::FETCH_ASSOC);

    // CORRECTION: Utiliser la variable $themeResult, et '[]' si NULL
    $jsonThemeString = $themeResult['themes'] ?? '[]';

    // Décodage en tableau PHP (liste numériquement indexée)
    $decodedThemes = json_decode($jsonThemeString, true);

    // Vérifier si le décodage a réussi et que c'est un tableau
    if (is_array($decodedThemes)) {
        $userThemes = $decodedThemes;
    }
    $full_path = __FILE__;
    $directory_path = dirname($full_path);
    $dynamic_segment = basename($directory_path);
    if(!in_array($dynamic_segment, $userThemes)){
        header("Location: ../index.php");
    }
}
else {
    header("Location: ../login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Drive</title>
    <link rel="stylesheet" href="../../css/sobre.css">
    <script src="../../script/drive.js" defer></script>
    <link
</head>
<body>

<header class="header">
    <div class="left">
        <div class="logo">EZDrive</div>
    </div>

    <div class="center">
        <div class="search-bar">
            <input type="text" placeholder="en vous souhaitant un joyeux noel...">
            <button class="search">Rechercher</button>
        </div>
    </div>

    <div class="right user-actions">
        <button class="selectAll"><img src="../../ressources/sobre/select_all.png" alt="Tout Sélectionner" /></button>
        <button class="create_folder"><img src="../../ressources/sobre/create_folder.png" alt="Créer" /></button>
        <button class="upload"><img src="../../ressources/sobre/upload.png" alt="Importer" /></button>
        <button class="download"><img src="../../ressources/sobre/download.png" alt="Exporter" /></button>

        <button class="profil"><?php echo $_SESSION['user']['name']; ?></button>
        <button class="logout">Déconnexion</button>
    </div>
</header>



<div class="page-container">
    <aside class="sidebar">
        <h3>Types de fichiers</h3>
        
        <ul>
            <li>
                <input type="checkbox" id="image" class="filter">
                <label for="image">Images</label>
            </li>
            <li>
                <input type="checkbox" id="media" class="filter">
                <label for="media">Médias</label>
            </li>
            <li>
                <input type="checkbox" id="document" class="filter">
                <label for="document">Documents</label>
            </li>
            <li>
                <input type="checkbox" id="divers" class="filter">
                <label for="divers">Divers</label>
            </li>
        </ul>

        <h3>Personnalisé</h3>
        <ul>
            <li>
                <input type="checkbox" id="favorite" class="filter">
                <label for="favorite">Favoris</label>
            </li>
            <li>
                <input type="checkbox" id="shared" class="filter">
                <label for="shared">Partagés</label>
            </li>
            <li>
                <input type="checkbox" id="recent" class="filter">
                <label for="recent">Récents</label>
            </li>
        </ul>

    </aside>

    <main class="main-content">
    </main>

</div><div class="popup" id="profilInfo" role="dialog" aria-modal="true" aria-labelledby="popupTitle">
    <div class="popup-header">
        <h2 id="popupTitle">Mon Profil</h2> <button class="close-btn" aria-label="Fermer la fenêtre">X</button>
    </div>

    <div class="popup-content">
        <h3><?php echo $_SESSION['user']['name']; ?></h3>

        <form id="username-form">
            <label for="username-input" class="visually-hidden">Changer de nom d'utilisateur : </label>
            <input type="text" id="username-input" placeholder="un exemple de nom d'utilisateur...">
            <button class="userName" type="submit">changer</button>
        </form>

        <?php if (!empty($userThemes)): ?>
            <form>
                <label for="themes-select">Mes thèmes :</label>
                <select name="themes" id="themes-select">
                    <option value="sobre">sobre</option>
                    <option value="default">default</option>
                    <?php foreach ($userThemes as $theme):
                        if ($theme !== 'default' && $theme !== 'sobre'): ?>
                            <option value="<?= htmlspecialchars($theme) ?>"><?= htmlspecialchars($theme) ?></option>
                        <?php endif; endforeach; ?>
                </select>
            </form>
        <?php endif; ?>


        <button class="DeleteAcct">Supprimer le compte</button>
    </div>

</div>

<div class="context-menu">
    <ul class="context">
        <li class="contextLink" id="copy" ><img src="../../ressources/sobre/copy.png" alt="copier" /><span class="contextLabel">copier</span></li>
        <li class="contextLink" id="paste"><img src="../../ressources/sobre/paste.png" alt="coller" /><span class="contextLabel">coller</span></li>
        <li class="contextLink" id="cut"><img src="../../ressources/sobre/cut.png" alt="couper" /><span class="contextLabel">couper</span></li>
        <li class="contextLink" id="properties"><img src="../../ressources/sobre/properties.png" alt="propriétés" /><span>propriétés</span></li>
        <li class="contextLink" id="delete"><img src="../../ressources/sobre/delete.png" alt="supprimer" /><span class="contextLabel">supprimer</span></li>
        <li class="contextLink" id="rename"><img src="../../ressources/sobre/rename.png" alt="renomer" /><span class="contextLabel">renommer</span></li>
        <li class="contextLink" id="setFavorite"><img src="../../ressources/sobre/favorite.png" alt="favoris" /><span class="contextLabel">favori</span></li>
    </ul>
</div>

<div class="context-menu-bis">
    <ul class="context">
        <li class="contextLink" id="sort_name" ><img src="../../ressources/sobre/sort_alphabet.png" alt="asc" /><span class="contextLabel">trier par nom</span></li>
        <li class="contextLink" id="sort_date"><img src="../../ressources/sobre/calendar.png" alt="date" /><span class="contextLabel">trier par date</span></li>
    </ul>
</div>

<div class="popup" id="fileInfo" role="dialog" aria-modal="true" aria-labelledby="popupTitle">
    <div class="popup-header">
        <h2 id="popupTitle">Propriétés</h2>
        <button class="close-btn" aria-label="Fermer la fenêtre">X</button>
    </div>
    <div class="popup-content">
        <table>
        </table>
    </div>
</div>

</body>
</html>

