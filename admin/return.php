<?php
require_once('../config/db_config.php');
$conn->select_db(DB_NAME);

$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['item_id'])) {
    $item_id = $_POST['item_id'];
    
    $sql = "UPDATE items SET status = 'returned' WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $item_id);
    
    if ($stmt->execute()) {
        $message = "物品已成功標記為已歸還！";
        $success = true;
    } else {
        $message = "Error: " . $conn->error;
    }
}

// Get all found items
$sql = "SELECT i.*, GROUP_CONCAT(t.name) as tags 
        FROM items i 
        LEFT JOIN item_tags it ON i.id = it.item_id 
        LEFT JOIN tags t ON it.tag_id = t.id 
        WHERE i.status = 'found'
        GROUP BY i.id
        ORDER BY i.found_date DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>物品歸還</title>
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
            max-width: 1200px;
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

        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }

        .success {
            background-color: rgba(76, 175, 80, 0.1);
            color: var(--success-color);
        }

        .error {
            background-color: rgba(244, 67, 54, 0.1);
            color: var(--error-color);
        }

        .items-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .item-card {
            background: var(--panel-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            overflow: hidden;
            transition: transform 0.3s ease;
        }

        .item-card:hover {
            transform: translateY(-5px);
        }

        .item-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .item-info {
            padding: 20px;
        }

        .item-name {
            font-size: 1.2em;
            font-weight: 500;
            margin-bottom: 10px;
            color: var(--accent-color);
        }

        .item-date {
            color: var(--text-color);
            opacity: 0.8;
            font-size: 0.9em;
            margin-bottom: 10px;
        }

        .item-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 15px;
        }

        .tag {
            background: var(--primary-color);
            color: white;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 0.9em;
        }

        .return-form {
            margin-top: 15px;
        }

        .return-button {
            width: 100%;
            padding: 12px;
            background: var(--success-color);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1em;
            transition: all 0.3s ease;
        }

        .return-button:hover {
            background: #43a047;
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

        .search-section {
            margin-bottom: 30px;
        }

        .search-form {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .search-input {
            flex: 1;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1em;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(33, 150, 243, 0.1);
        }

        .search-button {
            padding: 12px 24px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1em;
            transition: all 0.3s ease;
        }

        .search-button:hover {
            background: var(--accent-color);
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

            .items-grid {
                grid-template-columns: 1fr;
            }

            .search-form {
                flex-direction: column;
            }

            .search-button {
                width: 100%;
            }

            h1 {
                font-size: 1.5em;
            }
        }

        @media (max-width: 480px) {
            .content-section {
                padding: 15px;
            }

            .item-image {
                height: 150px;
            }

            .item-info {
                padding: 15px;
            }

            .message {
                padding: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="header">
            <h1>物品歸還</h1>
            <a href="./admin.php" class="back-link">返回管理選單</a>
        </header>

        <section class="content-section">
            <?php if (isset($message)): ?>
                <div class="message <?php echo $success ? 'success' : 'error'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <div class="search-section">
                <form method="GET" class="search-form">
                    <input type="text" name="search" placeholder="搜尋物品名稱..." 
                           value="<?php echo htmlspecialchars($search ?? ''); ?>" 
                           class="search-input">
                    <button type="submit" class="search-button">搜尋</button>
                </form>
            </div>

            <div class="items-grid">
                <?php if ($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <div class="item-card">
                            <img src="../<?php echo htmlspecialchars($row['image_path']); ?>" 
                                 alt="<?php echo htmlspecialchars($row['name']); ?>"
                                 class="item-image">
                            <div class="item-info">
                                <div class="item-name"><?php echo htmlspecialchars($row['name']); ?></div>
                                <div class="item-date">拾獲日期：<?php echo $row['found_date']; ?></div>
                                <?php if($row['tags']): ?>
                                    <div class="item-tags">
                                        <?php foreach(explode(',', $row['tags']) as $tag): ?>
                                            <span class="tag"><?php echo htmlspecialchars($tag); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                <form method="POST" class="return-form">
                                    <input type="hidden" name="item_id" value="<?php echo $row['id']; ?>">
                                    <button type="submit" class="return-button" 
                                            onclick="return confirm('確定要將此物品標記為已歸還？');">
                                        標記為已歸還
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="message">
                        目前沒有待歸還的物品。
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>
</body>
</html>
