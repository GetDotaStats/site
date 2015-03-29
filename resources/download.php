<?php
try {
    $file = $_GET['f'];
    switch ($file) {
        case 'LXUpdater.vbs':
            $file = './lx/LXUpdater.vbs';
            if (file_exists($file)) {
                header('X-Sendfile: ' . $file);
                header("Content-Type: application/octet-stream");
                header('Content-Disposition: attachment;filename="' . basename($file) . '"');
            } else {
                throw new Exception(basename($file) . ' not found!');
            }
            break;
        default:
            throw new Exception('No file selected!');
    }
} catch (Exception $e) {
    header('HTTP/1.0 404 Not Found');
    echo 'Error: ' . $e->getMessage();
}