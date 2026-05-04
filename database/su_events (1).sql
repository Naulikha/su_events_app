-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 04, 2026 at 06:20 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `su_events`
--

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

DROP TABLE IF EXISTS `bookings`;
CREATE TABLE `bookings` (
  `booking_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `attendee_id` int(11) NOT NULL,
  `booking_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

DROP TABLE IF EXISTS `events`;
CREATE TABLE `events` (
  `event_id` int(11) NOT NULL,
  `organiser_id` int(11) NOT NULL,
  `society_name` varchar(100) NOT NULL,
  `title` varchar(150) NOT NULL,
  `description` text NOT NULL,
  `event_date` datetime NOT NULL,
  `location` varchar(255) NOT NULL,
  `max_capacity` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `category` varchar(50) NOT NULL DEFAULT 'Social',
  `image_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`event_id`, `organiser_id`, `society_name`, `title`, `description`, `event_date`, `location`, `max_capacity`, `created_at`, `category`, `image_path`) VALUES
(11, 11, 'Computing Society', 'Annual Hackathon 2026', 'Join us for 24 hours of coding, pizza, and prizes!', '2026-05-09 06:47:29', 'Library Tech Hub', 100, '2026-05-04 04:47:29', 'Academic', 'uploads/banner4.jpg'),
(12, 11, 'Computing Society', 'Cybersecurity Workshop', 'Learn the basics of ethical hacking.', '2026-05-16 06:47:29', 'Room 402', 30, '2026-05-04 04:47:29', 'Academic', 'uploads/banner4.jpg'),
(13, 12, 'Drama Club', 'Improv Comedy Night', 'Laughs guaranteed.', '2026-05-07 06:47:29', 'Main Auditorium', 150, '2026-05-04 04:47:29', 'Entertainment', 'uploads/banner2.jpg'),
(14, 12, 'Drama Club', 'Auditions: Hamlet', 'Open casting call.', '2026-05-14 06:47:29', 'Studio B', 20, '2026-05-04 04:47:29', 'Social', 'uploads/banner1.jpg'),
(15, 13, 'Sports Union', 'Inter-varsity Basketball', 'Support our team!', '2026-05-06 06:47:29', 'Campus Gymnasium', 200, '2026-05-04 04:47:29', 'Sports', 'uploads/banner5.jpg'),
(16, 13, 'Sports Union', 'Yoga for Beginners', 'De-stress before exams.', '2026-05-11 06:47:29', 'Dance Studio', 25, '2026-05-04 04:47:29', 'Sports', 'uploads/banner3.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `event_categories`
--

DROP TABLE IF EXISTS `event_categories`;
CREATE TABLE `event_categories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `event_categories`
--

INSERT INTO `event_categories` (`category_id`, `category_name`) VALUES
(1, 'Academic'),
(4, 'Career'),
(5, 'Entertainment'),
(2, 'Social'),
(3, 'Sports');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `student_id` varchar(20) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(100) NOT NULL,
  `role` enum('Admin','Organiser','Attendee') NOT NULL DEFAULT 'Attendee',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `full_name`, `student_id`, `email`, `password_hash`, `role`, `created_at`) VALUES
(1, 'austin', '123456', 'austine@gmail.com', '$2y$10$daq4wvimGB3UXVY2TR9D9u3f8aTANJIY5sH8GD5lQKUEbfwbR0fqe', 'Admin', '2026-05-02 07:59:50'),
(6, 'austina', '1234567', 'a@gmail.com', '$2y$10$eIGmXh2XqBsQJMhrHob4lOEthgqDf/I1cjgWCbEFLm53GhPQxO8bu', 'Organiser', '2026-05-04 00:47:35'),
(11, 'Computing Society', 'COMP001', 'comp@su.edu', '$2y$10$eD.JvQ16o4EKljsLJp7xEujMlL3702BclMWCzSDSGhkdvg9z.0lyO', 'Organiser', '2026-05-04 04:29:18'),
(12, 'Drama Club', 'DRAMA01', 'drama@su.edu', '$2y$10$eD.JvQ16o4EKljsLJp7xEujMlL3702BclMWCzSDSGhkdvg9z.0lyO', 'Organiser', '2026-05-04 04:29:18'),
(13, 'Sports Union', 'SPORT01', 'sports@su.edu', '$2y$10$eD.JvQ16o4EKljsLJp7xEujMlL3702BclMWCzSDSGhkdvg9z.0lyO', 'Organiser', '2026-05-04 04:29:18'),
(14, 'John Doe', 'STD10001', 'john@student.edu', '$2y$10$eD.JvQ16o4EKljsLJp7xEujMlL3702BclMWCzSDSGhkdvg9z.0lyO', 'Attendee', '2026-05-04 04:29:18'),
(15, 'Jane Smith', 'STD10002', 'jane@student.edu', '$2y$10$eD.JvQ16o4EKljsLJp7xEujMlL3702BclMWCzSDSGhkdvg9z.0lyO', 'Attendee', '2026-05-04 04:29:18');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`booking_id`),
  ADD UNIQUE KEY `unique_booking` (`event_id`,`attendee_id`),
  ADD KEY `attendee_id` (`attendee_id`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`event_id`),
  ADD UNIQUE KEY `unique_event` (`organiser_id`,`title`,`event_date`),
  ADD KEY `fk_event_category` (`category`);

--
-- Indexes for table `event_categories`
--
ALTER TABLE `event_categories`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `category_name` (`category_name`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `student_id` (`student_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `booking_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `event_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `event_categories`
--
ALTER TABLE `event_categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`attendee_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `events_ibfk_1` FOREIGN KEY (`organiser_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_event_category` FOREIGN KEY (`category`) REFERENCES `event_categories` (`category_name`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
