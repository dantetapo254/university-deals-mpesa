CREATE DATABASE IF NOT EXISTS university_deals
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE university_deals;

-- 1. USER ACCOUNTS TABLE
-- Holds user profiles, authentication hashes, and real-time wallet balance.
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL,
    password VARCHAR(255) NOT NULL,
    wallet_balance DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. WALLET TRANSACTIONS AUDIT LOG
-- Records all balance movements (deposits and withdrawals) for ledger auditing.
CREATE TABLE IF NOT EXISTS wallet_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('deposit', 'withdrawal') NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    status ENUM('pending', 'completed', 'failed') NOT NULL DEFAULT 'pending',
    mpesa_receipt VARCHAR(100) DEFAULT NULL,
    transaction_reference VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 3. M-PESA DARAJA GATEWAY LOG
-- Stores raw API parameters, checkout request IDs, and Safaricom callback metadata.
CREATE TABLE IF NOT EXISTS mpesa_transactions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    order_id VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    status ENUM('PENDING','PAID','FAILED') NOT NULL DEFAULT 'PENDING',
    merchant_request_id VARCHAR(100) NULL,
    checkout_request_id VARCHAR(100) NULL,
    response_code VARCHAR(20) NULL,
    response_description VARCHAR(255) NULL,
    result_code INT NULL,
    result_description VARCHAR(255) NULL,
    mpesa_receipt VARCHAR(50) NULL,
    paid_amount DECIMAL(12,2) NULL,
    paid_phone VARCHAR(20) NULL,
    transaction_date VARCHAR(30) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_order_id (order_id),
    INDEX idx_checkout_request_id (checkout_request_id),
    INDEX idx_mpesa_receipt (mpesa_receipt),
    INDEX idx_status (status)
) ENGINE=InnoDB;