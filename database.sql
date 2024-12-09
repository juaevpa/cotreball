CREATE DATABASE IF NOT EXISTS cotreball;
USE cotreball;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    is_admin BOOLEAN DEFAULT FALSE,
    privacy_accepted BOOLEAN DEFAULT FALSE,
    terms_accepted BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS spaces (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    address VARCHAR(255) NOT NULL,
    city VARCHAR(100) NOT NULL,
    lat DECIMAL(10, 8) NOT NULL,
    lng DECIMAL(11, 8) NOT NULL,
    price DECIMAL(10, 2) NULL,
    price_month DECIMAL(10, 2) NULL,
    available BOOLEAN DEFAULT TRUE,
    approved BOOLEAN DEFAULT FALSE,
    data_processing_accepted BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

INSERT INTO users (username, email, password, is_admin, privacy_accepted, terms_accepted) 
VALUES ('admin', 'admin@cotreball.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', TRUE, TRUE, TRUE);

ALTER TABLE users 
ADD COLUMN IF NOT EXISTS privacy_accepted BOOLEAN DEFAULT FALSE,
ADD COLUMN IF NOT EXISTS terms_accepted BOOLEAN DEFAULT FALSE;

ALTER TABLE spaces
ADD COLUMN IF NOT EXISTS data_processing_accepted BOOLEAN DEFAULT FALSE;

-- Modificar columnas para permitir NULL
ALTER TABLE spaces
MODIFY COLUMN price DECIMAL(10, 2) NULL,
MODIFY COLUMN price_month DECIMAL(10, 2) NULL; 