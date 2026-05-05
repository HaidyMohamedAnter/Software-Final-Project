CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    email VARCHAR(100) UNIQUE,
    password VARCHAR(255),
    user_type ENUM('admin','user') DEFAULT 'user'
);


CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    price DECIMAL(10,2),
    image VARCHAR(255),
    quantity INT DEFAULT 0
);


CREATE TABLE cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    name VARCHAR(100),
    price DECIMAL(10,2),
    quantity INT,
    image VARCHAR(255),
    product_id INT,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);


CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    name VARCHAR(100),
    number VARCHAR(20),
    email VARCHAR(100),
    method VARCHAR(50),
    address TEXT,
    total_products TEXT,
    total_price DECIMAL(10,2),
    placed_on VARCHAR(50),
    payment_status VARCHAR(20) DEFAULT 'pending',
    product_id INT,
    quantity INT,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);


CREATE TABLE message (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    name VARCHAR(100),
    email VARCHAR(100),
    number VARCHAR(20),
    message TEXT,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);


