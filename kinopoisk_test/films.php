<?php
// films.php - специально для таблицы с фильмами
require_once 'config.php';

$db = AccessDB::getInstance();
$tableName = "films"; // Название вашей таблицы

// Получаем данные
$sql = "SELECT * FROM [{$tableName}]";
$films = $db->query($sql);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Фильмы из Access</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        table { border-collapse: collapse; width: 100%; }
        th { background: #667eea; color: white; padding: 10px; }
        td { padding: 10px; border-bottom: 1px solid #ddd; }
        tr:hover { background: #f5f5f5; }
        .count { margin-top: 20px; color: #666; }
    </style>
</head>
<body>
    <h1>🎬 Фильмы</h1>
    <table>
        <thead>
            <tr>
                <?php if (!empty($films)): ?>
                    <?php foreach(array_keys($films[0]) as $field): ?>
                        <th><?= htmlspecialchars($field) ?></th>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach($films as $film): ?>
                <tr>
                    <?php foreach($film as $value): ?>
                        <td><?= htmlspecialchars($value ?? '-') ?></td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <div class="count">Всего фильмов: <?= count($films) ?></div>
</body>
</html>