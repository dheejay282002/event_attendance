-- School Management System Sample Data
-- Run this AFTER running database_structure.sql

-- Insert Roles
INSERT INTO `roles` (`name`, `guard_name`, `created_at`, `updated_at`) VALUES
('Admin', 'web', NOW(), NOW()),
('Teacher', 'web', NOW(), NOW()),
('Parent', 'web', NOW(), NOW()),
('Student', 'web', NOW(), NOW());

-- Insert Users (password is hashed version of 'codeastro.com')
INSERT INTO `users` (`name`, `email`, `password`, `profile_picture`, `created_at`, `updated_at`) VALUES
('Admin', 'admin@mail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'avatar.png', NOW(), NOW()),
('Teacher', 'teacher@mail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'avatar.png', NOW(), NOW()),
('Parent', 'parent@mail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'avatar.png', NOW(), NOW()),
('Student', 'student@mail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'avatar.png', NOW(), NOW());

-- Assign roles to users
INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES
(1, 'App\\User', 1),  -- Admin role to Admin user
(2, 'App\\User', 2),  -- Teacher role to Teacher user
(3, 'App\\User', 3),  -- Parent role to Parent user
(4, 'App\\User', 4);  -- Student role to Student user

-- Insert Teacher data
INSERT INTO `teachers` (`user_id`, `gender`, `phone`, `dateofbirth`, `current_address`, `permanent_address`, `created_at`, `updated_at`) VALUES
(2, 'male', '6969540014', '1990-04-11', '63 Walnut Hill Drive', '385 Emma Street', NOW(), NOW());

-- Insert Parent data
INSERT INTO `parents` (`user_id`, `gender`, `phone`, `current_address`, `permanent_address`, `created_at`, `updated_at`) VALUES
(3, 'male', '0147854545', '46 Custer Street', '46 Custer Street', NOW(), NOW());

-- Insert Grade data
INSERT INTO `grades` (`teacher_id`, `class_numeric`, `class_name`, `class_description`, `created_at`, `updated_at`) VALUES
(1, 1, 'One', 'class one', NOW(), NOW());

-- Insert Student data
INSERT INTO `students` (`user_id`, `parent_id`, `class_id`, `roll_number`, `gender`, `phone`, `dateofbirth`, `current_address`, `permanent_address`, `created_at`, `updated_at`) VALUES
(4, 1, 1, 1, 'male', '7801256654', '2007-04-11', '103 Pine Tree Lane', '103 Pine Tree Lane', NOW(), NOW());

-- Insert some sample subjects
INSERT INTO `subjects` (`name`, `slug`, `subject_code`, `teacher_id`, `description`, `created_at`, `updated_at`) VALUES
('Mathematics', 'mathematics', 101, 1, 'Basic mathematics for grade one', NOW(), NOW()),
('English', 'english', 102, 1, 'English language and literature', NOW(), NOW()),
('Science', 'science', 103, 1, 'Basic science concepts', NOW(), NOW());

-- Link subjects to grades
INSERT INTO `grade_subject` (`grade_id`, `subject_id`, `created_at`, `updated_at`) VALUES
(1, 1, NOW(), NOW()),
(1, 2, NOW(), NOW()),
(1, 3, NOW(), NOW());

-- Insert sample attendance records
INSERT INTO `attendances` (`class_id`, `teacher_id`, `student_id`, `attendence_date`, `attendence_status`, `created_at`, `updated_at`) VALUES
(1, 1, 1, '2024-01-15', 1, NOW(), NOW()),
(1, 1, 1, '2024-01-16', 1, NOW(), NOW()),
(1, 1, 1, '2024-01-17', 0, NOW(), NOW()),
(1, 1, 1, '2024-01-18', 1, NOW(), NOW()),
(1, 1, 1, '2024-01-19', 1, NOW(), NOW());
