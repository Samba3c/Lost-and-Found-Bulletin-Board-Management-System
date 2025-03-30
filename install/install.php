<?php
require_once('../config/db_config.php');

// Create database
$sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
if ($conn->query($sql) === TRUE) {
    echo "Database created successfully<br>";
} else {
    echo "Error creating database: " . $conn->error . "<br>";
}

// Select the database
$conn->select_db(DB_NAME);

// Create items table
$sql = "CREATE TABLE IF NOT EXISTS items (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    found_date DATE NOT NULL,
    status ENUM('found', 'returned') DEFAULT 'found',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Items table created successfully<br>";
} else {
    echo "Error creating items table: " . $conn->error . "<br>";
}

// Create tags table
$sql = "CREATE TABLE IF NOT EXISTS tags (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE
)";

if ($conn->query($sql) === TRUE) {
    echo "Tags table created successfully<br>";
} else {
    echo "Error creating tags table: " . $conn->error . "<br>";
}

// Create item_tags table for many-to-many relationship
$sql = "CREATE TABLE IF NOT EXISTS item_tags (
    item_id INT(11),
    tag_id INT(11),
    PRIMARY KEY (item_id, tag_id),
    FOREIGN KEY (item_id) REFERENCES items(id),
    FOREIGN KEY (tag_id) REFERENCES tags(id)
)";

if ($conn->query($sql) === TRUE) {
    echo "Item_tags table created successfully<br>";
} else {
    echo "Error creating item_tags table: " . $conn->error . "<br>";
}

$conn->close();
?>
