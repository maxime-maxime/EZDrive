// ====================================================================
// 1. INITIALIZATION & GLOBAL VARIABLES
// ====================================================================

let ctrl = false; // Ctrl key status
let a = false;    // 'a' key status
let q = false;    // 'q' key status

const fileInput = document.createElement('input');
fileInput.type = 'file';
fileInput.multiple = true;
fileInput.webkitdirectory = true;

// URL parameters defining content TYPE (filters)
const URLparams = ['image', 'media', 'divers', 'document'];

// GLOBAL SELECTORS
const menu_principal = document.querySelector('.context-menu');
const menu_secondaire = document.querySelector('.context-menu-bis');

// Base paths
const contentPath = '../../../bdd/content/';

// Folder ID from URL (read only at start)
const folderId = getParamFromUrl("folderId");


// ====================================================================
// 2. UTILS & HELPERS
// ====================================================================

/**
 * Extracts the dynamic segment from the URL (e.g., 'images' from /pages/images/index.php).
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
 * Extracts the value of a given parameter from the current URL.
 * @param {string} param - The parameter name (key).
 * @returns {string | null} The parameter value or null if absent.
 */
function getParamFromUrl(param){
    const url = new URL(window.location.href);
    return url.searchParams.get(param);
}

/**
 * Updates an URL parameter without reloading the page, then calls loadFiles().
 * @param {string} key - The parameter name to modify.
 * @param {string | boolean | null} value - The new value.
 */
function updateUrlAndLoad(key, value) {
    const url = new URL(window.location.href);

    if (value === null || value === false || value === 'false') {
        url.searchParams.delete(key);
    } else {
        url.searchParams.set(key, (typeof value === 'boolean') ? 'true' : value);
    }

    window.history.pushState({ path: url.href }, '', url.href);
    loadFiles();
}

/**
 * Updates sort parameters (orderType and order) in the URL and relaunches loadFiles().
 * @param {string} newSortField - The sort field to use (e.g., 'name', 'updated_at').
 */
function handleSorting(newSortField) {
    const url = new URL(window.location.href);
    const currentOrderField = url.searchParams.get('order');

    if (currentOrderField === newSortField) {
        // Already sorting by this field: reverse order
        const currentOrderType = url.searchParams.get('orderType');
        const newOrderType = (currentOrderType === 'ASC' || currentOrderType === 'asc') ? 'DESC' : 'ASC';
        url.searchParams.set('orderType', newOrderType);
    } else {
        // New sort: set field and default order 'ASC'
        url.searchParams.set('order', newSortField);
        url.searchParams.set('orderType', 'ASC');
    }

    window.history.pushState({ path: url.href }, '', url.href);
    loadFiles();
}

/**
 * Logs out the user and redirects to the login page.
 */
function logout() {
    fetch(`../../ajax/logout.php`).then(() => window.location.href = '../../pages/login.php?ciao=ciao');
}

/**
 * Positions the context menu so it doesn't go off-screen.
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
 * Loads the properties of a selected file or folder and displays them in the #fileInfo popup.
 * @param {HTMLElement} icon - The selected icon element (.file-icon or .folder-icon).
 */
function loadProperties(icon) {
    const isFolder = icon.classList.contains('folder-icon');
    const folderId = isFolder ? icon.dataset.id : null;
    const fileId = !isFolder ? icon.dataset.id : null;

    const url = `../../ajax/getInfos.php?folderId=${encodeURIComponent(folderId || null)}&fileId=${encodeURIComponent(fileId || null)}`
    fetch(url)
        .then(res => res.json())
        .then(data => {
            const container = document.querySelector('#fileInfo .popup-content table');
            if (!container) return;

            container.innerHTML = '';
            // Fill the table with key:value pairs received via AJAX
            Object.entries(data).forEach(([key, value]) => {
                const row = document.createElement('tr');
                row.innerHTML = `<td>${key}</td><td>${value}</td>`;
                container.appendChild(row);
            });

            // Hide menus and show the popup
            document.querySelectorAll('.popup').forEach(i => i.classList.remove('show'));
            if (menu_principal) menu_principal.style.visibility = null;
            if (menu_secondaire) menu_secondaire.style.visibility = null;
            document.querySelector("#fileInfo").classList.add("show");
        })
        .catch(err => console.error('Error loading properties:', err));
}

/**
 * Loads and renders files and folders based on current URL parameters (filters, sort).
 */
function loadFiles() {
    document.querySelectorAll('.popup').forEach(i => i.classList.remove('show'));

    let theme = getDynamicSegmentFromUrl();
    const previewPath = '../../../bdd/_thumbs/'+theme+'/';

    const currentOrderType = getParamFromUrl('orderType') || 'ASC';
    const currentOrderField = getParamFromUrl('order') || 'name';

    // 1. COLLECT ACTIVE FILTER CRITERIA
    const criteria = { type: [], favorite: null, shared: null, recent: null };

    URLparams.forEach(param => {
        const checkbox = document.getElementById(param);
        if (checkbox && checkbox.checked) {
            criteria.type.push(param);
        }
    });

    criteria.favorite = getParamFromUrl('favorite') ? 1 : null;
    criteria.shared = getParamFromUrl('shared') ? 1 : null;
    criteria.recent = getParamFromUrl('recent') ? 1 : null;

    // 2. BUILD THE API QUERY STRING
    const params = new URLSearchParams({
        orderType: currentOrderType,
        order: currentOrderField,
        folderId: folderId || '',
    });

    criteria.type.forEach(t => params.append('type[]', t));

    Object.keys(criteria).forEach(key => {
        if (key !== 'type' && criteria[key]) params.append(key, criteria[key]);
    });

    // 3. FETCH AND RENDER
    fetch(`../../ajax/getToPrint.php?${params.toString()}`)
        .then(res => res.json())
        .then(data => {
            console.log(data);
            const container = document.querySelector('.main-content');
            container.innerHTML = '';

            // Render Folders
            data.Folders.forEach(folder => {
                const div = document.createElement('div');
                div.className = 'folder-icon';
                div.dataset.id = folder.id;
                const prev = folder.favorite === 0 ? previewPath + 'standard/folder.png' : previewPath + 'favorite/folder.png';
                div.innerHTML = `<span class="icon"><img src="${prev}" alt="folder" class="icon"></span><span class="name">${folder.name}</span>`;
                container.appendChild(div);
            });

            // Render Files
            data.Files.forEach(file => {
                const div = document.createElement('div');
                div.className = 'file-icon';
                div.dataset.id = file.id;
                const prev = file.favorite === 0 ? previewPath + 'standard' + '/' + file.preview : previewPath + 'favorite' + '/' + file.preview;
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

// Drag and Drop listeners
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

/**
 * Uploads a list of files to the server.
 * @param {Array<File>} files - The list of files to upload.
 */
async function uploadFiles(files){
    const token = Math.random().toString(36).slice(2, 10);
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
            folderId: currentFolderId,
            token
        }));
        try {
            await fetch("../../ajax/upload.php", { method: "POST", body: form })
                .then(res => res.text())
                .then(data => {
                    console.log(data);
                });
            loadFiles();
        }
        catch (err) {
            console.error(`Error for ${file.name}:`, err);
        }
    }
}


// ====================================================================
// 5. INPUT LISTENERS (KEYBOARD & MOUSE)
// ====================================================================

// Keydown: Ctrl + A (Select All), Ctrl + Q (Download)
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
    if (ctrl && a) {
        e.preventDefault();
        document.querySelectorAll('.file-icon, .folder-icon').forEach(i => i.classList.add('show'));
    }
});

// Keyup: Ctrl release, Delete (delete selected items)
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


// Left Click: Icon selection
document.body.addEventListener('click', e => {
    if (e.button !== 0) return;

    if (menu_principal) menu_principal.style.visibility = null;
    if (menu_secondaire) menu_secondaire.style.visibility = null;

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
});

// Double Click: Folder navigation
document.body.addEventListener('dblclick', e => {
    const icon = e.target.closest('div.file-icon, div.folder-icon');
    if (!icon || !icon.classList.contains('folder-icon')) return;

    const nouveauFolderId = icon.dataset.id;
    const url = new URL(window.location.href);
    url.searchParams.set('folderId', nouveauFolderId);

    window.location.href = url.href;
});

// Right Click: Context menu
document.addEventListener('contextmenu', (ev) => {
    ev.preventDefault();
    const icon = ev.target.closest('div.file-icon, div.folder-icon');
    const icons = document.querySelectorAll('.file-icon, .folder-icon');

    icons.forEach(i => i.classList.remove('menuSelected','multiSelected'));
    const properties = document.getElementById('properties');
    const rename = document.getElementById('rename');

    if (properties) properties.classList.remove('disabled');
    if (rename) rename.classList.remove('disabled');

    document.querySelectorAll('.popup').forEach(i => i.classList.remove('show'));

    if(icon){
        // Click on an icon (Main menu)
        if (menu_secondaire) menu_secondaire.style.visibility = null;
        if (menu_principal) {
            update_menu_pos(menu_principal, ev.clientX, ev.clientY);
            menu_principal.style.visibility = 'visible';
        }

        const selectedIcons = document.querySelectorAll('.file-icon.show, .folder-icon.show');
        if (icon.classList.contains('show') || selectedIcons.length === 0) {
            selectedIcons.forEach(i => i.classList.add('multiSelected'));
        } else {
            selectedIcons.forEach(i => i.classList.remove('show'));
        }
        icon.classList.add('show', 'multiSelected', 'menuSelected');
    }
    else if (ev.target.closest('.main-content')){
        // Click on background (Secondary menu)
        if (menu_principal) menu_principal.style.visibility = null;
        if (menu_secondaire) {
            update_menu_pos(menu_secondaire, ev.clientX, ev.clientY);
            menu_secondaire.style.visibility = 'visible';
        }
    }
    else {
        // Click elsewhere: hide everything
        if (menu_principal) menu_principal.style.visibility = null;
        if (menu_secondaire) menu_secondaire.style.visibility = null;
    }
});

/**
 * Deletes the selected files and folders.
 * @param {NodeListOf<HTMLElement>} icons - The list of icon elements to delete.
 */
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
        .then((res) => {
            console.log(res);
            loadFiles()
        })
        .catch(err => console.error(err));
}


// ====================================================================
// 6. DOM CONTENT LOADED (Main Initialization)
// ====================================================================

document.addEventListener('DOMContentLoaded', function() {

    // 1. INITIALIZE FILTER STATE (based on URL)
    const filters = document.querySelectorAll('.filter');
    filters.forEach(f => {
        if (getParamFromUrl(f.id) !== null) {
            f.checked = true;
        }
    });

    // 2. LISTENERS for ACTIONS and FILTERS

    // Filter change handler (AJAX)
    filters.forEach(filter=>{
        filter.addEventListener('change', function() {
            updateUrlAndLoad(this.id, this.checked);
        });
    });

    // --- SORTING LOGIC ---
    document.querySelector('#sort_name')?.addEventListener('click', (e) => {
        e.preventDefault();
        handleSorting('name');
    });

    document.querySelector('#sort_date')?.addEventListener('click', (e) => {
        e.preventDefault();
        handleSorting('updated_at');
    });

    // --- POPUP and ACTION BUTTON HANDLERS ---

    // PROFIL button: opens the Profile popup
    document.querySelector('.profil')?.addEventListener('click', (e) => {
        e.stopPropagation();
        if (menu_principal) menu_principal.style.visibility = null;
        if (menu_secondaire) menu_secondaire.style.visibility = null;
        document.querySelectorAll('.popup').forEach(i => i.classList.remove('show'));
        document.getElementById('profilInfo').classList.add('show');
    });

    // CLOSE button (for popups)
    document.querySelectorAll('.popup .close-btn').forEach(button => {
        button.addEventListener('click', (e) => {
            e.stopPropagation();
            e.target.closest('.popup').classList.remove('show');
        });
    });

    // PROPERTIES button: opens the #fileInfo popup
    document.querySelector('#properties')?.addEventListener('click', () => {
        const icon = document.querySelector('.file-icon.menuSelected, .folder-icon.menuSelected');
        if (icon) {
            loadProperties(icon);
        }
    });

    // DOWNLOAD action
    document.querySelector('.download')?.addEventListener('click', () => {
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

    // SET FAVORITE action
    document.querySelector('#setFavorite')?.addEventListener('click', () => {
        const icons = document.querySelectorAll('.file-icon.show, .folder-icon.show');
        const folders = [], files = [];
        icons.forEach(i => {
            if (i.classList.contains('folder-icon')) folders.push(i.dataset.id);
            else files.push(i.dataset.id);
        });
        const url = `../../ajax/setFavorite.php?folders=${encodeURIComponent(folders.join(','))}&files=${encodeURIComponent(files.join(','))}`;
        fetch(url)
            .then(() => loadFiles())
            .catch(err => console.error(err));
    });

    // RENAME action
    document.querySelector('#rename')?.addEventListener('click', () => {
        const icons = document.querySelectorAll('.file-icon.menuSelected, .folder-icon.menuSelected');
        if (icons.length === 0) return;
        const newName = prompt('Choose a new name:');
        if (!newName) return;
        const folders = {}, files = {};
        icons.forEach(i => {
            if (i.classList.contains('folder-icon')) folders[i.dataset.id] = newName;
            else files[i.dataset.id] = newName;
        });
        const url = `../../ajax/rename.php?parent_id=${getParamFromUrl("folderId")}&folders=${encodeURIComponent(JSON.stringify(folders))}&files=${encodeURIComponent(JSON.stringify(files))}`;
        fetch(url)
            .then(res => res.text())
            .then((res) => {
                console.log(res);
                loadFiles();
            })
            .catch(err => console.error(err));
    });

    // SELECT ALL action
    document.querySelector('.selectAll')?.addEventListener('click', (e) => {
        e.stopPropagation();
        document.querySelectorAll('.file-icon, .folder-icon').forEach(i => i.classList.add("show"));
    });

    // DELETE action (from context menu)
    document.querySelector('#delete')?.addEventListener('click', () => {
        const icons = document.querySelectorAll('.file-icon.multiSelected, .folder-icon.multiSelected, ' + '.file-icon.show.multiSelected, .folder-icon.show.multiSelected');
        deleteContent(icons);
    });

    // CREATE FOLDER action
    document.querySelector('.create_folder')?.addEventListener('click', () => {
        const folderName = prompt('Folder name:');
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

    // UPLOAD button
    document.querySelector('.upload')?.addEventListener('click', () => fileInput.click());

    // LOGOUT button
    document.querySelector('.logout')?.addEventListener('click', logout);
    
    document.querySelector('.search')?.addEventListener('click', function(e) {
        e.preventDefault;
        searchText = document.querySelector('#searchText').value;
        if(searchText!='sdv'){
            
        document.querySelectorAll('.popup').forEach(i => i.classList.remove('show'));

        let theme = getDynamicSegmentFromUrl();
        const previewPath = '../../../bdd/_thumbs/'+theme+'/';

        fetch(`../../ajax/search.php?searchText=${encodeURIComponent(searchText)}`)
            .then(res => res.json())
            .then(data => {
                //console.log(data);
                const container = document.querySelector('.main-content');
                container.innerHTML = '';

                // Render Folders
                data.Folders.forEach(folder => {
                    const div = document.createElement('div');
                    div.className = 'folder-icon';
                    div.dataset.id = folder.id;
                    const prev = folder.favorite === 0 ? previewPath + 'standard/folder.png' : previewPath + 'favorite/folder.png';
                    div.innerHTML = `<span class="icon"><img src="${prev}" alt="folder" class="icon"></span><span class="name">${folder.name}</span>`;
                    container.appendChild(div);
                });

                // Render Files
                data.Files.forEach(file => {
                    const div = document.createElement('div');
                    div.className = 'file-icon';
                    div.dataset.id = file.id;
                    const prev = file.favorite === 0 ? previewPath + 'standard' + '/' + file.preview : previewPath + 'favorite' + '/' + file.preview;
                    div.innerHTML = `<span class="icon"><img src="${prev}" alt="file" class="icon"></span><span class="name">${file.name}</span>`;
                    container.appendChild(div);
                });
            })
            .catch(err => console.error(err));
    }});

    // --- THEME LOGIC ---
    document.getElementById('themes-select')?.addEventListener('change', function(e) {
        e.stopPropagation();
        const selectedTheme = this.value;

        const currentUrl = new URL(window.location.href);
        let newSearchParams = currentUrl.searchParams.toString();

        fetch(`../../ajax/setLastTheme.php?last_theme=${selectedTheme}`);
        window.location.href = `/EZDrive/app/pages/${selectedTheme}/index.php?${newSearchParams}`;
    });

    // Username update handler
    document.querySelector(".userName")?.addEventListener("click", function(e) {
        e.stopPropagation();
        const usernameInput = document.querySelector('#username-input');
        if (usernameInput && usernameInput.value) {
            fetch(`../../ajax/changeUsername.php?username=${encodeURIComponent(usernameInput.value)}`)
                .then(() => window.location.reload());
        }
    });

    document.getElementById('username-form')?.addEventListener('submit', function(event) {
        event.stopPropagation()
        event.preventDefault();
    });

    document.querySelector('.DeleteAcct')?.addEventListener('click', function(event) {
        event.stopPropagation()
        event.preventDefault();
        // Account deletion logic
    });

    // 3. INITIAL FILE LOADING
    loadFiles();
});

// Prevent drag-and-drop icon opening
document.addEventListener('dragstart', (e) => {
    e.preventDefault();
});