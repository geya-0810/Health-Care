<?php
// src/models/Schedule.php

class Schedule
{
    // Available slots for a doctor on a date (only status='available').
    public static function availableSlots(PDO $db, int $doctorId, string $date): array
    {
        $stmt = $db->prepare(
            "SELECT * FROM schedules
             WHERE doctor_id = :doctor_id AND slot_date = :date AND status = 'available'
             ORDER BY start_time ASC"
        );
        $stmt->execute(['doctor_id' => $doctorId, 'date' => $date]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Admin view: all slots for a doctor on a date, including booked slots, for the schedule.
    public static function forDoctorOnDate(PDO $db, int $doctorId, string $date): array
    {
        $stmt = $db->prepare(
            'SELECT * FROM schedules WHERE doctor_id = :doctor_id AND slot_date = :date ORDER BY start_time ASC'
        );
        $stmt->execute(['doctor_id' => $doctorId, 'date' => $date]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function findById(PDO $db, int $id): ?array
    {
        $stmt = $db->prepare('SELECT * FROM schedules WHERE schedule_id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    // Admin opens a new slot.
    public static function create(PDO $db, int $doctorId, string $date, string $startTime, string $endTime): int
    {
        $stmt = $db->prepare(
            'INSERT INTO schedules (doctor_id, slot_date, start_time, end_time, status)
             VALUES (:doctor_id, :date, :start, :end, "available")'
        );
        $stmt->execute([
            'doctor_id' => $doctorId,
            'date'      => $date,
            'start'     => $startTime,
            'end'       => $endTime,
        ]);
        return (int) $db->lastInsertId();
    }

    public static function setStatus(PDO $db, int $scheduleId, string $status): bool
    {
        $stmt = $db->prepare('UPDATE schedules SET status = :status WHERE schedule_id = :id');
        return $stmt->execute(['status' => $status, 'id' => $scheduleId]);
    }

    public static function delete(PDO $db, int $scheduleId): bool
    {
        // Only slots without bookings may be deleted.
        $stmt = $db->prepare("DELETE FROM schedules WHERE schedule_id = :id AND status = 'available'");
        return $stmt->execute(['id' => $scheduleId]);
    }
}
