// ====================================================================
// 1. INITIALISATION & VARIABLES GLOBALES
// ====================================================================

let ctrl = false; // Ctrl press
let a = false;    // 'a' press
let q = false;    // 'w' press

const fileInput = document.createElement('input');
fileInput.type = 'file';
fileInput.multiple = true;
fileInput.webkitdirectory = true;

// Paramètres d'URL qui définissent un TYPE de contenu (filtres)
const URLparams = ['image', 'media', 'divers', 'document'];

// SÉLECTEURS GLOBALS
const menu_principal = document.querySelector('.context-menu');
const menu_secondaire = document.querySelector('.context-menu-bis');

// Chemins et infos de base
const contentPath = '../../../bdd/content/';

// Les valeurs de ces variables ne sont lues qu'au début.
const folderId = getParamFromUrl("folderId");


// ====================================================================
// 2. UTILS & HELPERS
// ====================================================================

/**
 * Extrait le segment dynamique de l'URL (ex: 'images' dans /pages/images/index.php).
 * @returns {string | null}
 */
function getDynamicSegmentFromUrl() {
    const path = window.location.pathname;
    const segments = path.split('/').filter(Boolean);
    const pagesIndex = segments.indexOf('pages');

    if (pagesIndex !== -1 && segments.length > pagesIndex + 1) {
        return segments[pagesIndex + 1];
    }

    return null;
}

/**
 * Extrait la valeur d'un paramètre donné de l'URL courante.
 * Utilise l'API URLSearchParams.
 * @param {string} param - Le nom du paramètre (clé).
 * @returns {string | null} La valeur du paramètre ou null s'il est absent.
 */
function getParamFromUrl(param){
    const url = new URL(window.location.href);
    return url.searchParams.get(param);
}

/**
 * Met à jour un paramètre dans l'URL sans recharger la page, puis appelle loadFiles().
 * Si value est null ou false, le paramètre est supprimé.
 * @param {string} key - Le nom du paramètre à modifier.
 * @param {string | boolean | null} value - La nouvelle valeur.
 */
function updateUrlAndLoad(key, value) {
    const url = new URL(window.location.href);

    // Supprimer le paramètre si la valeur est fausse, nulle, ou 'false'
    if (value === null || value === false || value === 'false') {
        url.searchParams.delete(key);
    } else {
        // Pour les filtres binaires, la valeur est 'true'
        url.searchParams.set(key, (typeof value === 'boolean') ? 'true' : value);
    }

    // Mettre à jour l'URL sans recharger la page
    window.history.pushState({ path: url.href }, '', url.href);

    loadFiles();
}

/**
 * Met à jour les paramètres de tri (orderType et order) dans l'URL et relance loadFiles().
 * @param {string} newSortField - Le champ de tri à utiliser (ex: 'name', 'updated_at').
 */
function handleSorting(newSortField) {
    const url = new URL(window.location.href);

    // PHP attend 'orderType' pour l'ordre (ASC/DESC) et 'order' pour le champ ('name'/'updated_at')
    const currentOrderField = url.searchParams.get('order'); // Champ de tri actuel

    if (currentOrderField === newSortField) {
        // Le tri est déjà actif sur ce champ : on inverse l'ordre
        const currentOrderType = url.searchParams.get('orderType'); // Ordre actuel (ASC/DESC)
        const newOrderType = (currentOrderType === 'ASC' || currentOrderType === 'asc') ? 'DESC' : 'ASC';
        url.searchParams.set('orderType', newOrderType);
    } else {
        // Nouveau tri : définir le champ et l'ordre par défaut 'ASC'
        url.searchParams.set('order', newSortField);
        url.searchParams.set('orderType', 'ASC');
    }

    // Mettre à jour l'URL sans recharger
    window.history.pushState({ path: url.href }, '', url.href);

    // Charger le contenu avec les nouveaux paramètres de tri
    loadFiles();
}

function logout() {
    fetch(`../../ajax/logout.php`).then(() => window.location.href = '../../pages/login.php?ciao=ciao');
}

/**
 * Positionne le menu contextuel pour qu'il ne dépasse pas les bords de l'écran.
 */
const update_menu_pos = (element, x, y) => {
    if (!element) return;
    const maxLeftValue = window.innerWidth - element.offsetWidth;
    const maxTopValue = window.innerHeight - element.offsetHeight;
    element.style.left = `${Math.min(maxLeftValue, x)}px`
    element.style.top = `${Math.min(maxTopValue, y)}px`
};


// ====================================================================
// 3. FILE LOADING / RENDERING
// ====================================================================

/**
 * Charge les propriétés d'un fichier ou dossier sélectionné et les affiche dans le popup #fileInfo.
 * @param {HTMLElement} icon - L'élément icône sélectionné (.file-icon ou .folder-icon).
 */
function loadProperties(icon) {
    const isFolder = icon.classList.contains('folder-icon');
    const folderId = isFolder ? icon.dataset.id : null;
    const fileId = !isFolder ? icon.dataset.id : null;

    const url = `../../ajax/getInfos.php?folderId=${encodeURIComponent(folderId || null)}&fileId=${encodeURIComponent(fileId || null)}`
    fetch(url)
        .then(res => res.json())
        .then(data => {
            console.log(data);
            const container = document.querySelector('#fileInfo .popup-content table');
            if (!container) return;

            container.innerHTML = '';
            // Remplissage du tableau avec les paires clé:valeur reçues par AJAX
            Object.entries(data).forEach(([key, value]) => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${key}</td>
                    <td>${value}</td>
                `;
                container.appendChild(row);
            });

            // Masquer les menus et afficher le popup
            document.querySelectorAll('.popup').forEach(i => i.classList.remove('show'));
            if (menu_principal) menu_principal.style.visibility = null;
            if (menu_secondaire) menu_secondaire.style.visibility = null;
            document.querySelector("#fileInfo").classList.add("show");
        })
        .catch(err => console.error('Erreur de chargement des propriétés:', err));
}


function loadFiles() {
    document.querySelectorAll('.popup').forEach(i => i.classList.remove('show'));

    let theme = getDynamicSegmentFromUrl();
    const previewPath = '../../../bdd/_thumbs/'+theme+'/';

    // Lire les paramètres d'URL (mis à jour par les filtres et le tri)
    const currentOrderType = getParamFromUrl('orderType') || 'ASC';
    const currentOrderField = getParamFromUrl('order') || 'name';

    // 1. COLLECTER LES CRITÈRES DE FILTRE ACTIFS
    const criteria = { type: [], favorite: null, shared: null, recent: null };

    URLparams.forEach(param => {
        const checkbox = document.getElementById(param);
        // Si la case à cocher existe et est cochée
        if (checkbox && checkbox.checked) {
            criteria.type.push(param);
        }
    });

    const fav = getParamFromUrl('favorite');
    const sha = getParamFromUrl('shared');
    const rec = getParamFromUrl('recent');

    criteria.favorite = fav?1:null;
    criteria.shared = sha?1:null;
    criteria.recent = rec?1:null;

    // 2. CONSTRUIRE LA QUERY STRING POUR L'API
    const params = new URLSearchParams({
        orderType: currentOrderType, // ASC/DESC
        order: currentOrderField,   // Champ: name, updated_at
        folderId: folderId || '',
    });

    // Ajouter les filtres 'type' sous forme de tableau (type[]=image&type[]=audio...)
    criteria.type.forEach(t => params.append('type[]', t));

    // Si d'autres critères sont ajoutés à l'objet 'criteria' plus tard (en dehors de 'type')
    Object.keys(criteria).forEach(key => {
        if (key !== 'type' && criteria[key]) params.append(key, criteria[key]);
    });

    // 3. FETCH ET RENDU
    fetch(`../../ajax/getToPrint.php?${params.toString()}`)
        .then(res => res.json())
        .then(data => {
            const container = document.querySelector('.main-content');
            container.innerHTML = '';

            // Rendu des Dossiers
            data.Folders.forEach(folder => {
                const div = document.createElement('div');
                div.className = 'folder-icon';
                div.dataset.id = folder.id;
                folder.favorite === 0 ? prev = previewPath + 'standard/folder.png' : prev = previewPath + 'favorite/folder.png';
                div.innerHTML = `<span class="icon"><img src="${prev}" alt="folder" class="icon"></span><span class="name">${folder.name}</span>`;
                container.appendChild(div);
            });

            // Rendu des Fichiers
            data.Files.forEach(file => {
                const div = document.createElement('div');
                div.className = 'file-icon';
                div.dataset.id = file.id;
                file.favorite === 0 ? prev = previewPath + 'standard' + '/' + file.preview : prev = previewPath + 'favorite' + '/' + file.preview;
                div.innerHTML = `<span class="icon"><img src="${prev}" alt="file" class="icon"></span><span class="name">${file.name}</span>`;
                container.appendChild(div);
            });
        })
        .catch(err => console.error(err));
}


// ====================================================================
// 4. UPLOAD
// ====================================================================

fileInput.addEventListener('change', async() => {
    const files = Array.from(fileInput.files);
    await uploadFiles(files);
});

const dropzone = document.querySelector('.page-container');
dropzone.addEventListener('dragover', (e) => {
    e.preventDefault();
    dropzone.classList.add('dragover');
});

dropzone.addEventListener('dragleave', (e) => {
    e.preventDefault();
    dropzone.classList.remove('dragover');
});

dropzone.addEventListener('drop', async(e) => {
    e.preventDefault();
    dropzone.classList.remove('dragover');
    const files = Array.from(e.dataTransfer.files);
    await uploadFiles(files);
});

async function uploadFiles(files){
    const token = Math.random().toString(36).slice(2, 10);
    // Récupère l'ID du dossier actuel pour l'upload
    const currentFolderId = getParamFromUrl("folderId");

    for (const file of files) {
        const form = new FormData();
        form.append("file", file);
        form.append("meta", JSON.stringify({
            name: file.name,
            size: file.size,
            type: file.type,
            lastModified: file.lastModified,
            webdir: file.webkitRelativePath || "",
            folderId: currentFolderId, // Utilise l'ID du dossier actuel
            token
        }));
        try {
            const response = await fetch("../../ajax/upload.php", { method: "POST", body: form });
            // const text = await response.text();
            // console.log(`Fichier envoyé : ${file.name}`, text);
            loadFiles(); // Recharger après chaque fichier ou après la boucle, selon la préférence
        }
        catch (err) {
            console.error(`Erreur pour ${file.name} :`, err);
        }
    }
}


// ====================================================================
// 5. INPUT LISTENERS (KEYBOARD & MOUSE)
// ====================================================================

// Ctrl + A gestion
document.addEventListener('keydown', e => {
    const key = e.key.toLowerCase();
    if (key === 'control') {
        ctrl = true;
        document.querySelectorAll('.file-icon, .folder-icon').forEach(i => i.classList.add('ctrl'));
    } else if (key === 'a') {
        a = true;
    } else if (key === 'q') {
        q = true;
    } else if (key === 'escape') {
        document.querySelectorAll('.popup').forEach(i => i.classList.remove('show'));
    }

    if (ctrl && q) {
        document.querySelector('.download').click();
        ctrl = false;
        document.querySelectorAll('.file-icon, .folder-icon').forEach(i => i.classList.remove('ctrl'));
        q = false;
    }
    // SÉLECTIONNER TOUT (Ctrl + A)
    if (ctrl && a) {
        e.preventDefault(); // Empêche la sélection de texte native du navigateur
        document.querySelectorAll('.file-icon, .folder-icon').forEach(i => i.classList.add('show'));
    }
});

document.addEventListener('keyup', e => {
    const key = e.key.toLowerCase();

    if (key === 'control') {
        ctrl = false;
        document.querySelectorAll('.file-icon, .folder-icon').forEach(i => i.classList.remove('ctrl'));
    } else if (key === 'a') {
        a = false;
    } else if (key === 'q') {
        q = false;
    } else if (key === 'delete' || key === 'supr') {
        const icons = document.querySelectorAll('.file-icon.show, .folder-icon.show');
        if (icons.length > 0) {
            deleteContent(icons);
        }
    }
});


// Sélection d'icônes (Clic gauche)
document.body.addEventListener('click', e => {
    if (e.button === 0){
        // Cacher les menus contextuels
        if (menu_principal) menu_principal.style.visibility = null;
        if (menu_secondaire) menu_secondaire.style.visibility = null;

        // Si le clic est à l'intérieur d'un popup, on ne fait rien
        if (e.target.closest('.popup')) {
            return;
        }
        document.querySelectorAll('.popup').forEach(i => i.classList.remove('show'));

        const icon = e.target.closest('div.file-icon, div.folder-icon');
        const icons = document.querySelectorAll('.file-icon, .folder-icon');

        icons.forEach(i => i.classList.remove('menuSelected', 'multiSelected'));

        if (!icon) {
            // Clic sur le fond : désélectionner tout
            icons.forEach(i => i.classList.remove('show'));
        } else if (!ctrl) {
            // Clic simple sans Ctrl : sélectionner un seul élément
            icons.forEach(i => i.classList.remove('show'));
            icon.classList.add('show');
        } else {
            // Clic avec Ctrl : basculer l'état de l'icône
            icon.classList.toggle('show');
        }
    }
});

// Double-clic sur icône (Navigation dans les dossiers)
document.body.addEventListener('dblclick', e => {
    const icon = e.target.closest('div.file-icon, div.folder-icon');
    if (!icon || !icon.classList.contains('folder-icon')) return;

    // 1. Définir le nouveau folderId
    const nouveauFolderId = icon.dataset.id;

    // 2. Construire la nouvelle URL
    const url = new URL(window.location.href);
    url.searchParams.set('folderId', nouveauFolderId);

    // La redirection utilise les autres paramètres déjà présents dans l'URL (sort, type, etc.)
    window.location.href = url.href;
});

// Menu contextuel (Clic droit)
document.addEventListener('contextmenu', (ev) => {
    ev.preventDefault();
    const icon = ev.target.closest('div.file-icon, div.folder-icon');
    const icons = document.querySelectorAll('.file-icon, .folder-icon');

    // Réinitialisation de la sélection visuelle des menus
    icons.forEach(i => i.classList.remove('menuSelected','multiSelected'));
    const properties = document.getElementById('properties');
    const rename = document.getElementById('rename');

    // S'assurer que les éléments existent avant de manipuler les classes
    if (properties) properties.classList.remove('disabled');
    if (rename) rename.classList.remove('disabled');

    document.querySelectorAll('.popup').forEach(i => i.classList.remove('show'));

    if(icon){
        // Clic sur une icône (Menu principal)
        if (menu_secondaire) menu_secondaire.style.visibility = null;
        if (menu_principal) {
            update_menu_pos(menu_principal, ev.clientX, ev.clientY);
            menu_principal.style.visibility = 'visible';
        }

        // Gestion de la sélection multiple/simple au clic droit
        const selectedIcons = document.querySelectorAll('.file-icon.show, .folder-icon.show');
        if (icon.classList.contains('show') || selectedIcons.length === 0) {
            // Clic droit sur une icône déjà sélectionnée ou pas d'autres sélections
            selectedIcons.forEach(i => i.classList.add('multiSelected'));
        } else {
            // Clic droit sur une icône non sélectionnée alors que d'autres le sont
            // On désélectionne les autres pour se concentrer sur celle-ci
            selectedIcons.forEach(i => i.classList.remove('show'));
        }
        icon.classList.add('show', 'multiSelected', 'menuSelected');
    }
    else if (ev.target.closest('.main-content')){
        // Clic sur le fond (Menu secondaire)
        if (menu_principal) menu_principal.style.visibility = null;
        if (menu_secondaire) {
            update_menu_pos(menu_secondaire, ev.clientX, ev.clientY);
            menu_secondaire.style.visibility = 'visible';
        }
    }
    else {
        // Clic ailleurs : masquer tout
        if (menu_principal) menu_principal.style.visibility = null;
        if (menu_secondaire) menu_secondaire.style.visibility = null;
    }
});

function deleteContent(icons){
    const folders = [], files = [];
    icons.forEach(i => {
        i.classList.remove('show');
        if (i.classList.contains('folder-icon')) folders.push(i.dataset.id);
        else files.push(i.dataset.id);
    });

    const url = `../../ajax/delete.php?folders=${encodeURIComponent(folders.join(','))}&files=${encodeURIComponent(files.join(','))}`;
    fetch(url)
        .then(res => res.text())
        .then(() => loadFiles())
        .catch(err => console.error(err));
}


// ====================================================================
// 6. DOM CONTENT LOADED (Initialisation principale)
// ====================================================================

document.addEventListener('DOMContentLoaded', function() {

    // 1. INITIALISATION DE L'ÉTAT DES FILTRES (basé sur l'URL)
    const filters = document.querySelectorAll('.filter');
    filters.forEach(f => {
        if (getParamFromUrl(f.id) !== null) {
            f.checked = true;
        }
    });

    // 2. ÉCOUTEURS D'ACTIONS ET FILTRES

    // Gestion du changement de filtre (AJAX)
    filters.forEach(filter=>{
        filter.addEventListener('change', function() {
            const paramName = this.id;
            const isChecked = this.checked;
            updateUrlAndLoad(paramName, isChecked);
        });
    });

    // --- LOGIQUE DE TRI ---

    const sortNameElement = document.querySelector('#sort_name');
    if (sortNameElement) {
        sortNameElement.addEventListener('click', (e) => {
            e.preventDefault();
            handleSorting('name');
        });
    }

    const sortDateElement = document.querySelector('#sort_date'); // ID probable dans votre HTML
    if (sortDateElement) {
        sortDateElement.addEventListener('click', (e) => {
            e.preventDefault();
            handleSorting('updated_at');
        });
    }

    // --- GESTION DES POPUPS ET BOUTONS D'ACTION ---

    // Bouton PROFIL : ouvre le popup Profil
    document.querySelector('.profil').addEventListener('click', (e) => {
        e.stopPropagation();
        if (menu_principal) menu_principal.style.visibility = null;
        if (menu_secondaire) menu_secondaire.style.visibility = null;
        document.querySelectorAll('.popup').forEach(i => i.classList.remove('show'));
        document.getElementById('profilInfo').classList.add('show');
    });

    // Bouton FERMER (des popups)
    document.querySelectorAll('.popup .close-btn').forEach(button => {
        button.addEventListener('click', (e) => {
            e.stopPropagation();
            e.target.closest('.popup').classList.remove('show');
        });
    });

    // Bouton PROPRIÉTÉS : ouvre le popup #fileInfo
    document.querySelector('#properties').addEventListener('click', () => {
        const icon = document.querySelector('.file-icon.menuSelected, .folder-icon.menuSelected');
        if (icon) {
            loadProperties(icon);
        }
    });

    // [Autres écouteurs]
    document.querySelector('.download').addEventListener('click', () => {
        const icons = document.querySelectorAll('.file-icon.show, .folder-icon.show');
        const folders = [], files = [];
        icons.forEach(i => {
            i.classList.remove('show');
            if (i.classList.contains('folder-icon')) folders.push(i.dataset.id);
            else files.push(i.dataset.id);
        });

        const url = `../../ajax/download.php?folders=${encodeURIComponent(folders.join(','))}&files=${encodeURIComponent(files.join(','))}`;
        fetch(url)
            .then(res => res.json())
            .then(paths => {
                paths.forEach(path => {
                    const a = document.createElement('a');
                    a.href = contentPath + path;
                    a.download = '';
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                });
            })
            .catch(err => console.error(err));
    });

    document.querySelector('#setFavorite').addEventListener('click', () => {
        const icons = document.querySelectorAll('.file-icon.show, .folder-icon.show');
        const folders = [], files = [];
        icons.forEach(i => {
            if (i.classList.contains('folder-icon')) folders.push(i.dataset.id);
            else files.push(i.dataset.id);
        });
        const url = `../../ajax/setFavorite.php?folders=${encodeURIComponent(folders.join(','))}&files=${encodeURIComponent(files.join(','))}`;
        fetch(url)
            .then(res => res.text())
            .then(() => loadFiles())
            .catch(err => console.error(err));
    });

    document.querySelector('#rename').addEventListener('click', () => {
        const icons = document.querySelectorAll('.file-icon.menuSelected, .folder-icon.menuSelected');
        if (icons.length === 0) return;
        const newName = prompt('choisissez un nouveau nom :');
        if (!newName) return;
        const folders = {}, files = {};
        icons.forEach(i => {
            if (i.classList.contains('folder-icon')) folders[i.dataset.id] = newName;
            else files[i.dataset.id] = newName;
        });
        const url = `../../ajax/rename.php?parent_id=${getParamFromUrl("folderId")}&folders=${encodeURIComponent(JSON.stringify(folders))}&files=${encodeURIComponent(JSON.stringify(files))}`;
        fetch(url)
            .then(res => res.text())
            .then(() => loadFiles())
            .catch(err => console.error(err));
    });

    document.querySelector('.selectAll').addEventListener('click', (e) => {
        e.stopPropagation();
        document.querySelectorAll('.file-icon, .folder-icon').forEach(i => i.classList.add("show"));
    });

    document.querySelector('#delete').addEventListener('click', () => {
        const icons = document.querySelectorAll('.file-icon.multiSelected, .folder-icon.multiSelected, ' + '.file-icon.show.multiSelected, .folder-icon.show.multiSelected');
        deleteContent(icons);
    });

    document.querySelector('.create_folder').addEventListener('click', () => {
        const folderName = prompt('Nom du dossier :');
        if (folderName) {
            const url = `../../ajax/createFolder.php?name=${encodeURIComponent(folderName)}&parentId=${getParamFromUrl("folderId")}`;
            fetch(url)
                .then(res => res.text())
                .then((res) => {
                    console.log(res);
                    loadFiles()
                });
        }
    });

    document.querySelector('.upload').addEventListener('click', () => fileInput.click());
    document.querySelector('.logout').addEventListener('click', logout);

    // [Logique de Thèmes]
    const themesSelect = document.getElementById('themes-select');
    if (themesSelect) {
        themesSelect.addEventListener('change', function(e) {
            e.stopPropagation();
            const selectedTheme = this.value;

            // 1. Récupérer l'URL actuelle
            const currentUrl = new URL(window.location.href);
            let newSearchParams = currentUrl.searchParams.toString();

            fetch(`../../ajax/setLastTheme.php?last_theme=${selectedTheme}`);
            window.location.href = `/EZDrive/app/pages/${selectedTheme}/index.php?${newSearchParams}`;
        });
    }

    // Gestion de la mise à jour du nom d'utilisateur dans le popup Profil
    document.querySelector(".userName").addEventListener("click", function(e) {
        e.stopPropagation();
        const usernameInput = document.querySelector('#username-input');
        if (usernameInput && usernameInput.value) {
            fetch(`../../ajax/changeUsername.php?username=${encodeURIComponent(usernameInput.value)}`)
                .then(res => res.text())
                .then(data => {
                    console.log('Changement nom utilisateur:', data);
                    window.location.reload();
                });
        }
    });

    document.getElementById('username-form').addEventListener('submit', function(event) {
        event.stopPropagation()
        event.preventDefault();
    });

    document.querySelector('.DeleteAcct').addEventListener('click', function(event) {
        event.stopPropagation()
        event.preventDefault();
        // Logique de suppression de compte
    });

    // 3. CHARGEMENT INITIAL DES FICHIERS
    loadFiles();
});

// Empêcher l'ouverture de l'icône drag and drop
document.addEventListener('dragstart', (e) => {
    e.preventDefault();
});