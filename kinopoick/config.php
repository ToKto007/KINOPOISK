<?php
// config.php - главный файл конфигурации
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Указываем путь к файлу Access - ИЗМЕНИТЕ НА СВОЙ ПУТЬ!
$GLOBALS['db_path'] = "C:/kinopoick/bd.mdb"; 

// Проверяем существование файла
if (!file_exists($GLOBALS['db_path'])) {
    die("❌ Файл базы данных не найден по пути: " . $GLOBALS['db_path'] . 
        "<br>Пожалуйста, проверьте путь в файле config.php");
}

// Подключаем класс для работы с COM
require_once 'config_com.php';
?>