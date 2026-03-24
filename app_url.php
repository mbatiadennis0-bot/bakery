<?php
function app_url(string $path = ''): string
{
    static $basePath = null;

    if ($basePath === null) {
        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $basePath = rtrim(str_replace('/' . basename($scriptName), '', $scriptName), '/');
    }

    if ($path === '') {
        return $basePath !== '' ? $basePath : '/';
    }

    return ($basePath !== '' ? $basePath : '') . '/' . ltrim($path, '/');
}
?>
