let ctrl = false; // Ctrl press
let a = false;    // 'a' press
let q = false;    // 'w' press

// Initialisation & Global Vars
const fileInput = document.createElement('input');
// NOTE: context_menu est défini ici, mais son querySelector n'est pas enveloppé,
// il est supposé être un élément statique et présent dès l'analyse du script.
const context_menu = document.querySelector('.context');
const contentPath = '../../../bdd/content/';
const folderId = window.location.search.split("=")[1];

fileInput.type = 'file';
fileInput.multiple = true;
fileInput.webkitdirectory = true;

// --- UTILS & HELPERS ---

function getDynamicSegmentFromUrl() {
    const path = window.location.pathname;
    const segments = path.split('/').filter(Boolean);
    const pagesIndex = segments.indexOf('pages');

    if (pagesIndex !== -1 && segments.length > pagesIndex + 1) {
        return segments[pagesIndex + 1];
    }

    return null;
}

function logout() {
    fetch(`../../ajax/logout.php`).then(() => window.location.href = '../../pages/login.php?ciao=ciao');
}

const update_menu_pos = (x, y) => {
    const maxLeftValue = window.innerWidth - context_menu.offsetWidth;
    const maxTopValue = window.innerHeight - context_menu.offsetHeight;

    context_menu.style.left = `${Math.min(maxLeftValue, x)}px`
    context_menu.style.top = `${Math.min(maxTopValue, y)}px`
};


// --- FILE LOADING / RENDERING ---

function loadFiles() {
    document.querySelectorAll('.popup')
        .forEach(i => i.classList.remove('show'));
    let theme = getDynamicSegmentFromUrl();
    const previewPath = '../../../bdd/_thumbs/'+theme+'/';
    const folderPreview = previewPath+ 'folder.png';
    const criteria = { type: [] };
    // Types
    if (document.getElementById('filter-images').checked) criteria.type.push("image");
    if (document.getElementById('filter-documents').checked) criteria.type.push("document");
    if (document.getElementById('filter-videos').checked) criteria.type.push("video");
    if (document.getElementById('filter-audio').checked) criteria.type.push("audio");

    // Filtres personnalisés
    if (document.getElementById('filter-favorites').checked) criteria.fav = "true";
    if (document.getElementById('filter-shared').checked) criteria.shared = "true";
    if (document.getElementById('filter-recent').checked) criteria.recent = "true";

    // Construire URL
    const params = new URLSearchParams({ orderType: 'ASC', order: 'name', folderId });
    criteria.type.forEach(t => params.append('type[]', t));
    Object.keys(criteria).forEach(key => { if (key !== 'type') params.append(key, criteria[key]); });

    fetch(`../../ajax/getToPrint.php?${params.toString()}`)
        .then(res => res.json())
        .then(data => {
            const container = document.querySelector('.main-content');
            container.innerHTML = '';

            data.Folders.forEach(folder => {
                const div = document.createElement('div');
                div.className = 'folder-icon';
                div.dataset.id = folder.id;
                div.innerHTML = `
                    <span class="icon"><img src="${folderPreview}" alt="folder" class="icon"></span>
                    <span class="name">${folder.name}</span>
                `;
                container.appendChild(div);
            });

            data.Files.forEach(file => {
                const preview = previewPath + file.preview;
                console.log(preview);
                const div = document.createElement('div');
                div.className = 'file-icon';
                div.dataset.id = file.id;
                div.innerHTML = `
                    <span class="icon"><img src="${preview}" alt="file" class="icon"></span>
                    <span class="name">${file.name}</span>
                `;
                container.appendChild(div);
            });
        })
        .catch(err => console.error(err));
}


// --- UPLOAD ---

fileInput.addEventListener('change', async () => {
    const token = Math.random().toString(36).slice(2, 10);
    const folderId = new URLSearchParams(window.location.search).get("folderId");
    const files = Array.from(fileInput.files);

    for (const file of files) {
        const form = new FormData();
        form.append("file", file);
        form.append("meta", JSON.stringify({
            name: file.name,
            size: file.size,
            type: file.type,
            lastModified: file.lastModified,
            webdir: file.webkitRelativePath || "",
            folderId,
            token
        }));

        try {
            const response = await fetch("../../ajax/upload.php", { method: "POST", body: form });
            const text = await response.text();
            console.log(`Fichier envoyé : ${file.name}`, text);
            loadFiles();
        } catch (err) {
            console.error(`Erreur pour ${file.name} :`, err);
        }
    }
});


// --- INPUT LISTENERS (KEYBOARD & MOUSE) ---

// Ctrl + A gestion
document.addEventListener('keydown', e => {
    const key = e.key.toLowerCase();
    if (key === 'control') {
        ctrl = true;
        document.querySelectorAll('.file-icon, .folder-icon')
            .forEach(i => i.classList.add('ctrl'));
    } else if (key === 'a') {
        a = true;
    } else if (key === 'q') {
        q = true;
    } else if (key === 'escape') {
        document.querySelectorAll('.popup')
            .forEach(i => i.classList.remove('show'));
    }

    if (ctrl && q) {
        document.querySelector('.download').click();
        ctrl = false;
        document.querySelectorAll('.file-icon, .folder-icon')
            .forEach(i => i.classList.remove('ctrl'));
        q = false;}
    if (ctrl && a)
        document.querySelectorAll('.file-icon, .folder-icon')
            .forEach(i => i.classList.add('show'));
});

document.addEventListener('keyup', e => {
    const key = e.key.toLowerCase();

    if (key === 'control') {
        ctrl = false;
        document.querySelectorAll('.file-icon, .folder-icon')
            .forEach(i => i.classList.remove('ctrl'));
    } else if (key === 'a') {
        a = false;
    } else if (key === 'q') {
        q = false;
    } else if (key === 'delete' || key === 'supr') {
        const icons = document.querySelectorAll('.file-icon.show, .folder-icon.show');
        deleteContent(icons);
    }
});


// Sélection d'icônes (Clic gauche)
document.body.addEventListener('click', e => {
    if (e.button === 0){
        context_menu.style.visibility = null ;
        if (e.target.closest('.popup')) {
                return;
            }
        document.querySelectorAll('.popup').forEach(i => i.classList.remove('show'));
        const icon = e.target.closest('div.file-icon, div.folder-icon');
        const icons = document.querySelectorAll('.file-icon, .folder-icon');
        icons.forEach(i => i.classList.remove('menuSelected', 'multiSelected'));
        if (!icon) {
            icons.forEach(i => i.classList.remove('show'));
        } else if (!ctrl) {
            icons.forEach(i => i.classList.remove('show'));
            icon.classList.add('show');
        } else {
            icon.classList.toggle('show');
        }
    }
});

// Double-clic sur icône
document.body.addEventListener('dblclick', e => {
    const icon = e.target.closest('div.file-icon, div.folder-icon');
    if (!icon) return;
    if (icon.classList.contains('folder-icon')) {
        window.location.href = `../../pages/${getDynamicSegmentFromUrl()}/index.php?folderId=${icon.dataset.id}`;
    } else fetchFileInfo(icon.dataset.id); // fonction existante
});

// Menu contextuel (Clic droit)
document.addEventListener('contextmenu', (ev) => {
    ev.preventDefault();
    const icon = ev.target.closest('div.file-icon, div.folder-icon');
    const icons = document.querySelectorAll('.file-icon, .folder-icon');
    icons.forEach(i => i.classList.remove('menuSelected','multiSelected'));
    document.getElementById('properties').classList.remove('disabled');
    document.getElementById('rename').classList.remove('disabled');
    document.querySelectorAll('.popup')
        .forEach(i => i.classList.remove('show'));

    if(icon){
        update_menu_pos(ev.clientX, ev.clientY);
        context_menu.style.visibility = 'visible';
        if(icon.classList.contains('show')){
            document.querySelectorAll('.file-icon.show, .folder-icon.show').forEach(i => {
                i.classList.add('multiSelected');
            });        icon.classList.add('menuSelected');
        }
        else{
            document.querySelectorAll('.file-icon.show, .folder-icon.show').forEach(i => {
                i.classList.remove('show');
            });
            icon.classList.add('multiSelected');
            icon.classList.add('menuSelected');
        }
    }
    else{
        document.querySelector(".context").style.visibility = null ;
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
        .then(files => {
            console.log(files);
            loadFiles();
        })
        .catch(err => console.error(err));
}

// --- INITIALISATION & CONFIGURATION ---

document.addEventListener('DOMContentLoaded', function() {

    // 1. ÉCOUTEURS D'ACTIONS PRINCIPALES (Menu contextuel et en-tête)

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
            .then(files => {
                console.log(files);
                files.forEach(path => {
                    console.log(contentPath +  path);
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
        console.log("here - SetFavorite fonctionne!");
        const icons = document.querySelectorAll('.file-icon.show, .folder-icon.show');
        const folders = [], files = [];
        icons.forEach(i => {
            if (i.classList.contains('folder-icon')) folders.push(i.dataset.id);
            else files.push(i.dataset.id);
        });
        const url = `../../ajax/setFavorite.php?folders=${encodeURIComponent(folders.join(','))}&files=${encodeURIComponent(files.join(','))}`;
        fetch(url)
            .then(res => res.text())
            .then(files => {
                console.log(files);
                loadFiles();
            })
            .catch(err => console.error(err));
    });

    document.querySelector('#rename').addEventListener('click', (e) => {
        const icons = document.querySelectorAll('.file-icon.menuSelected, .folder-icon.menuSelected');
        if (icons.length === 0) return;
        const newName = prompt('choisissez un nouveau nom :');
        if (!newName) return;
        const folders = {}, files = {};
        icons.forEach(i => {
            if (i.classList.contains('folder-icon')) folders[i.dataset.id] = newName;
            else files[i.dataset.id] = newName;
        });
        const url = `../../ajax/rename.php?parent_id=${window.location.search.split("=")[1]}&folders=${encodeURIComponent(JSON.stringify(folders))}&files=${encodeURIComponent(JSON.stringify(files))}`;
        fetch(url)
            .then(res => res.text())
            .then(files => {
                console.log(files);
            })
            .catch(err => console.error(err));
        loadFiles();
    });

    document.querySelector('.selectAll').addEventListener('click', (e) => {
        const icons = document.querySelectorAll('.file-icon, .folder-icon');
        icons.forEach(i => {e.stopPropagation();i.classList.add("show");console.log(i);});
    });

    document.querySelector('#delete').addEventListener('click', (e) => {
        const icons = document.querySelectorAll('.file-icon.multiSelected, .folder-icon.multiSelected, ' + '.file-icon.show.multiSelected, .folder-icon.show.multiSelected');
        deleteContent(icons);
    });

    document.querySelector('.create_folder').addEventListener('click', (e) => {
        const folderName = prompt('Nom du dossier :');
        if (folderName) {
            const url = `../../ajax/createFolder.php?name=${encodeURIComponent(folderName)}&parentId=${window.location.search.split("=")[1]}`;
            console.log(url);
            fetch(url)
                .then(res => res.text())
                .then(data => {
                    console.log('data : '+ data);
                    loadFiles();
                });
        }
    });

    // 2. ÉCOUTEURS D'ACTIONS SIMPLES ET FILTRES

    document.querySelector('.upload').addEventListener('click', () => fileInput.click());

    // Filtres
    document.querySelectorAll('.filter').forEach(filter => filter.addEventListener('change', loadFiles));

    // Logout bouton
    document.querySelector('.logout').addEventListener('click', logout);



    // Popup et profil
    document.querySelector(".profil").addEventListener("click", (e) => {
        e.stopPropagation();
        document.querySelector(".context").style.visibility = null ;
        document.querySelectorAll('.popup')
            .forEach(i => i.classList.remove('show'));
        document.querySelector("#profilInfo").classList.add("show");
    });


    document.querySelector("#properties").addEventListener("click", (e) => {
        const icon = document.querySelector('.menuSelected')
        e.stopPropagation();
        if(icon===null){
            document.querySelector(".context").style.visibility = null ;
            return;
        }
            let folderId = null;
            let fileId = null;
            if(icon.classList.contains('folder-icon'))folderId = icon.dataset.id;
            else fileId = icon.dataset.id;
        const url = `../../ajax/getInfos.php?folderId=${encodeURIComponent(folderId)}&fileId=${encodeURIComponent(fileId)}`
        fetch(url)
            .then(res => res.json())
            .then(data => {
                const container = document.querySelector('#fileInfo .popup-content table');
                container.innerHTML = '';
                Object.entries(data).forEach(([key, value]) => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${key}</td>
                        <td>${value}</td>
                    `;
                    container.appendChild(row);
                });
            });
        document.querySelectorAll('.popup')
            .forEach(i => i.classList.remove('show'));
        document.querySelector(".context").style.visibility = null ;

        document.querySelector("#fileInfo").classList.add("show");
    });
    document.querySelector("#fileInfo .close-btn").addEventListener("click",(e) =>{
        document.querySelector("#fileInfo").classList.remove("show");
    })

    document.querySelector("#copy").addEventListener("click", (e) => {
    });

    document.querySelector("#paste").addEventListener("click", (e) => {
    });


    document.querySelector(".userName").addEventListener("click", function(e) {
        e.stopPropagation();
        const usernameInput = document.querySelector('#username-input')
        console.log(usernameInput.value);
        fetch(`../../ajax/changeUsername.php?username=${encodeURIComponent(usernameInput.value)}`)
            .then(res => res.text())
            .then(data => {
                console.log('data : '+ data);
            });
        window.location.reload();
    });

    // Logique thèmes (était déjà dans un DOMContentLoaded)
    const themesSelect = document.getElementById('themes-select');
    if (themesSelect) {
        themesSelect.addEventListener('change', function(e) {
            e.stopPropagation();
            const selectedTheme = this.value;
            fetch(`../../ajax/setLastTheme.php?last_theme=${selectedTheme}`)
            window.location.href = '/EZDrive/app/pages/'+selectedTheme+'/index.php?folderId='+window.location.search.split("=")[1];
        });
    }

    // Logique formulaire et fermeture popup (était déjà dans un DOMContentLoaded)
    document.getElementById('username-form').addEventListener('submit', function(event) {
        event.stopPropagation()
        event.preventDefault();
    });

    document.querySelector('.DeleteAcct').addEventListener('click', function(event) {
        event.stopPropagation()
        event.preventDefault();
    });

    document.querySelector('.close-btn').addEventListener('click', function(event) {
        event.stopPropagation()
        document.querySelector('.popup').classList.remove('show');
    });

    // 3. CHARGEMENT INITIAL DES FICHIERS
    loadFiles();
});