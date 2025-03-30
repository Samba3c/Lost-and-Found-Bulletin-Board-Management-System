<?php
require_once('config/db_config.php');
$conn->select_db(DB_NAME);

// 獲取要查詢的週期
$week = $_GET['week'] ?? 'current';

// 設定日期範圍
switch ($week) {
    case 'prev':
        // 上週一
        $start_date = date('Y-m-d', strtotime('monday last week'));
        // 上週六
        $end_date = date('Y-m-d', strtotime('saturday last week'));
        break;
    case 'prev2':
        // 上上週一
        $start_date = date('Y-m-d', strtotime('monday 2 weeks ago'));
        // 上上週六
        $end_date = date('Y-m-d', strtotime('saturday 2 weeks ago'));
        break;
    default:
        // 本週一
        $start_date = date('Y-m-d', strtotime('monday this week'));
        // 今天
        $end_date = date('Y-m-d');
        break;
}

// Get items for the selected period
$sql = "SELECT i.*, GROUP_CONCAT(t.name) as tags 
        FROM items i 
        LEFT JOIN item_tags it ON i.id = it.item_id 
        LEFT JOIN tags t ON it.tag_id = t.id 
        WHERE i.found_date >= ? 
        AND i.found_date <= ?
        AND i.status = 'found'
        GROUP BY i.id
        ORDER BY i.found_date DESC, i.id DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();

// Get tag cloud data for the selected period
$sql = "SELECT t.*, COUNT(*) as count 
        FROM tags t 
        JOIN item_tags it ON t.id = it.tag_id 
        JOIN items i ON it.item_id = i.id 
        WHERE i.found_date >= ? 
        AND i.found_date <= ?
        AND i.status = 'found'
        GROUP BY t.id
        ORDER BY COUNT(*) DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$tagResult = $stmt->get_result();

// 計算總共有多少遺失物
$sql = "SELECT COUNT(*) as total FROM items WHERE status = 'found'";
$totalResult = $conn->query($sql);
$totalItems = $totalResult->fetch_assoc()['total'];

// 計算本週已歸還的物品數
$sql = "SELECT COUNT(*) as returned FROM items WHERE status = 'returned' AND found_date >= ? AND found_date <= ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$returnedResult = $stmt->get_result();
$returnedItems = $returnedResult->fetch_assoc()['returned'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>失物招領公佈欄</title>
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
        }

        .header {
            background: var(--panel-bg);
            padding: 20px;
            box-shadow: var(--shadow);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .site-title {
            color: var(--primary-color);
            font-size: 1.8em;
            font-weight: 700;
        }

        .stats {
            display: flex;
            gap: 20px;
        }

        .stat-item {
            background: var(--panel-bg);
            padding: 10px 20px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
        }

        .stat-number {
            color: var(--primary-color);
            font-size: 1.2em;
            font-weight: 700;
        }

        .main-container {
            max-width: 1400px;
            margin: 20px auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: 3fr 1fr;
            gap: 20px;
        }

        .slideshow-section {
            background: var(--panel-bg);
            padding: 20px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
        }

        .slideshow {
            position: relative;
            border-radius: var(--border-radius);
            overflow: hidden;
            margin-bottom: 20px;
        }

        .slideshow-container {
            display: flex;
            transition: transform 0.5s ease;
        }

        .slide {
            flex: 0 0 33.333%;
            padding: 10px;
        }

        .slide-content {
            position: relative;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--shadow);
        }

        .slide img {
            width: 100%;
            height: 300px;
            object-fit: cover;
            border-radius: var(--border-radius);
        }

        .slide-info {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 15px;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            border-bottom-left-radius: var(--border-radius);
            border-bottom-right-radius: var(--border-radius);
        }

        .slide-controls {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 10px;
        }

        .control-button {
            padding: 8px 16px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .control-button:hover {
            background: var(--accent-color);
        }

        .tag-section {
            background: var(--panel-bg);
            padding: 20px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
        }

        .tag-cloud {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .tag {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 20px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            text-decoration: none;
            border-radius: var(--border-radius);
            transition: all 0.3s ease;
        }

        .tag:hover {
            transform: translateX(5px);
            box-shadow: var(--shadow);
        }

        .tag-name {
            font-size: 1.2em;
            font-weight: 500;
        }

        .tag-count {
            background: rgba(255, 255, 255, 0.2);
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 0.9em;
        }

        .footer {
            background: var(--panel-bg);
            padding: 20px;
            margin-top: 40px;
            box-shadow: var(--shadow);
        }

        .footer-content {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .footer-links {
            display: flex;
            gap: 20px;
        }

        .footer-link {
            color: var(--primary-color);
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 20px;
            transition: all 0.3s ease;
        }

        .footer-link:hover {
            background: var(--primary-color);
            color: white;
        }

        @media (max-width: 768px) {
            .main-container {
                grid-template-columns: 1fr;
            }

            .header-content {
                flex-direction: column;
                gap: 15px;
            }

            .stats {
                flex-direction: column;
            }

            .slide {
                flex: 0 0 100%;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <h1 class="site-title">失物招領公佈欄</h1>
            <div class="stats">
                <div class="stat-item">
                    <span class="stat-number"><?php echo $totalItems; ?></span>
                    <span class="stat-label">個待領取物品</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?php echo $returnedItems; ?></span>
                    <span class="stat-label">個本週歸還物品</span>
                </div>
            </div>
        </div>
    </header>

    <main class="main-container">
        <section class="slideshow-section">
            <div class="slideshow">
                <div class="slideshow-container">
                    <?php while($row = $result->fetch_assoc()): ?>
                        <div class="slide">
                            <div class="slide-content">
                                <img src="<?php echo htmlspecialchars($row['image_path']); ?>" 
                                     alt="<?php echo htmlspecialchars($row['name']); ?>">
                                <div class="slide-info">
                                    <h3><?php echo htmlspecialchars($row['name']); ?></h3>
                                    <p>拾獲日期：<?php echo $row['found_date']; ?></p>
                                    <?php if($row['tags']): ?>
                                        <p>標籤：<?php echo htmlspecialchars($row['tags']); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
            <div class="slide-controls">
                <button class="control-button" onclick="prevSlide()">上一組</button>
                <button class="control-button" onclick="nextSlide()">下一組</button>
            </div>
        </section>

        <section class="tag-section">
            <div class="tag-cloud">
                <?php while($tag = $tagResult->fetch_assoc()): ?>
                    <a href="tag_items.php?tag_id=<?php echo $tag['id']; ?>" class="tag">
                        <span class="tag-name"><?php echo htmlspecialchars($tag['name']); ?></span>
                        <span class="tag-count"><?php echo $tag['count']; ?></span>
                    </a>
                <?php endwhile; ?>
            </div>
        </section>
    </main>

    <footer class="footer">
        <div class="footer-content">
            <div class="footer-links">
                <a href="admin/admin.php" class="footer-link">管理後台</a>
                <a href="?week=prev" class="footer-link">上週遺失物</a>
                <a href="?week=prev2" class="footer-link">上上週遺失物</a>
            </div>
        </div>
    </footer>

    <script>
        let currentPosition = 0;
        const container = document.querySelector('.slideshow-container');
        const slides = document.querySelectorAll('.slide');
        const totalSlides = slides.length;
        const slidesPerView = window.innerWidth <= 768 ? 1 : 3;

        function updateSlidePosition() {
            container.style.transform = `translateX(-${currentPosition * (100 / slidesPerView)}%)`;
        }

        function nextSlide() {
            if (currentPosition < totalSlides - slidesPerView) {
                currentPosition++;
            } else {
                currentPosition = 0;
            }
            updateSlidePosition();
        }

        function prevSlide() {
            if (currentPosition > 0) {
                currentPosition--;
            } else {
                currentPosition = totalSlides - slidesPerView;
            }
            updateSlidePosition();
        }

        // 自動輪播
        setInterval(nextSlide, 5000);

        // 響應式調整
        window.addEventListener('resize', () => {
            const newSlidesPerView = window.innerWidth <= 768 ? 1 : 3;
            if (slidesPerView !== newSlidesPerView) {
                location.reload();
            }
        });
    </script>
</body>
</html>
