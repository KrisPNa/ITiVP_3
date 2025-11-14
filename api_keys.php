<?php
require_once 'config/Database.php';
require_once 'config/Auth.php';

$demo_user_id = 1;

$auth = new Auth();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление API ключами</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        .block {
            border: 1px solid #4a90e2;
            padding: 20px;
            margin-bottom: 20px;
            background: #e8f4fd;
        }
        .api-key-display {
            border: 1px solid #333;
            padding: 10px;
            font-family: monospace;
            margin: 10px 0;
            display: inline-block;
        }
        button {
            background: #4a90e2;
            color: white;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <?php
    if ($_POST['action'] ?? '' === 'generate_key') {
        $result = $auth->createApiKey($demo_user_id);
        
        if ($result) {
            echo '<div class="block">';
            echo '<p>Новый API ключ создан!</p>';
            echo '<div class="api-key-display">' . $result['plain_key'] . '</div>';
            echo '<p>Сохраните этот ключ! Он больше не будет показан.</p>';
            echo '</div>';
        }
    }
    ?>
    
    <div class="block">
        <form method="POST">
            <input type="hidden" name="action" value="generate_key">
            <button type="submit">Сгенерировать новый API ключ</button>
        </form>
    </div>
</body>
</html>