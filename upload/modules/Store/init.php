<?php
/*
 *  Made by Samerton
 *  https://github.com/samerton
 *  NamelessMC version 2.0.0-pr5
 *
 *  License: MIT
 *
 *  Store initialisation file
 */

// Language
$store_language = new Language(ROOT_PATH . '/modules/Store/language', LANGUAGE);

// Load classes
spl_autoload_register(function ($class) {
    $path = join(DIRECTORY_SEPARATOR, array(ROOT_PATH, 'modules', 'Store', 'classes', $class . '.php'));
    if (file_exists($path)) {
        require_once($path);
    }
});

require_once(ROOT_PATH . '/modules/Store/module.php');
$module = new Store_Module($language, $store_language, $pages, $cache, $endpoints);