<?php
require_once('../config/db_config.php');

session_start();

// 檢查登入狀態
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: login.php');
    exit;
}

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS);
if ($conn->connect_error) {
    die("連接失敗: " . $conn->connect_error);
}
$conn->select_db(DB_NAME);

$message = '';
$success = false;

// 處理表單提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $found_date = $_POST['found_date'] ?? '';
    $tags = $_POST['tags'] ?? [];

    // 驗證必填欄位
    if (empty($name) || empty($found_date) || !isset($_FILES['image'])) {
        $message = "請填寫所有必填欄位";
    } else {
        try {
            // 開始資料庫交易
            $conn->begin_transaction();

            // 處理圖片上傳
            $target_dir = "../uploads/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }

            $file = $_FILES['image'];
            $imageFileType = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $newFileName = uniqid() . '.' . $imageFileType;
            $target_file = $target_dir . $newFileName;

            // 檢查圖片類型
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
            if (!in_array($imageFileType, $allowed_types)) {
                throw new Exception("只允許上傳 JPG, JPEG, PNG 或 GIF 格式的圖片");
            }

            // 檢查檔案大小 (最大 5MB)
            if ($file['size'] > 5 * 1024 * 1024) {
                throw new Exception("檔案大小不能超過 5MB");
            }

            if (!move_uploaded_file($file['tmp_name'], $target_file)) {
                throw new Exception("圖片上傳失敗");
            }

            // 插入物品資料
            $stmt = $conn->prepare("INSERT INTO items (name, image_path, found_date) VALUES (?, ?, ?)");
            $image_path = "uploads/" . $newFileName;
            $stmt->bind_param("sss", $name, $image_path, $found_date);
            
            if (!$stmt->execute()) {
                throw new Exception("新增物品失敗: " . $stmt->error);
            }
            
            $item_id = $conn->insert_id;

            // 新增標籤關聯
            if (!empty($tags)) {
                $stmt = $conn->prepare("INSERT INTO item_tags (item_id, tag_id) VALUES (?, ?)");
                foreach ($tags as $tag_id) {
                    $stmt->bind_param("ii", $item_id, $tag_id);
                    if (!$stmt->execute()) {
                        throw new Exception("新增標籤關聯失敗: " . $stmt->error);
                    }
                }
            }

            // 提交交易
            $conn->commit();
            $message = "遺失物新增成功！";
            $success = true;

            // 清空表單
            $_POST = array();
        } catch (Exception $e) {
            // 發生錯誤時回滾交易
            $conn->rollback();
            $message = $e->getMessage();
            
            // 如果圖片已上傳，刪除它
            if (isset($target_file) && file_exists($target_file)) {
                unlink($target_file);
            }
        }
    }
}

// 獲取所有標籤
$tags_result = $conn->query("SELECT * FROM tags ORDER BY name");
?>

<!DOCTYPE html>
<html>
<head>
    <title>新增遺失物</title>
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
            max-width: 800px;
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

        .form-group {
            margin-bottom: 25px;
        }

        label {
            display: block;
            margin-bottom: 10px;
            color: var(--text-color);
            font-weight: 500;
        }

        input[type="text"],
        input[type="date"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1em;
            transition: all 0.3s ease;
        }

        input[type="text"]:focus,
        input[type="date"]:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(33, 150, 243, 0.1);
        }

        input[type="file"] {
            width: 100%;
            padding: 12px;
            border: 2px dashed var(--primary-color);
            border-radius: 8px;
            background: rgba(33, 150, 243, 0.05);
            cursor: pointer;
            margin-bottom: 10px;
        }

        .tags-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .tag-item {
            background: var(--panel-bg);
            padding: 12px;
            border-radius: 8px;
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
        }

        .tag-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
        }

        .tag-item input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .tag-item label {
            margin: 0;
            cursor: pointer;
        }

        .submit-button {
            width: 100%;
            padding: 15px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1em;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 20px;
        }

        .submit-button:hover {
            background: var(--accent-color);
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

        .preview-image {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            margin-top: 10px;
            display: none;
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

            .tags-grid {
                grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            }
        }

        @media (max-width: 480px) {
            .content-section {
                padding: 15px;
            }

            .form-group {
                margin-bottom: 20px;
            }

            input[type="text"],
            input[type="date"],
            input[type="file"] {
                padding: 10px;
            }

            .tag-item {
                padding: 10px;
            }

            .submit-button {
                padding: 12px;
            }
        }
    </style>
    <script>
        // 在選擇檔案時預覽並調整圖片
        function handleImageSelect(event) {
            const file = event.target.files[0];
            if (!file) return;

            // 檢查是否為圖片
            if (!file.type.startsWith('image/')) {
                alert('請選擇圖片檔案');
                event.target.value = '';
                return;
            }

            const reader = new FileReader();
            reader.onload = function(e) {
                const img = new Image();
                img.onload = function() {
                    // 創建 canvas 來調整圖片大小
                    const canvas = document.createElement('canvas');
                    const ctx = canvas.getContext('2d');

                    // 計算新的尺寸，保持比例
                    let width = 600;
                    let height = (img.height * 600) / img.width;

                    // 設定 canvas 尺寸
                    canvas.width = width;
                    canvas.height = height;

                    // 繪製調整大小後的圖片
                    ctx.drawImage(img, 0, 0, width, height);

                    // 顯示預覽
                    const preview = document.getElementById('imagePreview');
                    preview.src = canvas.toDataURL(file.type);
                    preview.style.display = 'block';

                    // 將調整大小後的圖片資料存入隱藏的 input
                    canvas.toBlob(function(blob) {
                        // 創建新的 File 物件
                        const resizedFile = new File([blob], file.name, {
                            type: file.type,
                            lastModified: new Date().getTime()
                        });

                        // 創建新的 FileList 物件
                        const dataTransfer = new DataTransfer();
                        dataTransfer.items.add(resizedFile);
                        
                        // 更新 input file 的值
                        document.getElementById('image').files = dataTransfer.files;
                    }, file.type);
                };
                img.src = e.target.result;
            };
            reader.readAsDataURL(file);
        }

        // 表單提交前的驗證
        function validateForm() {
            const name = document.getElementById('name').value;
            const image = document.getElementById('image').files[0];
            const found_date = document.getElementById('found_date').value;
            
            if (!name || !image || !found_date) {
                alert('請填寫所有必填欄位');
                return false;
            }
            return true;
        }
    </script>
</head>
<body>
    <div class="container">
        <header class="header">
            <h1>新增遺失物</h1>
            <a href="./admin.php" class="back-link">返回管理選單</a>
        </header>

        <section class="content-section">
            <?php if (isset($message)): ?>
                <div class="message <?php echo $success ? 'success' : 'error'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" onsubmit="return validateForm()">
                <div class="form-group">
                    <label for="name">物品名稱：</label>
                    <input type="text" id="name" name="name" required>
                </div>

                <div class="form-group">
                    <label for="image">物品照片：</label>
                    <input type="file" id="image" name="image" accept="image/*" required onchange="handleImageSelect(event)">
                    <img id="imagePreview" src="#" alt="預覽圖片" style="display: none; max-width: 100%; margin-top: 10px;">
                </div>

                <div class="form-group">
                    <label for="found_date">拾獲日期：</label>
                    <input type="date" id="found_date" name="found_date" 
                           value="<?php echo date('Y-m-d'); ?>" required>
                </div>

                <div class="form-group">
                    <label>標籤：</label>
                    <div class="tags-grid">
                        <?php while ($tag = $tags_result->fetch_assoc()): ?>
                            <div class="tag-item">
                                <input type="checkbox" name="tags[]" 
                                       value="<?php echo $tag['id']; ?>"
                                       id="tag_<?php echo $tag['id']; ?>">
                                <label for="tag_<?php echo $tag['id']; ?>">
                                    <?php echo htmlspecialchars($tag['name']); ?>
                                </label>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>

                <button type="submit" class="submit-button">新增遺失物</button>
            </form>
        </section>
    </div>

    <script>
        function previewImage(input) {
            const preview = document.getElementById('preview');
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>
</html>
