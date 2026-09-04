<?php
// get_data.php - возвращает данные из таблицы Access
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 0); // Отключаем вывод ошибок в JSON

try {
    require_once 'config.php';
    
    $db = AccessDB::getInstance();
    $tableName = isset($_GET['table']) ? $_GET['table'] : '';
    
    if (empty($tableName)) {
        $tables = $db->getTables();
        echo json_encode(['tables' => $tables], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Получаем сырое имя таблицы для запроса
    $rawTables = $db->getTablesRaw();
    $rawTableName = $tableName;
    
    // Ищем соответствие
    foreach ($rawTables as $table) {
        $converted = mb_convert_encoding($table['Name'], 'UTF-8', 'Windows-1251');
        if ($converted === $tableName) {
            $rawTableName = $table['Name'];
            break;
        }
    }
    
    // Выполняем запрос
    $sql = "SELECT * FROM [{$rawTableName}]";
    $rows = $db->query($sql);
    
    $fields = [];
    if (!empty($rows)) {
        $fields = array_keys($rows[0]);
    }
    
    echo json_encode([
        'fields' => $fields,
        'rows' => $rows,
        'count' => count($rows)
    ], JSON_UNESCAPED_UNICODE);
    
} catch(Exception $e) {
    echo json_encode([
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>