-- ============================================================
-- Hospital Booking & Reservation System
-- MVP Database Schema (MySQL 5.7+ / MariaDB, tested for XAMPP)
-- ============================================================

CREATE DATABASE IF NOT EXISTS hospital_booking
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE hospital_booking;

-- ------------------------------------------------------------
-- 1. users
-- Patient 和 Admin 共用一张表，用 role 区分权限
-- ------------------------------------------------------------
CREATE TABLE users (
    user_id         INT AUTO_INCREMENT PRIMARY KEY,
    full_name       VARCHAR(100)        NOT NULL,
    email           VARCHAR(150)        NOT NULL UNIQUE,
    password_hash   VARCHAR(255)        NOT NULL,   -- password_hash() 生成，不要存明文
    phone           VARCHAR(20),
    role            ENUM('patient', 'admin') NOT NULL DEFAULT 'patient',
    created_at      TIMESTAMP           DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP           DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 2. doctors
-- 医生资料。profile_image_url 存 Object Storage(S3/Blob) 返回的URL，不存文件本身
-- ------------------------------------------------------------
CREATE TABLE doctors (
    doctor_id       INT AUTO_INCREMENT PRIMARY KEY,
    full_name       VARCHAR(100)        NOT NULL,
    specialty       VARCHAR(100)        NOT NULL,   -- e.g. 'Cardiology', 'General Practice'
    email           VARCHAR(150)        UNIQUE,
    phone           VARCHAR(20),
    bio             TEXT,
    profile_image_url VARCHAR(255),                 -- Object Storage URL
    consultation_fee DECIMAL(10,2)      DEFAULT 0.00,
    is_active       BOOLEAN             DEFAULT TRUE,  -- admin可停用某医生而不删除历史数据
    created_at      TIMESTAMP           DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP           DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 3. schedules
-- 医生开放的可预约时段。一条记录 = 一个slot
-- status 控制该slot是否已被预约，配合 UNIQUE 约束防止双重预约
-- ------------------------------------------------------------
CREATE TABLE schedules (
    schedule_id     INT AUTO_INCREMENT PRIMARY KEY,
    doctor_id       INT                 NOT NULL,
    slot_date       DATE                NOT NULL,
    start_time      TIME                NOT NULL,
    end_time        TIME                NOT NULL,
    status          ENUM('available', 'booked', 'blocked') NOT NULL DEFAULT 'available',
    created_at      TIMESTAMP           DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (doctor_id) REFERENCES doctors(doctor_id) ON DELETE CASCADE,

    -- 防止同一医生同一天同一时间被重复开出两个slot
    UNIQUE KEY uq_doctor_slot (doctor_id, slot_date, start_time)
) ENGINE=InnoDB;

CREATE INDEX idx_schedules_doctor_date ON schedules(doctor_id, slot_date, status);

-- ------------------------------------------------------------
-- 4. appointments
-- 预约记录。schedule_id 加 UNIQUE，从数据库层面杜绝一个slot被两个人同时约到
-- ------------------------------------------------------------
CREATE TABLE appointments (
    appointment_id  INT AUTO_INCREMENT PRIMARY KEY,
    patient_id      INT                 NOT NULL,
    doctor_id       INT                 NOT NULL,
    schedule_id     INT                 NOT NULL UNIQUE,  -- 关键：一个slot只能对应一条有效预约
    status          ENUM('confirmed', 'cancelled', 'completed', 'no_show') NOT NULL DEFAULT 'confirmed',
    reason          VARCHAR(255),                -- 就诊原因/简单备注
    booked_at       TIMESTAMP           DEFAULT CURRENT_TIMESTAMP,
    cancelled_at    TIMESTAMP           NULL,

    FOREIGN KEY (patient_id)  REFERENCES users(user_id)      ON DELETE CASCADE,
    FOREIGN KEY (doctor_id)   REFERENCES doctors(doctor_id)  ON DELETE CASCADE,
    FOREIGN KEY (schedule_id) REFERENCES schedules(schedule_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE INDEX idx_appointments_patient ON appointments(patient_id, status);
CREATE INDEX idx_appointments_doctor  ON appointments(doctor_id, status);

-- ------------------------------------------------------------
-- 5. notifications
-- 站内通知（MVP先做in-app，够用；不用一开始就接email/SMS）
-- ------------------------------------------------------------
CREATE TABLE notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id          INT                 NOT NULL,
    appointment_id   INT,                          -- 可为空，也可能是系统通知
    message          VARCHAR(255)        NOT NULL,
    is_read          BOOLEAN             DEFAULT FALSE,
    created_at       TIMESTAMP           DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id)        REFERENCES users(user_id)               ON DELETE CASCADE,
    FOREIGN KEY (appointment_id) REFERENCES appointments(appointment_id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE INDEX idx_notifications_user ON notifications(user_id, is_read);

-- ------------------------------------------------------------
-- 6. attachments (可选，走 Object Storage 集成)
-- 病历文件/检查报告等上传到 S3/Blob，这里只存metadata和URL
-- ------------------------------------------------------------
CREATE TABLE attachments (
    attachment_id   INT AUTO_INCREMENT PRIMARY KEY,
    appointment_id  INT                 NOT NULL,
    file_name       VARCHAR(255)        NOT NULL,
    file_url        VARCHAR(500)        NOT NULL,   -- Object Storage 返回的URL
    file_type       VARCHAR(50),                    -- e.g. 'pdf', 'jpg'
    uploaded_at     TIMESTAMP           DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (appointment_id) REFERENCES appointments(appointment_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 示例数据（方便本地开发测试用，正式部署前删掉或改成seed脚本）
-- ============================================================

INSERT INTO users (full_name, email, password_hash, phone, role) VALUES
('Admin User', 'admin@hospital.com', '$2y$10$examplehashvalueonly', '0123456789', 'admin'),
('Tan Wei Ling', 'weiling.tan@example.com', '$2y$10$examplehashvalueonly', '0129876543', 'patient');

INSERT INTO doctors (full_name, specialty, email, phone, consultation_fee) VALUES
('Dr. Lim Chee Keong', 'General Practice', 'lim.ck@hospital.com', '0311234567', 30.00),
('Dr. Nurul Huda', 'Pediatrics', 'nurul.h@hospital.com', '0311234568', 45.00);

INSERT INTO schedules (doctor_id, slot_date, start_time, end_time, status) VALUES
(1, '2026-08-20', '09:00:00', '09:30:00', 'available'),
(1, '2026-08-20', '09:30:00', '10:00:00', 'available'),
(2, '2026-08-20', '10:00:00', '10:30:00', 'available');
