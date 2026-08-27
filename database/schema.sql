CREATE DATABASE IF NOT EXISTS health_care
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE health_care;

CREATE TABLE IF NOT EXISTS users (
    user_id         INT AUTO_INCREMENT PRIMARY KEY,
    full_name       VARCHAR(100)        NOT NULL,
    email           VARCHAR(150)        NOT NULL UNIQUE,
    password_hash   VARCHAR(255)        NOT NULL,  
    phone           VARCHAR(20),
    avatar_url      VARCHAR(500),
    role            ENUM('patient', 'doctor', 'assist', 'admin') NOT NULL DEFAULT 'patient',
    is_active       BOOLEAN             DEFAULT TRUE,    
    created_at      TIMESTAMP           DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP           DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS doctors (  
    doctor_id       INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT                 NULL UNIQUE,
    specialty       VARCHAR(100)        NOT NULL,  
    bio             TEXT,
    -- profile_image_url VARCHAR(255),                 
    consultation_fee DECIMAL(10,2)      DEFAULT 0.00,
    
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS schedules (
    schedule_id     INT AUTO_INCREMENT PRIMARY KEY,
    doctor_id       INT                 NOT NULL,
    slot_date       DATE                NOT NULL,
    start_time      TIME                NOT NULL,
    end_time        TIME                NOT NULL,
    status          ENUM('available', 'booked', 'blocked') NOT NULL DEFAULT 'available',
    created_at      TIMESTAMP           DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (doctor_id) REFERENCES doctors(doctor_id) ON DELETE CASCADE,

    UNIQUE KEY uq_doctor_slot (doctor_id, slot_date, start_time),
    INDEX idx_schedules_doctor_date (doctor_id, slot_date, status)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS appointments (
    appointment_id  INT AUTO_INCREMENT PRIMARY KEY,
    patient_id      INT                 NOT NULL,
    doctor_id       INT                 NOT NULL,
    schedule_id     INT                 NOT NULL UNIQUE, 
    status          ENUM('confirmed', 'cancelled', 'completed', 'no_show', 'pending') NOT NULL DEFAULT 'confirmed',
    visit_type      ENUM('new_case', 'follow_up', 'specialist_referral', 'other') NOT NULL DEFAULT 'new_case',
    reason          VARCHAR(255),               
    booked_at       TIMESTAMP           DEFAULT CURRENT_TIMESTAMP,
    cancelled_at    TIMESTAMP           NULL,

    FOREIGN KEY (patient_id)  REFERENCES users(user_id)      ON DELETE CASCADE,
    FOREIGN KEY (doctor_id)   REFERENCES doctors(doctor_id)  ON DELETE CASCADE,
    FOREIGN KEY (schedule_id) REFERENCES schedules(schedule_id) ON DELETE CASCADE,
    INDEX idx_appointments_patient (patient_id, status),
    INDEX idx_appointments_doctor  (doctor_id, status)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id          INT                 NOT NULL,
    appointment_id   INT,                         
    message          VARCHAR(255)        NOT NULL,
    is_read          BOOLEAN             DEFAULT FALSE,
    created_at       TIMESTAMP           DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id)        REFERENCES users(user_id)               ON DELETE CASCADE,
    FOREIGN KEY (appointment_id) REFERENCES appointments(appointment_id) ON DELETE SET NULL,
    INDEX idx_notifications_user (user_id, is_read)
) ENGINE=InnoDB;

-- CREATE TABLE IF NOT EXISTS attachments (
--     attachment_id   INT AUTO_INCREMENT PRIMARY KEY,
--     appointment_id  INT                 NOT NULL,
--     file_name       VARCHAR(255)        NOT NULL,
--     file_url        VARCHAR(500)        NOT NULL,   
--     file_type       VARCHAR(50),                    
--     uploaded_at     TIMESTAMP           DEFAULT CURRENT_TIMESTAMP,

--     FOREIGN KEY (appointment_id) REFERENCES appointments(appointment_id) ON DELETE CASCADE
-- ) ENGINE=InnoDB;
