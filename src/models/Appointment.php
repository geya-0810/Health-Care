<?php
// src/models/Appointment.php

class Appointment
{
    public static function create(PDO $db, int $patientId, int $doctorId, int $scheduleId, string $reason = '', string $visitType = 'new_case', string $status = 'pending'): int
    {
        $stmt = $db->prepare(
            'INSERT INTO appointments (patient_id, doctor_id, schedule_id, reason, visit_type, status)
             VALUES (:patient_id, :doctor_id, :schedule_id, :reason, :visit_type, :status)'
        );
        $stmt->execute([
            'patient_id'  => $patientId,
            'doctor_id'   => $doctorId,
            'schedule_id' => $scheduleId,
            'reason'      => $reason,
            'visit_type'  => $visitType,
            'status'      => $status,
        ]);
        return (int) $db->lastInsertId();
    }

    public static function findById(PDO $db, int $id): ?array
    {
        $stmt = $db->prepare('SELECT * FROM appointments WHERE appointment_id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    // Complete patient appointment history, including doctor and slot details for profile.php.
    public static function byPatient(PDO $db, int $patientId): array
    {
        $stmt = $db->prepare(
            'SELECT a.appointment_id, a.status, a.reason, a.visit_type, a.booked_at,
                          d.doctor_id, du.full_name AS doctor_name, d.specialty,
                    s.slot_date, s.start_time
             FROM appointments a
             JOIN doctors d   ON d.doctor_id = a.doctor_id
                      LEFT JOIN users du ON du.user_id = d.user_id
             JOIN schedules s ON s.schedule_id = a.schedule_id
             WHERE a.patient_id = :patient_id
             ORDER BY s.slot_date DESC, s.start_time DESC'
        );
        $stmt->execute(['patient_id' => $patientId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // The doctor's own appointments for viewing and confirmation in profile.php.
    public static function byDoctor(PDO $db, int $doctorId): array
    {
        $stmt = $db->prepare(
            'SELECT a.appointment_id, a.status, a.reason, a.visit_type, a.booked_at,
                    u.user_id AS patient_id, u.full_name AS patient_name, u.email AS patient_email, u.phone AS patient_phone,
                    s.slot_date, s.start_time
             FROM appointments a
             JOIN users u      ON u.user_id = a.patient_id
             JOIN schedules s  ON s.schedule_id = a.schedule_id
             WHERE a.doctor_id = :doctor_id
             ORDER BY s.slot_date ASC, s.start_time ASC'
        );
        $stmt->execute(['doctor_id' => $doctorId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Admin view: all appointments, optionally filtered by status.
    public static function all(PDO $db, ?string $status = null): array
    {
        $sql = 'SELECT a.appointment_id, a.status, a.booked_at,
                       u.full_name AS patient_name, u.email AS patient_email,
                      du.full_name AS doctor_name,
                       s.slot_date, s.start_time
                FROM appointments a
                JOIN users u     ON u.user_id = a.patient_id
                JOIN doctors d   ON d.doctor_id = a.doctor_id
                  LEFT JOIN users du ON du.user_id = d.user_id
                JOIN schedules s ON s.schedule_id = a.schedule_id';
        if ($status) {
            $sql .= ' WHERE a.status = :status';
        }
        $sql .= ' ORDER BY s.slot_date DESC, s.start_time DESC';

        $stmt = $db->prepare($sql);
        $stmt->execute($status ? ['status' => $status] : []);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function setStatus(PDO $db, int $id, string $status): bool
    {
        $sql = 'UPDATE appointments SET status = :status';
        if ($status === 'cancelled') {
            $sql .= ', cancelled_at = NOW()';
        }
        $sql .= ' WHERE appointment_id = :id';
        $stmt = $db->prepare($sql);
        return $stmt->execute(['status' => $status, 'id' => $id]);
    }
}
