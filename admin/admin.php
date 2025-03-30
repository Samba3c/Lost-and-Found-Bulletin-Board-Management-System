<?php
require_once('../config/db_config.php');

// 開始 session
session_start();

// 檢查登入狀態
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: login.php');
    exit;
}

// 連接資料庫
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS);
if ($conn->connect_error) {
    die("連接失敗: " . $conn->connect_error);
}
$conn->select_db(DB_NAME);

// 初始化統計數據
$stats = [
    'total_items' => 0,
    'unreturned_items' => 0,
    'returned_items' => 0,
    'total_tags' => 0
];

try {
    // 檢查並修正資料庫連接的字符集
    $conn->set_charset('utf8mb4');
    
    // 計算待領取物品數（狀態為 found 的物品）
    $sql = "SELECT COUNT(*) as unreturned FROM items WHERE status = 'found'";
    $result = $conn->query($sql);
    $stats['unreturned_items'] = $result->fetch_assoc()['unreturned'];

    // 計算已歸還物品數（狀態為 returned 的物品）
    $sql = "SELECT COUNT(*) as returned FROM items WHERE status = 'returned'";
    $result = $conn->query($sql);
    $stats['returned_items'] = $result->fetch_assoc()['returned'];

    // 計算總物品數（待領取 + 已歸還）
    $stats['total_items'] = $stats['unreturned_items'] + $stats['returned_items'];

    // 計算標籤總數（計算所有標籤）
    $sql = "SELECT COUNT(*) as total_tags FROM tags";
    $result = $conn->query($sql);
    $stats['total_tags'] = $result->fetch_assoc()['total_tags'];

} catch (Exception $e) {
    error_log("統計數據查詢錯誤: " . $e->getMessage());
    // 設置預設值
    $stats = [
        'total_items' => 0,
        'unreturned_items' => 0,
        'returned_items' => 0,
        'total_tags' => 0
    ];
} finally {
    $conn->close();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>遺失物管理系統</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
            --warning-color: #ff9800;
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
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .header-content {
            flex-grow: 1;
            text-align: center;
        }

        h1 {
            color: var(--primary-color);
            font-size: 2em;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .username {
            color: var(--accent-color);
            font-weight: 500;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--panel-bg);
            padding: 20px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            text-align: center;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            font-size: 2em;
            margin-bottom: 10px;
            color: var(--primary-color);
        }

        .stat-value {
            font-size: 2em;
            font-weight: 700;
            color: var(--accent-color);
            margin-bottom: 5px;
        }

        .stat-label {
            color: var(--text-color);
            opacity: 0.8;
        }

        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }

        .menu-item {
            background: var(--panel-bg);
            padding: 20px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            text-decoration: none;
            color: var(--text-color);
            transition: all 0.3s ease;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            min-height: 200px;
        }

        .menu-item:hover {
            transform: translateY(-5px);
            background: var(--primary-color);
            color: white;
        }

        .menu-icon {
            font-size: 2.5em;
            margin-bottom: 15px;
        }

        .menu-title {
            font-size: 1.2em;
            font-weight: 500;
            margin-bottom: 10px;
        }

        .menu-description {
            font-size: 0.9em;
            opacity: 0.8;
            line-height: 1.4;
        }

        .button {
            display: inline-block;
            padding: 10px 20px;
            background: var(--panel-bg);
            color: var(--primary-color);
            text-decoration: none;
            border-radius: 20px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-family: inherit;
            font-size: 1em;
        }

        .button:hover {
            background: var(--primary-color);
            color: white;
        }

        .button-danger {
            color: var(--error-color);
        }

        .button-danger:hover {
            background: var(--error-color);
            color: white;
        }

        @media (max-width: 768px) {
            body {
                padding: 10px;
            }

            .header {
                padding: 20px;
            }

            h1 {
                font-size: 1.8em;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .menu-item {
                min-height: 180px;
            }

            .menu-icon {
                font-size: 2em;
            }
        }

        @media (max-width: 480px) {
            .header {
                padding: 15px;
                flex-direction: column;
                text-align: center;
            }

            .user-info {
                flex-direction: column;
                gap: 10px;
            }

            h1 {
                font-size: 1.5em;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .menu-grid {
                grid-template-columns: 1fr;
            }

            .menu-item {
                min-height: 160px;
            }

            .stat-value {
                font-size: 1.6em;
            }

            .menu-title {
                font-size: 1.1em;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="header">
            <div class="header-content">
                <h1>遺失物管理系統</h1>
            </div>
            <div class="user-info">
                <span class="username">
                    <i class="fas fa-user"></i> 
                    <?php echo htmlspecialchars($_SESSION['admin_username'] ?? '管理員'); ?>
                </span>
                <a href="logout.php" class="button button-danger">
                    <i class="fas fa-sign-out-alt"></i> 登出系統
                </a>
            </div>
        </header>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-box"></i>
                </div>
                <div class="stat-value"><?php echo $stats['total_items']; ?></div>
                <div class="stat-label">總物品數</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-value"><?php echo $stats['unreturned_items']; ?></div>
                <div class="stat-label">待領取物品</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-value"><?php echo $stats['returned_items']; ?></div>
                <div class="stat-label">已歸還物品</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-tags"></i>
                </div>
                <div class="stat-value"><?php echo $stats['total_tags']; ?></div>
                <div class="stat-label">標籤數量</div>
            </div>
        </div>

        <div class="menu-grid">
            <a href="add.php" class="menu-item">
                <div class="menu-icon">
                    <i class="fas fa-plus-circle"></i>
                </div>
                <div class="menu-title">新增遺失物</div>
                <div class="menu-description">登記新的遺失物品，包含照片和詳細資訊</div>
            </a>
            <a href="return.php" class="menu-item">
                <div class="menu-icon">
                    <i class="fas fa-undo"></i>
                </div>
                <div class="menu-title">物品歸還</div>
                <div class="menu-description">處理遺失物歸還程序和狀態更新</div>
            </a>
            <a href="tags.php" class="menu-item">
                <div class="menu-icon">
                    <i class="fas fa-tag"></i>
                </div>
                <div class="menu-title">標籤管理</div>
                <div class="menu-description">管理物品分類標籤系統</div>
            </a>
            <a href="../" class="menu-item">
                <div class="menu-icon">
                    <i class="fas fa-home"></i>
                </div>
                <div class="menu-title">返回首頁</div>
                <div class="menu-description">回到遺失物公告欄首頁</div>
            </a>
        </div>
    </div>
</body>
</html>
