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
        header("Location: login.php");
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
        header("Location: index.php?folderId=root");
    }
}
else {
    header("Location: ../login.php");
    exit; // Arrêter l'exécution après la redirection
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Drive</title>
    <link rel="stylesheet" href="../../css/xxx.css">
    <link rel="preload" as="image" href="../../ressources/xxx/dicky_cursor.cur">
    <script src="../../script/drive.js" defer></script>
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
            <input type="text" placeholder="old vs young...">
            <button class="search">Rechercher</button>
        </div>
    </div>

    <div class="right user-actions">
        <button class="selectAll"><img src="../../ressources/default/selectAll.png" alt="Tout Sélectionner" /></button>
        <button class="create_folder"><img src="../../ressources/xxx/create_folder.png" alt="Créer" /></button>
        <button class="upload"><img src="../../ressources/xxx/upload.png" alt="Importer" /></button>
        <button class="download"><img src="../../ressources/xxx/download.png" alt="Exporter" /></button>

        <button class="profil"><?php echo $_SESSION['user']['name']; ?></button>
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
                    <option value="xxx">xxx</option>
                    <option value="default">default</option>
                    <?php foreach ($userThemes as $theme):
                        if ($theme !== 'default' && $theme !== 'xxx'): ?>
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
        <li class="contextLink" id="copy" ><img src="../../ressources/default/copy.png" alt="copier" /><span class="contextLabel">copier</span></li>
        <li class="contextLink" id="paste"><img src="../../ressources/default/paste.png" alt="coller" /><span class="contextLabel">coller</span></li>
        <li class="contextLink" id="cut"><img src="../../ressources/default/cut.png" alt="couper" /><span class="contextLabel">couper</span></li>
        <li class="contextLink" id="properties"><img src="../../ressources/default/properties.png" alt="propriétés" /><span>propriétés</span></li>
        <li class="contextLink" id="delete"><img src="../../ressources/default/delete.png" alt="supprimer" /><span class="contextLabel">supprimer</span></li>
        <li class="contextLink" id="rename"><img src="../../ressources/default/rename.png" alt="renomer" /><span class="contextLabel">renommer</span></li>
        <li class="contextLink" id="setFavorite"><img src="../../ressources/default/favorite.png" alt="favoris" /><span class="contextLabel">favori</span></li>
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

