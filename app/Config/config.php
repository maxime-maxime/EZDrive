<?php

$extToType = [
    // Images
    'jpg'  => 'image',
    'jpeg' => 'image',
    'png'  => 'image',
    'gif'  => 'image',
    'bmp'  => 'image',
    'webp' => 'image',
    'tiff' => 'image',
    'svg'  => 'image',

    // Vidéos
    'mp4'  => 'video',
    'mkv'  => 'video',
    'avi'  => 'video',
    'mov'  => 'video',
    'wmv'  => 'video',
    'flv'  => 'video',
    'webm' => 'video',
    'mpeg' => 'video',

    // Audios
    'mp3'  => 'audio',
    'wav'  => 'audio',
    'ogg'  => 'audio',
    'flac' => 'audio',
    'aac'  => 'audio',
    'm4a'  => 'audio',
    'wma'  => 'audio',
];


$typeToPreview = [
    'image' => 'file.png',
    'document' => 'file.png',
    'video' => 'video.png',
    'audio' => 'file.png',
    'pdf' => 'file.png',
];

$rootPath = 'C:\wamp64\www\EZDrive\bdd\content';

$invalidChars = ['/', '\\', '?', '%', '*', ':', '|', '"', '<', '>', "\0", "\n", "\r"];