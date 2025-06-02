<?php
$servername = "localhost";
$username = "root";
$password = "";
$database = "crud"; // Ensure the database name is correct

// Create connection with error handling
$conn = new mysqli($servername, $username, $password);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8
$conn->set_charset("utf8");

// Create database if it doesn't exist
$db_check_query = "CREATE DATABASE IF NOT EXISTS $database";
if ($conn->query($db_check_query) === TRUE) {
    $conn->select_db($database);

    // Create 'users' table if it doesn't exist
    $users_table_query = "
    CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        role ENUM('Admin', 'Cashier') NOT NULL,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL
    )";

    if ($conn->query($users_table_query) === TRUE) {
        // Users table created successfully or already exists
    } else {
        echo "Error creating users table: " . $conn->error;
    }

    // Ensure 'role' column exists in 'users' table
    $check_role_column = $conn->query("SHOW COLUMNS FROM users LIKE 'role'");
    if ($check_role_column->num_rows == 0) {
        $conn->query("ALTER TABLE users ADD COLUMN role ENUM('Admin', 'Cashier') NOT NULL DEFAULT 'Cashier'");
    }

    // Create 'inventory' table if it doesn't exist
    $inventory_table_query = "
    CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    category VARCHAR(100) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    stock INT NOT NULL,
    image VARCHAR(255)
    )";

    if ($conn->query($inventory_table_query) === TRUE) {
        // Inventory table created successfully or already exists
    } else {
        echo "Error creating inventory table: " . $conn->error;
    }

    // Create suppliers table
    $suppliers_table_query = "
    CREATE TABLE IF NOT EXISTS suppliers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        contact VARCHAR(100),
        address VARCHAR(255)
    )";
    $conn->query($suppliers_table_query);

    // Create purchases table
    $purchases_table_query = "
    CREATE TABLE IF NOT EXISTS purchases (
        id INT AUTO_INCREMENT PRIMARY KEY,
        supplier_id INT,
        user_id INT,
        purchase_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        total DECIMAL(10,2) NOT NULL,
        FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
        FOREIGN KEY (user_id) REFERENCES users(id)
    )";
    $conn->query($purchases_table_query);

    // Create purchase_details table
    $purchase_details_table_query = "
    CREATE TABLE IF NOT EXISTS purchase_details (
        id INT AUTO_INCREMENT PRIMARY KEY,
        purchase_id INT,
        product_id INT,
        quantity INT NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        FOREIGN KEY (purchase_id) REFERENCES purchases(id),
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    )";
    $conn->query($purchase_details_table_query);

    // Create sales table
    $sales_table_query = "
    CREATE TABLE IF NOT EXISTS sales (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        sale_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        total DECIMAL(10,2) NOT NULL,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )";
    $conn->query($sales_table_query);

    // Create sales_details table
    $sales_details_table_query = "
    CREATE TABLE IF NOT EXISTS sales_details (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sale_id INT,
        product_id INT,
        quantity INT NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        FOREIGN KEY (sale_id) REFERENCES sales(id),
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    )";
    $conn->query($sales_details_table_query);

    // Create returns table
    $returns_table_query = "
    CREATE TABLE IF NOT EXISTS returns (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sale_id INT,
        user_id INT,
        return_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        reason VARCHAR(255),
        FOREIGN KEY (sale_id) REFERENCES sales(id),
        FOREIGN KEY (user_id) REFERENCES users(id)
    )";
    $conn->query($returns_table_query);

    // Create return_details table
    $return_details_table_query = "
    CREATE TABLE IF NOT EXISTS return_details (
        id INT AUTO_INCREMENT PRIMARY KEY,
        return_id INT,
        product_id INT,
        quantity INT NOT NULL,
        FOREIGN KEY (return_id) REFERENCES returns(id),
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    )";
    $conn->query($return_details_table_query);

    // Create purchase_returns table
    $purchase_returns_table_query = "
    CREATE TABLE IF NOT EXISTS purchase_returns (
        id INT AUTO_INCREMENT PRIMARY KEY,
        purchase_id INT,
        user_id INT,
        return_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        reason VARCHAR(255),
        FOREIGN KEY (purchase_id) REFERENCES purchases(id),
        FOREIGN KEY (user_id) REFERENCES users(id)
    )";
    $conn->query($purchase_returns_table_query);

    // Create purchase_return_details table
    $purchase_return_details_table_query = "
    CREATE TABLE IF NOT EXISTS purchase_return_details (
        id INT AUTO_INCREMENT PRIMARY KEY,
        purchase_return_id INT,
        product_id INT,
        quantity INT NOT NULL,
        FOREIGN KEY (purchase_return_id) REFERENCES purchase_returns(id),
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    )";
    $conn->query($purchase_return_details_table_query);
} else {
    echo "Error creating database: " . $conn->error;
}

?>
