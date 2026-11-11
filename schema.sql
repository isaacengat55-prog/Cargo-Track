CREATE DATABASE IF NOT EXISTS cargo_track
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE cargo_track;

CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    phone VARCHAR(32) NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'staff', 'customer') NOT NULL DEFAULT 'customer',
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_users_role_active (role, active)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS cargo (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    tracking_number VARCHAR(40) NOT NULL UNIQUE,
    customer_id BIGINT UNSIGNED NOT NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    description VARCHAR(250) NOT NULL,
    sender_name VARCHAR(120) NOT NULL,
    recipient_name VARCHAR(120) NOT NULL,
    origin VARCHAR(160) NOT NULL,
    destination VARCHAR(160) NOT NULL,
    expected_delivery DATE NULL,
    status ENUM('registered', 'picked_up', 'in_transit', 'at_customs', 'out_for_delivery', 'delayed', 'delivered', 'cancelled')
        NOT NULL DEFAULT 'registered',
    delivered_to VARCHAR(120) NULL,
    delivered_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_cargo_customer FOREIGN KEY (customer_id) REFERENCES users(id),
    CONSTRAINT fk_cargo_creator FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_cargo_customer_status (customer_id, status),
    INDEX idx_cargo_updated (updated_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS cargo_events (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    cargo_id BIGINT UNSIGNED NOT NULL,
    updated_by BIGINT UNSIGNED NOT NULL,
    status ENUM('registered', 'picked_up', 'in_transit', 'at_customs', 'out_for_delivery', 'delayed', 'delivered', 'cancelled') NOT NULL,
    location VARCHAR(160) NULL,
    notes VARCHAR(500) NULL,
    latitude DECIMAL(10, 7) NULL,
    longitude DECIMAL(10, 7) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_event_cargo FOREIGN KEY (cargo_id) REFERENCES cargo(id) ON DELETE CASCADE,
    CONSTRAINT fk_event_user FOREIGN KEY (updated_by) REFERENCES users(id),
    INDEX idx_events_cargo_time (cargo_id, created_at)
) ENGINE=InnoDB;
