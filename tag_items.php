<?php
require_once('config/db_config.php');
$conn->select_db(DB_NAME);

if (!isset($_GET['tag_id'])) {
    header('Location: index.php');
    exit;
}

$tag_id = $_GET['tag_id'];

// Get tag name
$sql = "SELECT name FROM tags WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $tag_id);
$stmt->execute();
$tag_result = $stmt->get_result();
$tag = $tag_result->fetch_assoc();

if (!$tag) {
    header('Location: index.php');
    exit;
}

// Get items with this tag
$sql = "SELECT i.* 
        FROM items i 
        JOIN item_tags it ON i.id = it.item_id 
        WHERE it.tag_id = ? 
        AND i.status = 'found' 
        ORDER BY i.found_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $tag_id);
$stmt->execute();
$items_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>標籤物品列表</title>
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
            padding: 30px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            margin-bottom: 30px;
            text-align: center;
        }

        h1 {
            color: var(--primary-color);
            margin-bottom: 20px;
            font-size: 2em;
            font-weight: 700;
        }

        .tag-name {
            color: var(--accent-color);
            font-size: 1.2em;
            margin-bottom: 10px;
        }

        .items-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            padding: 20px 0;
        }

        .item-card {
            background: var(--panel-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            overflow: hidden;
            transition: transform 0.3s ease;
            aspect-ratio: 3/4;
            display: flex;
            flex-direction: column;
        }

        .item-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }

        .item-image {
            width: 100%;
            flex: 1;
            object-fit: cover;
        }

        .item-info {
            padding: 20px;
            background: var(--panel-bg);
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

        .item-status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 0.9em;
            margin-top: 10px;
        }

        .status-available {
            background-color: rgba(76, 175, 80, 0.1);
            color: var(--success-color);
        }

        .status-returned {
            background-color: rgba(244, 67, 54, 0.1);
            color: var(--error-color);
        }

        .back-link {
            display: inline-block;
            padding: 10px 20px;
            color: var(--primary-color);
            text-decoration: none;
            border-radius: 20px;
            transition: all 0.3s ease;
            margin-top: 20px;
        }

        .back-link:hover {
            background: rgba(33, 150, 243, 0.1);
        }

        .no-items {
            text-align: center;
            padding: 40px;
            background: var(--panel-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            color: var(--text-color);
            font-size: 1.1em;
        }

        .tag-description {
            color: var(--text-color);
            opacity: 0.8;
            margin-bottom: 20px;
            text-align: center;
        }

        @media (max-width: 768px) {
            body {
                padding: 10px;
            }

            .container {
                width: 100%;
            }

            .header {
                padding: 20px;
            }

            h1 {
                font-size: 1.8em;
            }

            .items-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                padding: 10px 0;
            }

            .item-image {
                height: 180px;
            }
        }

        @media (max-width: 480px) {
            .header {
                padding: 15px;
            }

            h1 {
                font-size: 1.5em;
            }

            .items-grid {
                grid-template-columns: 1fr;
            }

            .item-card {
                margin-bottom: 15px;
            }

            .item-image {
                height: 160px;
            }

            .item-info {
                padding: 15px;
            }

            .item-name {
                font-size: 1.1em;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="header">
            <h1>標籤物品列表</h1>
            <?php if ($tag): ?>
                <div class="tag-description">
                    顯示所有標記為<div class="tag-name"><?php echo htmlspecialchars($tag['name']); ?></div>的物品
                </div>
            <?php endif; ?>
            <a href="index.php" class="back-link">返回首頁</a>
        </header>

        <?php if ($items_result && $items_result->num_rows > 0): ?>
            <div class="items-grid">
                <?php while ($item = $items_result->fetch_assoc()): ?>
                    <div class="item-card">
                        <img src="<?php echo htmlspecialchars($item['image_path']); ?>" 
                             alt="<?php echo htmlspecialchars($item['name']); ?>"
                             class="item-image">
                        <div class="item-info">
                            <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                            <div class="item-date">拾獲日期：<?php echo $item['found_date']; ?></div>
                            <div class="item-status <?php echo $item['returned'] ? 'status-returned' : 'status-available'; ?>">
                                <?php echo $item['returned'] ? '已歸還' : '待領取'; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="no-items">
                目前沒有相關的遺失物品。
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
