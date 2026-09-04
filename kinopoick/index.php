<?php
// index.php - главная страница с выбором таблиц
require_once 'config.php';
$db = AccessDB::getInstance();
$tables = $db->getTables();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Работа с Access БД</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 { text-align: center; color: #333; margin-bottom: 10px; }
        .subtitle { text-align: center; color: #666; margin-bottom: 30px; }
        .db-info { 
            background: #f8f9ff; 
            padding: 15px; 
            border-radius: 10px; 
            margin-bottom: 20px; 
            font-size: 14px; 
            color: #555;
            word-break: break-all;
        }
        .db-info .status-ok { color: #27ae60; }
        .db-info .status-error { color: #e74c3c; }
        .table-selector { 
            display: flex; 
            gap: 10px; 
            flex-wrap: wrap; 
            margin-bottom: 30px; 
            justify-content: center; 
        }
        .table-btn {
            padding: 10px 20px;
            border: 2px solid #667eea;
            background: white;
            color: #667eea;
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 500;
        }
        .table-btn:hover { background: #667eea; color: white; }
        .table-btn.active { background: #667eea; color: white; }
        #dataContainer { margin-top: 20px; overflow-x: auto; }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 20px; 
            display: none;
            font-size: 14px;
        }
        table.visible { display: table; }
        thead { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        th { white-space: nowrap; position: sticky; top: 0; }
        tr:hover { background-color: #f8f9ff; }
        .status { text-align: center; padding: 40px; color: #666; }
        .error { color: #e74c3c; background: #fde8e8; padding: 15px; border-radius: 10px; }
        .count-info { text-align: right; margin-top: 15px; color: #666; font-size: 14px; }
        .loader { display: none; text-align: center; padding: 40px; }
        .loader.active { display: block; }
        .loader span {
            display: inline-block; width: 20px; height: 20px;
            border: 3px solid #f3f3f3; border-top: 3px solid #667eea;
            border-radius: 50%; animation: spin 1s linear infinite;
        }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        .error-details {
            background: #fde8e8;
            border-left: 4px solid #e74c3c;
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🗄️ Работа с Access БД</h1>
        <p class="subtitle">Просмотр всех таблиц и данных</p>
        
        <div class="db-info">
            📁 Файл: <strong><?= htmlspecialchars($GLOBALS['db_path']) ?></strong><br>
            📊 Таблиц: <strong><?= count($tables) ?></strong><br>
            🔗 Подключение: <strong class="status-ok">✅ Активно</strong>
        </div>
        
        <div class="table-selector">
            <button class="table-btn active" onclick="loadTable('')">📋 Все таблицы</button>
            <?php foreach($tables as $table): 
                $name = htmlspecialchars($table['Name'], ENT_QUOTES);
            ?>
                <button class="table-btn" onclick="loadTable('<?= $name ?>')">
                    📊 <?= htmlspecialchars($table['Name']) ?>
                </button>
            <?php endforeach; ?>
        </div>
        
        <div class="loader" id="loader"><span></span> Загрузка данных...</div>
        <div id="status" class="status">👆 Выберите таблицу для просмотра данных</div>
        <div id="dataContainer"></div>
    </div>

    <script>
        async function loadTable(tableName) {
            const container = document.getElementById('dataContainer');
            const status = document.getElementById('status');
            const loader = document.getElementById('loader');
            
            loader.classList.add('active');
            status.style.display = 'none';
            status.className = 'status';
            container.innerHTML = '';
            
            // Обновляем активную кнопку
            document.querySelectorAll('.table-btn').forEach(btn => btn.classList.remove('active'));
            if (event && event.target) {
                event.target.classList.add('active');
            }

            // Если нажали "Все таблицы"
            if (tableName === '') {
                loader.classList.remove('active');
                status.style.display = 'block';
                status.className = 'status';
                status.textContent = '👆 Пожалуйста, выберите конкретную таблицу для просмотра данных';
                return;
            }

            try {
                const url = `/kinopoick/get_data.php?table=${encodeURIComponent(tableName)}`;
                console.log('Загрузка:', url); // Для отладки
                
                const response = await fetch(url);
                const data = await response.json();
                
                if (data.error) {
                    status.style.display = 'block';
                    status.className = 'status error';
                    status.innerHTML = '❌ Ошибка:<br><div class="error-details">' + data.error + '</div>';
                    return;
                }
                
                if (!data.fields || data.fields.length === 0) {
                    status.style.display = 'block';
                    status.className = 'status';
                    status.textContent = '📭 Таблица пуста или не содержит данных';
                    return;
                }
                
                let html = '<div style="overflow-x:auto;"><table class="visible">';
                html += '<thead><tr>';
                data.fields.forEach(field => html += `<th>${field}</th>`);
                html += '</tr></thead>';
                
                html += '<tbody>';
                if (data.rows && data.rows.length > 0) {
                    data.rows.forEach(row => {
                        html += '<tr>';
                        data.fields.forEach(field => {
                            const value = row[field] !== undefined && row[field] !== null ? row[field] : '-';
                            html += `<td>${value}</td>`;
                        });
                        html += '</tr>';
                    });
                } else {
                    html += `<tr><td colspan="${data.fields.length}" style="text-align:center;color:#999;">Нет данных</td></tr>`;
                }
                html += '</tbody></table></div>';
                
                if (data.rows && data.rows.length > 0) {
                    html += `<div class="count-info">📊 Всего записей: ${data.rows.length}</div>`;
                }
                
                container.innerHTML = html;
                
                loader.classList.remove('active');
                status.style.display = 'none';
            } catch (error) {
                console.error('Ошибка:', error);
                loader.classList.remove('active');
                status.style.display = 'block';
                status.className = 'status error';
                status.innerHTML = '❌ Ошибка загрузки данных:<br><div class="error-details">' + error.message + '</div>';
            }
        }
    </script>
</body>
</html>