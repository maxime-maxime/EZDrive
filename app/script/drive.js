let ctrl = false; // Ctrl press
let a = false;    // 'a' press
let q = false;    // 'w' press

// Créer input file unique pour upload
const fileInput = document.createElement('input');
const contentPath = '../../bdd/content/';
fileInput.type = 'file';
fileInput.multiple = true;


// Charger les fichiers et dossiers
function loadFiles() {
    const previewPath = '../../bdd/_thumbs/';
    const folderPreview = previewPath + 'xxx_folder.png';
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

    fetch(`../ajax/getToPrint.php?${params.toString()}`)
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

// Logout
function logout() {
    fetch(`../ajax/logout.php`).then(() => window.location.href = '../pages/login.php?ciao=ciao');
}

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

// Sélection d'icônes
document.body.addEventListener('click', e => {
    const icon = e.target.closest('div.file-icon, div.folder-icon');
    const icons = document.querySelectorAll('.file-icon, .folder-icon');
    if (!icon) {
        icons.forEach(i => i.classList.remove('show'));
    } else if (!ctrl) {
        icons.forEach(i => i.classList.remove('show'));
        icon.classList.add('show');
    } else {
        icon.classList.toggle('show');
    }
});

// Export des fichiers sélectionnés
document.querySelector('.download').addEventListener('click', () => {
    const icons = document.querySelectorAll('.file-icon.show, .folder-icon.show');
    const folders = [], files = [];
    icons.forEach(i => {
        i.classList.remove('show');
        if (i.classList.contains('folder-icon')) folders.push(i.dataset.id);
        else files.push(i.dataset.id);
    });

    const url = `../ajax/download.php?folders=${encodeURIComponent(folders.join(','))}&files=${encodeURIComponent(files.join(','))}`;
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

    const url = `../ajax/delete.php?folders=${encodeURIComponent(folders.join(','))}&files=${encodeURIComponent(files.join(','))}`;
    fetch(url)
        .then(res => res.text())
        .then(files => {
            console.log(files);
            loadFiles();
        })
        .catch(err => console.error(err));
});


// Upload
document.querySelector('.upload').addEventListener('click', () => fileInput.click());

fileInput.addEventListener('change', () => {
    const folderId = window.location.search.split("=")[1];
    const files = Array.from(fileInput.files);

    // Promesses pour attendre la lecture de tous les fichiers
    const readFiles = files.map(file => {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = e => {
                resolve({
                    name: file.name,
                    size: file.size,
                    extension: file.name.split('.').pop(),
                    type: file.type,
                    lastModified: file.lastModified,
                    content: e.target.result // base64
                });
            };
            reader.onerror = reject;
            reader.readAsDataURL(file); // ou readAsText si c'est du texte
        });
    });

    // Une fois que tous les fichiers sont lus
    Promise.all(readFiles).then(filesData => {
        fetch('../ajax/upload.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                folderId: folderId,
                files: filesData
            })
        })
            .then(res => res.text())
            .then(data => {
                console.log(data);
                loadFiles();
            });
    }).catch(err => console.error(err));
});

// Double-clic sur icône
document.body.addEventListener('dblclick', e => {
    const icon = e.target.closest('div.file-icon, div.folder-icon');
    if (!icon) return;
    if (icon.classList.contains('folder-icon')) {
        window.location.href = `../pages/index.php?folderId=${icon.dataset.id}`;
    } else fetchFileInfo(icon.dataset.id); // fonction existante
});

document.querySelector('.create_folder').addEventListener('click', () => {
    const folderName = prompt('Nom du dossier :');
    if (folderName) {
        const url = `../ajax/createFolder.php?name=${encodeURIComponent(folderName)}&parentId=${window.location.search.split("=")[1]}`;
        console.log(url);
        fetch(url)
            .then(res => res.text())
            .then(data => {
                console.log('data : '+ data);
                loadFiles();
            });
    }
});

// Filtres
document.querySelectorAll('.filter').forEach(filter => filter.addEventListener('change', loadFiles));

// Logout bouton
document.querySelector('.logout').addEventListener('click', logout);

// Charger les fichiers au chargement de la page
window.onload = loadFiles;
