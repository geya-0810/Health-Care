-- ALTER TABLE users ADD COLUMN avatar_url VARCHAR(500) NULL after phone;
-- ALTER TABLE doctors MODIFY user_id INT NOT NULL;
-- ALTER TABLE doctors DROP COLUMN full_name;
-- ALTER TABLE doctors DROP COLUMN email;
-- ALTER TABLE doctors DROP COLUMN phone;
-- ALTER TABLE doctors ADD CONSTRAINT fk_doctors_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE;
USE health_care;

INSERT INTO users (user_id, full_name, email, password_hash, phone, role) VALUES
(1, 'Admin', 'admin@healthcare.com.my', '$2y$10$j4ISo.o/nw7WBdqVx4MrsOWYQX22RTOE/h/uzbD9tf28ruxdI9S0C', '03 4567 8912', 'admin'),
(2, 'Victor Gan', 'ganvictor10@patient.com', '$2y$10$rfBkH5XcZwBSi7hbDX49/.xeugtAYL/Ol4D/CXQ2ly7nILPxiM2DS', '0173779880', 'patient'),
(3, 'Victor Gan', 'ganvictor10@doctor.com', '$2y$10$D1NqH1Zhehmifsl0Jfp2deDcKpB2eEZ/T0ZBlBNb6CIJfG4V17Sv.', '0173779880', 'doctor'),
(4, 'Victor Gan', 'ganvictor10@gmail.com', '$2y$10$pBrmvHQ2LRwfgmik95W3g.kIl3gEaQUy27ttjdzqfI0tO3F3t7W5W', '0173779880', 'assist'),
(5, 'Admin User', 'admin@hospital.example.com', '$2y$10$examplehashvalueonly', '0123456789', 'admin'),
(6, 'Tan Wei Ling', 'weiling.tan@example.com', '$2y$10$examplehashvalueonly', '0129876543', 'patient'),
(7, 'Dr. Lim Chee Keong', 'lim.ck@hospital.com', '$2y$10$examplehashvalueonly', '0311234567', 'doctor'),
(8, 'Dr. Nurul Huda', 'nurul.h@hospital.com', '$2y$10$examplehashvalueonly', '0311234568', 'doctor');

INSERT INTO doctors (doctor_id, user_id, specialty, bio, profile_image_url, consultation_fee, is_active) VALUES
(1, 3, 'eyes', 'i am a pro', NULL, 30.00, 1),
(2, 7, 'General Practice', NULL, NULL, 30.00, 1),
(3, 8, 'Pediatrics', NULL, NULL, 45.00, 1);

INSERT INTO schedules (schedule_id, doctor_id, slot_date, start_time, end_time, status) VALUES
(1, 2, '2026-08-20', '09:00:00', '09:30:00', 'available'),
(2, 2, '2026-08-20', '09:30:00', '10:00:00', 'available'),
(3, 3, '2026-08-20', '10:00:00', '10:30:00', 'available');

INSERT INTO appointments (appointment_id, patient_id, doctor_id, schedule_id, status, visit_type, reason, booked_at, cancelled_at) VALUES
(1, 2, 2, 1, 'cancelled', 'follow_up', 'my m get hurt by bd', '2026-08-18 10:19:35', '2026-08-20 10:01:24');

INSERT INTO notifications (notification_id, user_id, appointment_id, message, is_read, created_at) VALUES
(1, 2, 1, 'Your appointment on 2026-08-20 at 09:00:00 has been confirmed.', 0, '2026-08-18 10:19:35');

