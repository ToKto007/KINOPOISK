<?php
// config_com.php - подключение через COM с правильной кодировкой
error_reporting(E_ALL);
ini_set('display_errors', 1);

class AccessDB {
    private static $instance = null;
    private $conn;
    private $dbPath;
    
    private function __construct() {
        $this->dbPath = $GLOBALS['db_path'];
        
        try {
            $this->conn = new COM("ADODB.Connection");
            // Пробуем ACE
            $this->conn->Open("Provider=Microsoft.ACE.OLEDB.12.0;Data Source={$this->dbPath};");
        } catch(Exception $e) {
            try {
                // Пробуем JET
                $this->conn = new COM("ADODB.Connection");
                $this->conn->Open("Provider=Microsoft.Jet.OLEDB.4.0;Data Source={$this->dbPath};");
            } catch(Exception $e2) {
                die("❌ Ошибка подключения: " . $e2->getMessage());
            }
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getTablesRaw() {
        $cat = $this->conn->OpenSchema(20);
        $result = [];
        
        while (!$cat->EOF) {
            $tableName = $cat->Fields('TABLE_NAME')->Value;
            $tableType = $cat->Fields('TABLE_TYPE')->Value;
            
            if ($tableType == 'TABLE' && strpos($tableName, 'MSys') === false && strpos($tableName, '~') === false) {
                $result[] = ['Name' => $tableName];
            }
            $cat->MoveNext();
        }
        
        return $result;
    }

    public function getTables() {
        $tables = $this->getTablesRaw();
        foreach ($tables as &$table) {
            // Пробуем конвертировать из Windows-1251 в UTF-8
            $converted = mb_convert_encoding($table['Name'], 'UTF-8', 'Windows-1251');
            // Если конвертация не удалась, пробуем другие варианты
            if ($converted === false || $converted === '') {
                $converted = mb_convert_encoding($table['Name'], 'UTF-8', 'CP1251');
            }
            // Если всё ещё пусто, оставляем как есть
            $table['Name'] = $converted ?: $table['Name'];
        }
        return $tables;
    }
    
    // Функция для получения имени таблицы в правильной кодировке для запросов
    private function getTableNameForQuery($tableName) {
        // Если имя таблицы содержит русские буквы, используем квадратные скобки
        if (preg_match('/[а-яА-Я]/u', $tableName)) {
            // Ищем оригинальное имя в сырых данных
            $tables = $this->getTablesRaw();
            foreach ($tables as $table) {
                $converted = mb_convert_encoding($table['Name'], 'UTF-8', 'Windows-1251');
                if ($converted === $tableName) {
                    return $table['Name'];
                }
            }
        }
        return $tableName;
    }
    
    public function query($sql) {
        $rs = $this->conn->Execute($sql);
        $result = [];
        
        if (!$rs->EOF) {
            while (!$rs->EOF) {
                $row = [];
                for ($i = 0; $i < $rs->Fields->Count; $i++) {
                    $field = $rs->Fields($i);
                    $fieldName = mb_convert_encoding($field->Name, 'UTF-8', 'Windows-1251');
                    $fieldValue = mb_convert_encoding($field->Value, 'UTF-8', 'Windows-1251');
                    $row[$fieldName] = $fieldValue;
                }
                $result[] = $row;
                $rs->MoveNext();
            }
        }
        return $result;
    }
}
?>