<?php
require_once('../config/db_config.php');
$conn->select_db(DB_NAME);

$message = '';
$success = false;

// Add new tag
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add' && !empty($_POST['tag_name'])) {
        $tag_name = trim($_POST['tag_name']);
        
        // 檢查標籤是否已存在
        $check_sql = "SELECT COUNT(*) as count FROM tags WHERE name = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $tag_name);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        $exists = $result->fetch_assoc()['count'] > 0;
        
        if ($exists) {
            $message = "標籤「" . htmlspecialchars($tag_name) . "」已經存在！";
            $success = false;
        } else {
            $sql = "INSERT INTO tags (name) VALUES (?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $tag_name);
            
            if ($stmt->execute()) {
                $message = "標籤新增成功！";
                $success = true;
            } else {
                $message = "Error: " . $conn->error;
                $success = false;
            }
        }
    }
    // Delete tag
    elseif ($_POST['action'] === 'delete' && isset($_POST['tag_id'])) {
        // First check if tag is in use
        $sql = "SELECT COUNT(*) as count FROM item_tags WHERE tag_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $_POST['tag_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $count = $result->fetch_assoc()['count'];

        if ($count > 0) {
            $message = "無法刪除：此標籤正在使用中";
        } else {
            $sql = "DELETE FROM tags WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $_POST['tag_id']);
            
            if ($stmt->execute()) {
                $message = "標籤刪除成功！";
                $success = true;
            } else {
                $message = "Error: " . $conn->error;
            }
        }
    }
}

// Get all tags
$sql = "SELECT t.*, COUNT(it.item_id) as usage_count 
        FROM tags t 
        LEFT JOIN item_tags it ON t.id = it.tag_id 
        GROUP BY t.id 
        ORDER BY t.name";
$tags_result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>標籤管理</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2196f3;
            --secondary-color: #64b5f6;
            --background-color: #f5f5f5;
            --text-color: #333333;
            --accent-color: #1976d2;
            --panel-bg: #ffffff;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --border-radius: 12px;
            --success-color: #4caf50;
            --error-color: #f44336;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Noto Sans TC', sans-serif;
            background-color: var(--background-color);
            color: var(--text-color);
            line-height: 1.6;
            padding: 20px;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
        }

        .header {
            background: var(--panel-bg);
            padding: 20px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            margin-bottom: 30px;
            text-align: center;
        }

        h1 {
            color: var(--primary-color);
            margin-bottom: 20px;
            font-size: 2em;
        }

        .content-section {
            background: var(--panel-bg);
            padding: 30px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-color);
            font-weight: 500;
        }

        input[type="text"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1em;
            transition: all 0.3s ease;
        }

        input[type="text"]:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(33, 150, 243, 0.1);
        }

        .button {
            display: inline-block;
            padding: 12px 24px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1em;
            transition: all 0.3s ease;
        }

        .button:hover {
            background: var(--accent-color);
            transform: translateY(-2px);
        }

        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .success {
            background-color: rgba(76, 175, 80, 0.1);
            color: var(--success-color);
        }

        .error {
            background-color: rgba(244, 67, 54, 0.1);
            color: var(--error-color);
        }

        .tags-list {
            margin-top: 30px;
        }

        .tag-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background: rgba(33, 150, 243, 0.05);
            border-radius: 8px;
            margin-bottom: 10px;
            transition: all 0.3s ease;
        }

        .tag-item:hover {
            background: rgba(33, 150, 243, 0.1);
        }

        .tag-name {
            font-weight: 500;
        }

        .tag-actions {
            display: flex;
            gap: 10px;
        }

        .delete-button {
            background: var(--error-color);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .delete-button:hover {
            background: #d32f2f;
            transform: translateY(-2px);
        }

        .back-link {
            display: inline-block;
            padding: 10px 20px;
            color: var(--primary-color);
            text-decoration: none;
            border-radius: 20px;
            transition: all 0.3s ease;
        }

        .back-link:hover {
            background: rgba(33, 150, 243, 0.1);
        }

        @media (max-width: 768px) {
            body {
                padding: 10px;
            }

            .container {
                width: 100%;
            }

            .content-section {
                padding: 20px;
            }

            h1 {
                font-size: 1.5em;
            }

            .tag-item {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }

            .tag-actions {
                width: 100%;
                justify-content: center;
            }

            .button, .delete-button {
                width: 100%;
                text-align: center;
            }
        }

        @media (max-width: 480px) {
            .content-section {
                padding: 15px;
            }

            .message {
                padding: 12px;
            }

            input[type="text"] {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="header">
            <h1>標籤管理</h1>
            <a href="./admin.php" class="back-link">返回管理選單</a>
        </header>

        <section class="content-section">
            <?php if (isset($message)): ?>
                <div class="message <?php echo $success ? 'success' : 'error'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="tag_name">新增標籤：</label>
                    <input type="text" id="tag_name" name="tag_name" required>
                </div>
                <button type="submit" name="action" value="add" class="button">新增標籤</button>
            </form>

            <div class="tags-list">
                <?php while ($tag = $tags_result->fetch_assoc()): ?>
                    <div class="tag-item">
                        <span class="tag-name"><?php echo htmlspecialchars($tag['name']); ?></span>
                        <div class="tag-actions">
                            <form method="POST" action="" style="display: inline;">
                                <input type="hidden" name="tag_id" value="<?php echo $tag['id']; ?>">
                                <button type="submit" name="action" value="delete" class="delete-button"
                                        onclick="return confirm('確定要刪除這個標籤嗎？');">
                                    刪除
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </section>
    </div>
</body>
</html>
