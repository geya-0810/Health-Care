CREATE DATABASE IF NOT EXISTS health_care
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE health_care;

CREATE TABLE users (
    user_id         INT AUTO_INCREMENT PRIMARY KEY,
    full_name       VARCHAR(100)        NOT NULL,
    email           VARCHAR(150)        NOT NULL UNIQUE,
    password_hash   VARCHAR(255)        NOT NULL,  
    phone           VARCHAR(20),
    role            ENUM('patient', 'doctor', 'admin') NOT NULL DEFAULT 'patient',
    created_at      TIMESTAMP           DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP           DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE doctors (
    doctor_id       INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT                 NULL UNIQUE,
    full_name       VARCHAR(100)        NOT NULL,
    specialty       VARCHAR(100)        NOT NULL,  
    email           VARCHAR(150)        UNIQUE,
    phone           VARCHAR(20),
    bio             TEXT,
    profile_image_url VARCHAR(255),                 
    consultation_fee DECIMAL(10,2)      DEFAULT 0.00,
    is_active       BOOLEAN             DEFAULT TRUE,  
    created_at      TIMESTAMP           DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP           DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE schedules (
    schedule_id     INT AUTO_INCREMENT PRIMARY KEY,
    doctor_id       INT                 NOT NULL,
    slot_date       DATE                NOT NULL,
    start_time      TIME                NOT NULL,
    end_time        TIME                NOT NULL,
    status          ENUM('available', 'booked', 'blocked') NOT NULL DEFAULT 'available',
    created_at      TIMESTAMP           DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (doctor_id) REFERENCES doctors(doctor_id) ON DELETE CASCADE,

    UNIQUE KEY uq_doctor_slot (doctor_id, slot_date, start_time)
) ENGINE=InnoDB;
CREATE INDEX idx_schedules_doctor_date ON schedules(doctor_id, slot_date, status);

CREATE TABLE appointments (
    appointment_id  INT AUTO_INCREMENT PRIMARY KEY,
    patient_id      INT                 NOT NULL,
    doctor_id       INT                 NOT NULL,
    schedule_id     INT                 NOT NULL UNIQUE, 
    status          ENUM('confirmed', 'cancelled', 'completed', 'no_show') NOT NULL DEFAULT 'confirmed',
    visit_type      ENUM('new_case', 'follow_up', 'specialist_referral', 'other') NOT NULL DEFAULT 'new_case',
    reason          VARCHAR(255),               
    booked_at       TIMESTAMP           DEFAULT CURRENT_TIMESTAMP,
    cancelled_at    TIMESTAMP           NULL,

    FOREIGN KEY (patient_id)  REFERENCES users(user_id)      ON DELETE CASCADE,
    FOREIGN KEY (doctor_id)   REFERENCES doctors(doctor_id)  ON DELETE CASCADE,
    FOREIGN KEY (schedule_id) REFERENCES schedules(schedule_id) ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE INDEX idx_appointments_patient ON appointments(patient_id, status);
CREATE INDEX idx_appointments_doctor  ON appointments(doctor_id, status);

CREATE TABLE notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id          INT                 NOT NULL,
    appointment_id   INT,                         
    message          VARCHAR(255)        NOT NULL,
    is_read          BOOLEAN             DEFAULT FALSE,
    created_at       TIMESTAMP           DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id)        REFERENCES users(user_id)               ON DELETE CASCADE,
    FOREIGN KEY (appointment_id) REFERENCES appointments(appointment_id) ON DELETE SET NULL
) ENGINE=InnoDB;
CREATE INDEX idx_notifications_user ON notifications(user_id, is_read);

CREATE TABLE attachments (
    attachment_id   INT AUTO_INCREMENT PRIMARY KEY,
    appointment_id  INT                 NOT NULL,
    file_name       VARCHAR(255)        NOT NULL,
    file_url        VARCHAR(500)        NOT NULL,   
    file_type       VARCHAR(50),                    
    uploaded_at     TIMESTAMP           DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (appointment_id) REFERENCES appointments(appointment_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- INSERT INTO users (full_name, email, password_hash, phone, role) VALUES
-- ('Admin User', 'admin@hospital.com', '$2y$10$examplehashvalueonly', '0123456789', 'admin'),
-- ('Tan Wei Ling', 'weiling.tan@example.com', '$2y$10$examplehashvalueonly', '0129876543', 'patient');

-- INSERT INTO doctors (full_name, specialty, email, phone, consultation_fee) VALUES
-- ('Dr. Lim Chee Keong', 'General Practice', 'lim.ck@hospital.com', '0311234567', 30.00),
-- ('Dr. Nurul Huda', 'Pediatrics', 'nurul.h@hospital.com', '0311234568', 45.00);

-- INSERT INTO schedules (doctor_id, slot_date, start_time, end_time, status) VALUES
-- (1, '2026-08-20', '09:00:00', '09:30:00', 'available'),
-- (1, '2026-08-20', '09:30:00', '10:00:00', 'available'),
-- (2, '2026-08-20', '10:00:00', '10:30:00', 'available');
