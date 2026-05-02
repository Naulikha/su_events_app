--COpy this into your phpMyAdmin so you have the same database

CREATE DATABASE IF NOT EXISTS su_events;
USE su_events;

-- Users Table: Handles the 3 Roles
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    student_id VARCHAR(20) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(100) NOT NULL, -- UPDATED: Exactly 100 chars to perfectly hold the encrypted hash
    role ENUM('Admin', 'Organiser', 'Attendee') NOT NULL DEFAULT 'Attendee',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Events Table: Societies posting activities
CREATE TABLE events (
    event_id INT AUTO_INCREMENT PRIMARY KEY,
    organiser_id INT NOT NULL,
    society_name VARCHAR(100) NOT NULL,
    title VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    event_date DATETIME NOT NULL,
    location VARCHAR(255) NOT NULL,
    max_capacity INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (organiser_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Bookings Table: Students reserving tickets
CREATE TABLE bookings (
    booking_id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    attendee_id INT NOT NULL,
    booking_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_booking (event_id, attendee_id), -- Stops students booking twice
    FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE CASCADE,
    FOREIGN KEY (attendee_id) REFERENCES users(user_id) ON DELETE CASCADE
);