<?php

$extToType = [
    // --- 1. image ---
    'jpg'  => 'image', 'jpeg' => 'image', 'png'  => 'image', 'gif'  => 'image',
    'bmp'  => 'image', 'webp' => 'image', 'tiff' => 'image', 'tif'  => 'image',
    'ico'  => 'image', 'svg'  => 'image', 'psd'  => 'image', 'ai'   => 'image',
    'raw'  => 'image', 'cr2'  => 'image', 'nef'  => 'image', 'dng'  => 'image',
    'arw'  => 'image',

    // --- 2. medias (Audio & Vidéo) ---
    // Audios
    'mp3'  => 'media', 'wav'  => 'media', 'ogg'  => 'media', 'flac' => 'media',
    'aac'  => 'media', 'm4a'  => 'media', 'wma'  => 'media', 'aiff' => 'media',
    'opus' => 'media', 'midi' => 'media',

    // Vidéos
    'mp4'  => 'media', 'mkv'  => 'media', 'avi'  => 'media', 'mov'  => 'media',
    'wmv'  => 'media', 'flv'  => 'media', 'webm' => 'media', 'mpeg' => 'media',
    'mpg'  => 'media', 'm4v'  => 'media', '3gp'  => 'media', 'vob'  => 'media',

    // --- 3. document (Documents, Code, Data, Polices) ---
    // Documents
    'pdf'  => 'document', 'doc'  => 'document', 'docx' => 'document', 'dot'  => 'document',
    'xls'  => 'document', 'xlsx' => 'document', 'ppt'  => 'document', 'pptx' => 'document',
    'txt'  => 'document', 'log'  => 'document', 'md'   => 'document', 'rtf'  => 'document',
    'asc'  => 'document', 'csv'  => 'document', 'tsv'  => 'document',

    // Code & Scripts
    'html' => 'document', 'htm'  => 'document', 'xml'  => 'document', 'css'  => 'document',
    'scss' => 'document', 'sass' => 'document', 'js'   => 'document', 'json' => 'document',
    'ts'   => 'document', 'jsx'  => 'document', 'php'  => 'document', 'py'   => 'document',
    'java' => 'document', 'c'    => 'document', 'cpp'  => 'document', 'h'    => 'document',
    'hpp'  => 'document', 'sh'   => 'document', 'bat'  => 'document', 'rb'   => 'document',
    'go'   => 'document', 'rs'   => 'document', 'swift'=> 'document',

    // Données & BDD
    'cfg'  => 'document', 'ini'  => 'document', 'conf' => 'document', 'dat'  => 'document',
    'sql'  => 'document', 'db'   => 'document', 'sqlite'=> 'document', 'yml'  => 'document',
    'yaml' => 'document',

    // Polices
    'ttf'  => 'document', 'otf'  => 'document', 'woff' => 'document', 'woff2'=> 'document',
    'eot'  => 'document',

    // --- 4. divers (Archives, Exécutables) ---
    // Archives
    'zip'  => 'divers', 'rar'  => 'divers', 'tar'  => 'divers', 'gz'   => 'divers',
    'tgz'  => 'divers', '7z'   => 'divers', '7zip' => 'divers', 'bz2'  => 'divers',
    'iso'  => 'divers',

    // Exécutables / Binaires
    'exe'  => 'divers', 'apk'  => 'divers', 'bin'  => 'divers', 'msi'  => 'divers',
    'dmg'  => 'divers',
];

$typeToPreview = [
    // --- 1. CODE & SCRIPTS ---
    // C/C++
    'cpp'   => 'c++.png',
    'cxx'   => 'c++.png',
    'cc'    => 'c++.png',
    'c'     => 'c++.png',
    'h'     => 'c++.png',
    'hpp'   => 'c++.png',

    // JavaScript / TypeScript / Frontend
    'js'    => 'js.png',
    'json'  => 'js.png',
    'jsx'   => 'js.png',
    'tsx'   => 'js.png',
    'vue'   => 'js.png',
    'css'   => 'js.png',
    'scss'  => 'js.png',
    'sass'  => 'js.png',
    'less'  => 'js.png',

    // Web Markup / XML
    'html'  => 'html.png',
    'htm'   => 'html.png',
    'xhtml' => 'html.png',
    'xml'   => 'xml.png',
    'svg'   => 'xml.png',

    // Code divers (PHP, Python, Ruby, Go, Rust, Java, etc.) => Utilisation de 'code.png'
    'php'   => 'code.png',
    'py'    => 'code.png',
    'java'  => 'code.png',
    'rb'    => 'code.png',
    'go'    => 'code.png',
    'rs'    => 'code.png',
    'ipynb' => 'code.png',
    'swift' => 'code.png',
    'kt'    => 'code.png',
    'sh'    => 'code.png', // Script Shell
    'bat'   => 'code.png', // Script Batch

    // --- 2. DOCUMENTS TEXTE & DONNÉES ---
    // Texte brut et logs
    'txt'   => 'txt.png',
    'log'   => 'log.png',
    'asc'   => 'asc.png',
    'md'    => 'txt.png', // Markdown
    'rtf'   => 'txt.png', // Rich Text Format

    // Configuration / Paramètres => Utilisation de 'data.png'
    'cfg'   => 'data.png',
    'ini'   => 'data.png',
    'conf'  => 'data.png',
    'dat'   => 'data.png',
    'yml'   => 'data.png',
    'yaml'  => 'data.png',

    // --- 3. DOCUMENTS OFFICE & PDF ---
    'pdf'   => 'pdf.png',

    // Word
    'doc'   => 'doc.png',
    'docx'  => 'doc.png',
    'dot'   => 'doc.png',
    'dotx'  => 'doc.png',

    // Excel / CSV
    'xls'   => 'xls.png',
    'xlsx'  => 'xls.png',
    'xlsm'  => 'xls.png',
    'csv'   => 'csv.png',
    'tsv'   => 'csv.png',

    // PowerPoint
    'ppt'   => 'ppt.png',
    'pptx'  => 'ppt.png',
    'pptm'  => 'ppt.png',

    // Base de données => Utilisation de 'data.png'
    'sql'   => 'data.png',
    'db'    => 'data.png',
    'sqlite'=> 'data.png',

    // --- 4. IMAGES & GRAPHIQUES ---
    // Images courantes
    'png'   => 'png.png',
    'jpg'   => 'jpg.png',
    'jpeg'  => 'jpg.png',
    'gif'   => 'gif.png',
    'webp'  => 'webp.png',
    'tif'   => 'tif.png',
    'tiff'  => 'tif.png',

    // Formats divers (BMP, ICO, PBM, etc.) => Utilisation de 'image.png'
    'bmp'   => 'image.png',
    'ico'   => 'image.png',
    'pbm'   => 'image.png',
    'pgm'   => 'image.png',
    'ppm'   => 'image.png',
    'xpm'   => 'image.png',

    // Fichiers RAW
    'raw'   => 'raw.png',
    'cr2'   => 'raw.png',
    'nef'   => 'raw.png',
    'dng'   => 'raw.png',
    'arw'   => 'raw.png',

    // Fichiers Design (PSD, AI, EPS) => Utilisation de 'image.png'
    'psd'   => 'image.png',
    'ai'    => 'image.png',
    'eps'   => 'image.png',

    // --- 5. AUDIO ---
    'mp3'   => 'mp3.png',
    'wav'   => 'wav.png',
    'ogg'   => 'ogg.png',
    'wma'   => 'wma.png',
    'aac'   => 'aac.png',

    // Formats divers/haute qualité => Utilisation de 'music.png'
    'm4a'   => 'music.png',
    'flac'  => 'music.png',
    'aiff'  => 'music.png',
    'opus'  => 'music.png',
    'midi'  => 'music.png',

    // --- 6. VIDÉO ---
    'mov'   => 'mov.png',
    'mpg'   => 'mpg.png',
    'avi'   => 'avi.png',

    // Formats vidéo courants
    'm4v'   => 'mpg.png',
    'wmv'   => 'mpg.png',
    'flv'   => 'mpg.png',
    'webm'  => 'mpg.png',

    'mp4'   => 'video.png',
    '3gp'   => 'video.png',
    'vob'   => 'video.png',
    'mkv'   => 'video.png',

    // --- 7. ARCHIVES & BINAIRES ---
    'zip'   => 'zip.png',
    'rar'   => 'rar.png',
    'tar'   => 'tar.png',
    '7zip'  => '7zip.png',

    // Autres archives
    'gz'    => 'zip.png',
    'tgz'   => 'zip.png',
    'bz2'   => 'zip.png',
    'iso'   => 'zip.png', // Image disque

    // Exécutables et binaires
    'exe'   => 'exe.png',
    'apk'   => 'apk.png',
    'bin'   => 'bin.png',
    'msi'   => 'exe.png',
    'dmg'   => 'exe.png',

    // --- 8. POLICES (FONTS) ---
    'otf'   => 'otf.png',
    'ttf'   => 'ttf.png',
    'woff'  => 'woff.png',
    'woff2' => 'woff.png',
    'eot'   => 'woff.png',
];

$rootPath = 'C:\wamp64\www\EZDrive\bdd\content';

$invalidChars = ['/', '\\', '?', '%', '*', ':', '|', '"', '<', '>', "\0", "\n", "\r","."];

$maxUploadSize = 255000000;

$easteregg = array(
    'pornDrive' => "xxx",
    'sobre' => "sobre"
);

$defaultFolderName = "folder";