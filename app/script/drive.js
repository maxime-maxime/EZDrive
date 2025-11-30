let ctrl = false; // Ctrl press
let a = false;    // 'a' press
let q = false;    // 'w' press

// Initialisation & Global Vars
const fileInput = document.createElement('input');
const context_menu = document.querySelector('.context');
const contentPath = '../../../bdd/content/';
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
    let theme = getDynamicSegmentFromUrl();
    const previewPath = '../../../bdd/_thumbs/'+theme+'/';
    const folderPreview = previewPath+ 'folder.png';
    const folderId = window.location.search.split("=")[1];
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


// --- ACTIONS (DOWNLOAD, DELETE, CREATE FOLDER) ---

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


document.querySelector('.delete').addEventListener('click', () => {
    const icons = document.querySelectorAll('.file-icon.show, .folder-icon.show');
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
});


document.querySelector('.create_folder').addEventListener('click', () => {
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
        document.querySelector('.delete').click();
    }
});

// Sélection d'icônes (Clic gauche)
document.body.addEventListener('click', e => {
    if (e.button === 0){
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
        context_menu.style.visibility = null ;
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

});


// --- INITIALISATION & CONFIGURATION ---

document.querySelector('.upload').addEventListener('click', () => fileInput.click());

// Filtres
document.querySelectorAll('.filter').forEach(filter => filter.addEventListener('change', loadFiles));

// Logout bouton
document.querySelector('.logout').addEventListener('click', logout);

// Charger les fichiers au chargement de la page
window.onload= loadFiles;

document.querySelector(".profil").addEventListener("click", () => {
    document.querySelector(".popup").classList.add("show");
});

document.querySelector("#copy").addEventListener("click", () => {
});

document.querySelector("#paste").addEventListener("click", () => {
});

document.querySelector("#properties").addEventListener("click", () => {
});


document.addEventListener('DOMContentLoaded', function() {
    const themesSelect = document.getElementById('themes-select');

    if (themesSelect) {
        themesSelect.addEventListener('change', function() {
            const selectedTheme = this.value;
            fetch(`../../ajax/setLastTheme.php?last_theme=${selectedTheme}`)
            window.location.href = '/EZDrive/app/pages/'+selectedTheme+'/index.php?folderId='+window.location.search.split("=")[1];
        });
    }})

document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('username-form').addEventListener('submit', function(event) {
        event.preventDefault();
    });

    document.querySelector('.DeleteAcct').addEventListener('click', function(event) {
        event.preventDefault();
    });

    document.querySelector('.close-btn').addEventListener('click', function(event) {
        document.querySelector('.popup').classList.remove('show');});
});

document.querySelector('.userName').addEventListener('click', function() {
    const usernameInput = document.querySelector('#username-input')
    console.log(usernameInput.value);
    fetch(`../../ajax/changeUsername.php?username=${encodeURIComponent(usernameInput.value)}`)
        .then(res => res.text())
        .then(data => {
            console.log('data : '+ data);
        });
    window.location.reload();
});