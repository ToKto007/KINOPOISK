<?php
// test_db.php - полная диагностика
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 Диагностика Access БД</h1>";

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
    echo "Проверьте правильность пути и существование папки C:/kinopoick/<br>";
    echo "Создайте папку и скопируйте туда файл bd.mdb";
    exit;
}

// 3. Проверка через COM
echo "<h2>3. Проверка подключения через COM:</h2>";
try {
    if (!class_exists('COM')) {
        echo "❌ Класс COM не доступен. Включите расширение com_dotnet в php.ini<br>";
        echo "Раскомментируйте строку: extension=php_com_dotnet.dll";
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
        echo "Установите Microsoft Access Database Engine:<br>";
        echo "<a href='https://www.microsoft.com/en-us/download/details.aspx?id=54920' target='_blank'>Скачать ACE 2016</a><br>";
        echo "Или попробуйте установить Microsoft Access Database Engine 2010";
        exit;
    }
    
    // 4. Получаем список таблиц
    echo "<h2>4. Список таблиц в БД:</h2>";
    $cat = $conn->OpenSchema(20);
    $tables = [];
    while (!$cat->EOF) {
        $name = $cat->Fields('TABLE_NAME')->Value;
        $type = $cat->Fields('TABLE_TYPE')->Value;
        if ($type == 'TABLE' && strpos($name, 'MSys') === false && strpos($name, '~') === false) {
            $tables[] = $name;
            echo "📊 $name ($type)<br>";
        }
        $cat->MoveNext();
    }
    
    if (empty($tables)) {
        echo "❌ Таблицы не найдены в БД<br>";
    }
    
    // 5. Проверяем данные в таблице films
    if (in_array('films', $tables)) {
        echo "<h2>5. Проверка таблицы 'films':</h2>";
        $rs = $conn->Execute("SELECT TOP 5 * FROM [films]");
        if (!$rs->EOF) {
            echo "✅ Данные найдены!<br>";
            echo "<table border='1' cellpadding='5'>";
            echo "<tr>";
            for ($i = 0; $i < $rs->Fields->Count; $i++) {
                echo "<th>" . $rs->Fields($i)->Name . "</th>";
            }
            echo "</tr>";
            while (!$rs->EOF) {
                echo "<tr>";
                for ($i = 0; $i < $rs->Fields->Count; $i++) {
                    echo "<td>" . $rs->Fields($i)->Value . "</td>";
                }
                echo "</tr>";
                $rs->MoveNext();
            }
            echo "</table>";
        } else {
            echo "❌ Таблица 'films' пуста";
        }
    } else {
        echo "<h2>5. Таблица 'films' не найдена</h2>";
    }
    
    // 6. Проверяем get_data.php напрямую
    echo "<h2>6. Проверка get_data.php:</h2>";
    $url = "http://" . $_SERVER['HTTP_HOST'] . "/kinopoick/get_data.php?table=films";
    echo "Запрос: $url<br>";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headers = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    curl_close($ch);
    
    echo "HTTP Code: $httpCode<br>";
    echo "Заголовки: <pre>" . htmlspecialchars($headers) . "</pre>";
    echo "Тело ответа (первые 500 символов): <pre>" . htmlspecialchars(substr($body, 0, 500)) . "</pre>";
    
    // Проверяем JSON
    $json = json_decode($body, true);
    if ($json === null) {
        echo "❌ JSON парсинг не удался!<br>";
        echo "Ошибка JSON: " . json_last_error_msg() . "<br>";
        echo "Полный ответ: <pre>" . htmlspecialchars($body) . "</pre>";
    } else {
        echo "✅ JSON валидный!<br>";
        echo "Структура: <pre>" . print_r($json, true) . "</pre>";
    }
    
} catch (Exception $e) {
    echo "❌ Критическая ошибка: " . $e->getMessage() . "<br>";
    echo "Код ошибки: " . $e->getCode() . "<br>";
    echo "Трассировка: <pre>" . $e->getTraceAsString() . "</pre>";
}
?>