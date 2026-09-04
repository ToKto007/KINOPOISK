<?php
// test_db_fixed.php - диагностика с правильной кодировкой
header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 Диагностика Access БД (исправленная кодировка)</h1>";

// 1. Проверка расширений
echo "<h2>1. Проверка PHP расширений:</h2>";
echo "COM: " . (extension_loaded('com_dotnet') ? '✅ ДОСТУПНО' : '❌ НЕ ДОСТУПНО') . "<br>";
echo "PDO: " . (extension_loaded('pdo') ? '✅ ДОСТУПНО' : '❌ НЕ ДОСТУПНО') . "<br>";
echo "PDO_ODBC: " . (extension_loaded('pdo_odbc') ? '✅ ДОСТУПНО' : '❌ НЕ ДОСТУПНО') . "<br>";

// 2. Проверка файла
$dbPath = "C:/kinopoick/bd.mdb";
echo "<h2>2. Проверка файла БД:</h2>";
echo "Путь: $dbPath<br>";
if (file_exists($dbPath)) {
    echo "✅ Файл существует<br>";
    echo "Размер: " . filesize($dbPath) . " байт<br>";
    echo "Права доступа: " . substr(sprintf('%o', fileperms($dbPath)), -4) . "<br>";
    echo "Последнее изменение: " . date("Y-m-d H:i:s", filemtime($dbPath)) . "<br>";
} else {
    echo "❌ ФАЙЛ НЕ НАЙДЕН!<br>";
    exit;
}

// 3. Проверка через COM с правильной кодировкой
echo "<h2>3. Проверка подключения через COM:</h2>";
try {
    if (!class_exists('COM')) {
        echo "❌ Класс COM не доступен.<br>";
        exit;
    }
    
    $conn = new COM("ADODB.Connection");
    echo "✅ ADODB.Connection создан<br>";
    
    // Пробуем разные провайдеры
    $providers = [
        "Microsoft.ACE.OLEDB.12.0",
        "Microsoft.Jet.OLEDB.4.0"
    ];
    
    $connected = false;
    foreach ($providers as $provider) {
        try {
            echo "Пробуем провайдер: $provider... ";
            $conn->Open("Provider=$provider;Data Source=$dbPath;");
            echo "✅ ПОДКЛЮЧЕНО!<br>";
            $connected = true;
            break;
        } catch (Exception $e) {
            echo "❌ Ошибка: " . $e->getMessage() . "<br>";
        }
    }
    
    if (!$connected) {
        echo "<br>❌ Не удалось подключиться ни с одним провайдером.<br>";
        exit;
    }
    
    // 4. Получаем список таблиц с правильной кодировкой
    echo "<h2>4. Список таблиц в БД (исправленная кодировка):</h2>";
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr style='background: #667eea; color: white;'><th>#</th><th>Имя таблицы (сырое)</th><th>Имя таблицы (UTF-8)</th><th>Тип</th></tr>";
    
    $cat = $conn->OpenSchema(20);
    $tables = [];
    $count = 0;
    
    while (!$cat->EOF) {
        $name = $cat->Fields('TABLE_NAME')->Value;
        $type = $cat->Fields('TABLE_TYPE')->Value;
        
        if ($type == 'TABLE' && strpos($name, 'MSys') === false && strpos($name, '~') === false) {
            $count++;
            
            // Пробуем конвертировать в разные кодировки
            $name_utf8 = mb_convert_encoding($name, 'UTF-8', 'Windows-1251');
            $name_utf8_alt = mb_convert_encoding($name, 'UTF-8', 'CP1251');
            
            // Показываем сырые данные
            echo "<tr>";
            echo "<td>$count</td>";
            echo "<td><strong>'" . htmlspecialchars($name) . "'</strong><br>";
            echo "<small>Длина: " . strlen($name) . " байт</small></td>";
            echo "<td><strong>'" . htmlspecialchars($name_utf8) . "'</strong><br>";
            echo "<small>Длина: " . strlen($name_utf8) . " байт</small></td>";
            echo "<td>" . htmlspecialchars($type) . "</td>";
            echo "</tr>";
            
            $tables[] = [
                'raw' => $name,
                'utf8' => $name_utf8
            ];
        }
        $cat->MoveNext();
    }
    echo "</table>";
    
    if (empty($tables)) {
        echo "❌ Таблицы не найдены в БД<br>";
    } else {
        echo "<br>✅ Найдено таблиц: " . count($tables) . "<br>";
    }
    
    // 5. Проверяем данные в каждой таблице
    echo "<h2>5. Проверка данных в таблицах:</h2>";
    
    foreach ($tables as $table) {
        $tableName = $table['raw'];
        $tableNameDisplay = $table['utf8'];
        
        echo "<h3>Таблица: " . htmlspecialchars($tableNameDisplay) . " (сырое имя: '" . htmlspecialchars($tableName) . "')</h3>";
        
        try {
            // Пробуем запрос с разными вариантами имени
            $variants = [
                "[$tableName]",
                "`$tableName`",
                "'$tableName'",
            ];
            
            $dataFound = false;
            foreach ($variants as $variant) {
                try {
                    $sql = "SELECT TOP 3 * FROM $variant";
                    echo "Пробуем SQL: " . htmlspecialchars($sql) . "<br>";
                    $rs = $conn->Execute($sql);
                    
                    if (!$rs->EOF) {
                        echo "✅ Данные найдены!<br>";
                        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
                        echo "<tr style='background: #764ba2; color: white;'>";
                        for ($i = 0; $i < $rs->Fields->Count; $i++) {
                            $fieldName = $rs->Fields($i)->Name;
                            $fieldNameUtf8 = mb_convert_encoding($fieldName, 'UTF-8', 'Windows-1251');
                            echo "<th>" . htmlspecialchars($fieldNameUtf8) . "</th>";
                        }
                        echo "</tr>";
                        
                        $rowCount = 0;
                        while (!$rs->EOF && $rowCount < 3) {
                            echo "<tr>";
                            for ($i = 0; $i < $rs->Fields->Count; $i++) {
                                $value = $rs->Fields($i)->Value;
                                $valueUtf8 = mb_convert_encoding($value, 'UTF-8', 'Windows-1251');
                                echo "<td>" . htmlspecialchars($valueUtf8) . "</td>";
                            }
                            echo "</tr>";
                            $rs->MoveNext();
                            $rowCount++;
                        }
                        echo "</table>";
                        $dataFound = true;
                        break;
                    }
                } catch (Exception $e) {
                    // Пробуем следующий вариант
                }
            }
            
            if (!$dataFound) {
                echo "❌ Данные не найдены или таблица пуста<br>";
            }
            
        } catch (Exception $e) {
            echo "❌ Ошибка: " . $e->getMessage() . "<br>";
        }
        echo "<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Критическая ошибка: " . $e->getMessage() . "<br>";
    echo "Код ошибки: " . $e->getCode() . "<br>";
}
?>