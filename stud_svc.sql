-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 18, 2026 at 05:47 PM
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
-- Database: `stud_svc`
--

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `course_id` int(11) NOT NULL,
  `course_code` varchar(20) NOT NULL,
  `course_name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`course_id`, `course_code`, `course_name`, `description`) VALUES
(1, 'BSIT', 'Bachelor of Science in Information Technology', 'Information technology and information systems.'),
(2, 'BSCS', 'Bachelor of Science in Computer Science', 'Computing theory and software development.'),
(3, 'BSBA', 'Bachelor of Science in Business Administration', 'Business and management.'),
(4, 'BSEd', 'Bachelor of Secondary Education', 'Teacher preparation and secondary education.'),
(5, 'BSHM', 'Bachelor of Science in Hospitality Management', 'Hospitality, tourism, and service management.');

-- --------------------------------------------------------

--
-- Table structure for table `course_subjects`
--

CREATE TABLE `course_subjects` (
  `course_subject_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `year_level` tinyint(3) UNSIGNED NOT NULL,
  `semester` tinyint(3) UNSIGNED NOT NULL,
  `instructor_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `course_subjects`
--

INSERT INTO `course_subjects` (`course_subject_id`, `course_id`, `subject_id`, `year_level`, `semester`, `instructor_id`) VALUES
(1, 1, 1, 3, 1, 3),
(2, 1, 2, 3, 1, 3),
(3, 1, 3, 3, 1, 3),
(4, 2, 4, 3, 1, 4),
(5, 2, 6, 3, 1, 4),
(6, 2, 7, 3, 1, 4);

-- --------------------------------------------------------

--
-- Table structure for table `enrollments`
--

CREATE TABLE `enrollments` (
  `enrollment_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `academic_year` varchar(20) NOT NULL,
  `semester` tinyint(3) UNSIGNED NOT NULL,
  `enrollment_date` date NOT NULL,
  `status` enum('Pending','Enrolled','Completed','Cancelled') DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `enrollments`
--

INSERT INTO `enrollments` (`enrollment_id`, `student_id`, `academic_year`, `semester`, `enrollment_date`, `status`) VALUES
(1, 1, '2026-2027', 1, '2026-10-18', 'Pending'),
(2, 2, '2026-2027', 1, '2026-10-18', 'Pending'),
(3, 3, '2026-2027', 1, '2026-10-18', 'Pending'),
(4, 4, '2026-2027', 1, '2026-10-18', 'Pending'),
(5, 5, '2026-2027', 1, '2026-10-18', 'Pending');

-- --------------------------------------------------------

--
-- Table structure for table `enrollment_subjects`
--

CREATE TABLE `enrollment_subjects` (
  `enrollment_subject_id` int(11) NOT NULL,
  `enrollment_id` int(11) NOT NULL,
  `course_subject_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `enrollment_subjects`
--

INSERT INTO `enrollment_subjects` (`enrollment_subject_id`, `enrollment_id`, `course_subject_id`) VALUES
(1, 1, 1),
(2, 1, 2),
(3, 1, 3),
(4, 3, 4),
(5, 3, 5),
(6, 3, 6);

-- --------------------------------------------------------

--
-- Table structure for table `grades`
--

CREATE TABLE `grades` (
  `grade_id` int(11) NOT NULL,
  `enrollment_subject_id` int(11) NOT NULL,
  `grade` decimal(5,2) DEFAULT NULL,
  `remarks` varchar(50) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `grades`
--

INSERT INTO `grades` (`grade_id`, `enrollment_subject_id`, `grade`, `remarks`, `updated_at`) VALUES
(1, 1, 95.00, NULL, '2026-10-18 15:25:15'),
(2, 2, 89.50, NULL, '2026-10-18 15:25:15'),
(3, 3, 92.00, NULL, '2026-10-18 15:25:15'),
(4, 4, 88.00, NULL, '2026-10-18 15:25:15'),
(5, 5, 91.50, NULL, '2026-10-18 15:25:15'),
(6, 6, 94.00, NULL, '2026-10-18 15:25:15');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `student_id` int(11) NOT NULL,
  `student_number` varchar(30) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `course_id` int(11) DEFAULT NULL,
  `year_level` tinyint(3) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`student_id`, `student_number`, `first_name`, `middle_name`, `last_name`, `email`, `phone`, `address`, `course_id`, `year_level`, `created_at`) VALUES
(1, '240185', 'Juan', 'Santos', 'Dela Cruz', 'juan.delacruz@example.com', '09171234567', 'Taysan, Batangas', 1, 3, '2026-10-18 14:42:20'),
(2, '240186', 'Maria', 'Reyes', 'Santos', 'maria.santos@example.com', '09181234567', 'Lipa City, Batangas', 1, 3, '2026-10-18 14:42:20'),
(3, '240187', 'Pedro', 'Garcia', 'Reyes', 'pedro.reyes@example.com', '09191234567', 'Batangas City', 2, 3, '2026-10-18 14:42:20'),
(4, '240188', 'Ana', 'Mendoza', 'Cruz', 'ana.cruz@example.com', '09201234567', 'Tanauan, Batangas', 3, 3, '2026-10-18 14:42:20'),
(5, '240189', 'Mark', 'Lopez', 'Ramos', 'mark.ramos@example.com', '09211234567', 'Lemery, Batangas', 4, 3, '2026-10-18 14:42:20');

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `subject_id` int(11) NOT NULL,
  `subject_code` varchar(30) NOT NULL,
  `subject_name` varchar(150) NOT NULL,
  `units` tinyint(3) UNSIGNED NOT NULL DEFAULT 3
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`subject_id`, `subject_code`, `subject_name`, `units`) VALUES
(1, 'IT101', 'Introduction to Computing', 3),
(2, 'IT102', 'Computer Programming 1', 3),
(3, 'IT201', 'Database Management Systems', 3),
(4, 'CS301', 'Data Structures and Algorithms', 3),
(5, 'GE101', 'Understanding the Self', 3),
(6, 'IT202', 'Programming 3', 3),
(7, 'IT301', 'Web Development', 3);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('Admin','Registrar','Instructor','Student') NOT NULL,
  `student_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password_hash`, `role`, `student_id`) VALUES
(1, 'admin', '$2y$10$gVSQx0Rp/W8hvMOgGXxti.vVKo1hqr/rmY/aH2JGq9pM/Zi005jEG', 'Admin', NULL),
(2, 'registrar', '$2y$10$EJ/chf9IJrR202saO2ffOee2SN2q5kLeIPOTIuANJFdJ38t3pUsPq', 'Registrar', NULL),
(3, 'instructor1', '$2y$10$2AVvAJO37tsTafbR74jruepJ4Ecv2iSk.rIpJVHrosGaB0QOjNqb2', 'Instructor', NULL),
(4, 'instructor2', '$2y$10$nLsV9JqcnbfpLij3RfNHreCLj5bZBUR2egg724N3tZg42GIsHkJbK', 'Instructor', NULL),
(5, 'student240185', '$2y$10$aQYYFZfHAPHsRXB6XpVb7uLQNvPQQfvCvKgcGQuHg5G6y8jOqm4sa', 'Student', 1),
(6, 'student240186', '$2y$10$KSdrelmHmyOh24XjS10YPOuebB84wrKuQM4RskB1hnZ42UM7JcQoO', 'Student', 2),
(7, 'student240187', '$2y$10$6RrjQItRAXd4cdq3NmWe2ujXsxw3QGsKgZTgIS3nlLxuevDyJvHWm', 'Student', 3),
(8, 'student240188', '$2y$10$O8OjiTfOc1ZxYt6nqdXYFO4frcXPCRbTVJkSyULONQBDk2/6WLRBi', 'Student', 4),
(9, 'student240189', '$2y$10$kn0YTkDYVoUGjvP6iaQJfO9U9ZXnFARcKdmMbA2yvvpsIf40dLYTu', 'Student', 5);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`course_id`),
  ADD UNIQUE KEY `course_code` (`course_code`);

--
-- Indexes for table `course_subjects`
--
ALTER TABLE `course_subjects`
  ADD PRIMARY KEY (`course_subject_id`),
  ADD UNIQUE KEY `course_id` (`course_id`,`subject_id`,`year_level`,`semester`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `instructor_id` (`instructor_id`);

--
-- Indexes for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD PRIMARY KEY (`enrollment_id`),
  ADD UNIQUE KEY `student_id` (`student_id`,`academic_year`,`semester`);

--
-- Indexes for table `enrollment_subjects`
--
ALTER TABLE `enrollment_subjects`
  ADD PRIMARY KEY (`enrollment_subject_id`),
  ADD UNIQUE KEY `enrollment_id` (`enrollment_id`,`course_subject_id`),
  ADD KEY `course_subject_id` (`course_subject_id`);

--
-- Indexes for table `grades`
--
ALTER TABLE `grades`
  ADD PRIMARY KEY (`grade_id`),
  ADD UNIQUE KEY `enrollment_subject_id` (`enrollment_subject_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`student_id`),
  ADD UNIQUE KEY `student_number` (`student_number`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`subject_id`),
  ADD UNIQUE KEY `subject_code` (`subject_code`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `student_id` (`student_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `course_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `course_subjects`
--
ALTER TABLE `course_subjects`
  MODIFY `course_subject_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `enrollments`
--
ALTER TABLE `enrollments`
  MODIFY `enrollment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `enrollment_subjects`
--
ALTER TABLE `enrollment_subjects`
  MODIFY `enrollment_subject_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `grades`
--
ALTER TABLE `grades`
  MODIFY `grade_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `subject_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `course_subjects`
--
ALTER TABLE `course_subjects`
  ADD CONSTRAINT `course_subjects_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`),
  ADD CONSTRAINT `course_subjects_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`subject_id`),
  ADD CONSTRAINT `course_subjects_ibfk_3` FOREIGN KEY (`instructor_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD CONSTRAINT `enrollments_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`);

--
-- Constraints for table `enrollment_subjects`
--
ALTER TABLE `enrollment_subjects`
  ADD CONSTRAINT `enrollment_subjects_ibfk_1` FOREIGN KEY (`enrollment_id`) REFERENCES `enrollments` (`enrollment_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `enrollment_subjects_ibfk_2` FOREIGN KEY (`course_subject_id`) REFERENCES `course_subjects` (`course_subject_id`);

--
-- Constraints for table `grades`
--
ALTER TABLE `grades`
  ADD CONSTRAINT `grades_ibfk_1` FOREIGN KEY (`enrollment_subject_id`) REFERENCES `enrollment_subjects` (`enrollment_subject_id`) ON DELETE CASCADE;

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE SET NULL;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
