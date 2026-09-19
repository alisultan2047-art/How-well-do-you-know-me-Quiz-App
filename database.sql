-- ==========================================================
-- "How Well Do You Know Ali?" - Personal Quiz Database Schema
-- Production MySQL / MariaDB Schema for InfinityFree Hosting
-- Target Engine: InnoDB, Charset: utf8mb4
-- ==========================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------------------------------------
-- Table: questions
-- Stores quiz questions and 4 multiple-choice options
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `questions`;
CREATE TABLE `questions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `question_text` TEXT NOT NULL,
  `option_a` VARCHAR(255) NOT NULL,
  `option_b` VARCHAR(255) NOT NULL,
  `option_c` VARCHAR(255) NOT NULL,
  `option_d` VARCHAR(255) NOT NULL,
  `correct_answer` ENUM('A', 'B', 'C', 'D') NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `order_num` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_active_order` (`is_active`, `order_num`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- Table: quiz_responses
-- Stores each friend's overall quiz submission and final score
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `quiz_responses`;
CREATE TABLE `quiz_responses` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `friend_name` VARCHAR(100) NOT NULL,
  `score` INT NOT NULL,
  `total_questions` INT NOT NULL,
  `percentage` DECIMAL(5,2) NOT NULL,
  `submitted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_submitted_at` (`submitted_at` DESC),
  INDEX `idx_score` (`score` DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- Table: quiz_answers
-- Stores each individual answer chosen by the friend for audit & review
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `quiz_answers`;
CREATE TABLE `quiz_answers` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `response_id` INT NOT NULL,
  `question_id` INT NOT NULL,
  `selected_answer` VARCHAR(10) NOT NULL,
  `is_correct` TINYINT(1) NOT NULL,
  CONSTRAINT `fk_answer_response` FOREIGN KEY (`response_id`) REFERENCES `quiz_responses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_answer_question` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE,
  INDEX `idx_response_question` (`response_id`, `question_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- Table: admin_users
-- Secure credentials for Ali Sultan's admin dashboard
-- Note: Initial administrator account is created securely via
-- /admin/setup.php on first access, then permanently locked.
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `admin_users`;
CREATE TABLE `admin_users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ==========================================================
-- Sample Questions about Ali Sultan
-- (You can easily edit, replace, or add more in the Admin Dashboard)
-- ==========================================================

INSERT INTO `questions` (`id`, `question_text`, `option_a`, `option_b`, `option_c`, `option_d`, `correct_answer`, `is_active`, `order_num`) VALUES
(1, 'What is Ali\'s favorite color?', 'Electric Blue', 'Crimson Red', 'Emerald Green', 'Matte Black', 'A', 1, 1),
(2, 'Which technology field is Ali most passionate about?', 'Artificial Intelligence & Machine Learning', 'Game Development', 'Blockchain & Crypto', 'Hardware & Embedded IoT', 'A', 1, 2),
(3, 'What type of software projects does Ali love building?', 'Full-Stack Web Apps with Smart AI Features', 'Command Line Utility Scripts', 'Static Brochure Websites', 'Native Mobile 2D Games', 'A', 1, 3),
(4, 'What is one of Ali\'s favorite interests outside technology?', 'Photography & Exploring New Places', 'Competitive Swimming', 'Playing Classical Violin', 'Baking Gourmet Pastries', 'A', 1, 4),
(5, 'Which AI technology does Ali find most fascinating right now?', 'Autonomous Agents & Large Language Models', 'Rule-Based Expert Systems', 'Symbolic Logic Solvers', 'Genetic Algorithms', 'A', 1, 5),
(6, 'What kind of apps has Ali worked on most frequently?', 'Interactive Web Dashboards & Developer Tools', 'Banking Mainframe Systems', 'Hardware Driver Firmware', '3D CAD Software', 'A', 1, 6),
(7, 'What is Ali\'s favorite academic subject to dive into?', 'Computer Science & Software Architecture', '18th Century European History', 'Organic Chemistry', 'Microeconomics', 'A', 1, 7),
(8, 'Which dream destination would Ali love to visit next?', 'Japan (Tokyo & Kyoto)', 'Iceland', 'Switzerland', 'Australia', 'A', 1, 8),
(9, 'What is Ali\'s preferred learning style?', 'Hands-on building, hacking & rapid prototyping', 'Reading 900-page textbooks before coding', 'Listening to theory audiobooks only', 'Memorizing slides for exams', 'A', 1, 9),
(10, 'What is Ali currently focusing his time on studying?', 'Advanced Full-Stack Engineering & AI Systems', 'Ancient Architecture', 'Veterinary Medicine', 'Maritime Law', 'A', 1, 10);

