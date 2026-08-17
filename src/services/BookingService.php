<?php
// src/services/BookingService.php
// 核心：booking conflict prevention 用 DB transaction + row lock 实现，
// 配合 schema.sql 里 schedules.status 和 appointments.schedule_id 的 UNIQUE 约束双重保险

class BookingService
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * @throws RuntimeException 如果slot已被别人抢先约走
     */
    public function bookAppointment(int $patientId, int $scheduleId, string $reason = '', string $visitType = 'new_case'): int
    {
        $this->db->beginTransaction();
        try {
            // FOR UPDATE 锁住这一行，防止两个并发请求同时读到 available 再各自插入
            $stmt = $this->db->prepare(
                "SELECT * FROM schedules WHERE schedule_id = :id AND status = 'available' FOR UPDATE"
            );
            $stmt->execute(['id' => $scheduleId]);
            $schedule = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$schedule) {
                throw new RuntimeException('This time slot is no longer available. Please choose another one.');
            }

            Schedule::setStatus($this->db, $scheduleId, 'booked');

            $appointmentId = Appointment::create(
                $this->db,
                $patientId,
                (int) $schedule['doctor_id'],
                $scheduleId,
                $reason,
                $visitType
            );

            $this->db->commit();

            // 预约成功后写入通知（在同一个流程里，失败也不影响预约本身）
            try {
                (new NotificationService($this->db))->notify(
                    $patientId,
                    "Your appointment on {$schedule['slot_date']} at {$schedule['start_time']} has been confirmed.",
                    $appointmentId
                );
            } catch (Throwable $e) {
                error_log('Notification failed after booking: ' . $e->getMessage());
            }

            return $appointmentId;

        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * @throws RuntimeException 如果预约不存在或不属于这个patient
     */
    public function cancelAppointment(int $appointmentId, int $patientId): bool
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                'SELECT * FROM appointments WHERE appointment_id = :id AND patient_id = :pid LIMIT 1'
            );
            $stmt->execute(['id' => $appointmentId, 'pid' => $patientId]);
            $appointment = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$appointment) {
                throw new RuntimeException('Appointment not found.');
            }
            if ($appointment['status'] !== 'confirmed') {
                throw new RuntimeException('Only confirmed appointments can be cancelled.');
            }

            Appointment::setStatus($this->db, $appointmentId, 'cancelled');
            Schedule::setStatus($this->db, (int) $appointment['schedule_id'], 'available');

            $this->db->commit();
            return true;

        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function getAppointmentsByPatient(int $patientId): array
    {
        $all = Appointment::byPatient($this->db, $patientId);

        return [
            'upcoming' => array_values(array_filter($all, fn($a) => in_array($a['status'], ['confirmed', 'pending']))),
            'past'     => array_values(array_filter($all, fn($a) => in_array($a['status'], ['completed', 'cancelled']))),
        ];
    }

    public function getAvailableSlots(int $doctorId, string $date): array
    {
        return Schedule::availableSlots($this->db, $doctorId, $date);
    }
}