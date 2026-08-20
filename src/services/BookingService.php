<?php
// src/services/BookingService.php
// Core booking conflict prevention uses a DB transaction and row lock,
// reinforced by the UNIQUE constraints on schedules.status and appointments.schedule_id in schema.sql.
//
// Booking flow: patient submits -> status='pending' (the slot is immediately locked as booked)
//          -> doctor confirms -> status='confirmed' (only then is a confirmation email sent).

class BookingService
{
    private PDO $db;
    private MailService $mailer;

    public function __construct(PDO $db, ?MailService $mailer = null)
    {
        $this->db     = $db;
        $this->mailer = $mailer ?? new MailService();
    }

    /**
    * Patient submits an appointment. The status starts as pending until the doctor confirms it.
    * @throws RuntimeException If the slot was booked by someone else first.
     */
    public function bookAppointment(int $patientId, int $scheduleId, string $reason = '', string $visitType = 'new_case'): int
    {
        $this->db->beginTransaction();
        try {
            // FOR UPDATE locks this row so concurrent requests cannot both read available and insert.
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
                $visitType,
                'pending'
            );

            $this->db->commit();

            // ---- Notify the doctor: new appointment awaiting confirmation (in-app + email; failures do not affect the booking). ----
            $doctor  = Doctor::findById($this->db, (int) $schedule['doctor_id']);
            $patient = User::findById($this->db, $patientId);

            if ($doctor && $doctor['user_id']) {
                try {
                    (new NotificationService($this->db))->notify(
                        (int) $doctor['user_id'],
                        "New appointment request from {$patient['full_name']} on {$schedule['slot_date']}.",
                        $appointmentId
                    );
                } catch (Throwable $e) {
                    error_log('In-app notification (doctor) failed: ' . $e->getMessage());
                }
            }
            if ($doctor && !empty($doctor['email'])) {
                try {
                    $this->mailer->sendAppointmentRequestedToDoctor($doctor, $patient, $schedule);
                } catch (Throwable $e) {
                    error_log('Email to doctor failed: ' . $e->getMessage());
                }
            }

            return $appointmentId;

        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
    * Doctor confirms an appointment. Only the assigned doctor can confirm it.
    * @throws RuntimeException If the appointment does not exist, belongs to another doctor, or is no longer pending.
     */
    public function confirmAppointment(int $appointmentId, int $doctorId): bool
    {
        $stmt = $this->db->prepare('SELECT * FROM appointments WHERE appointment_id = :id AND doctor_id = :doctor_id LIMIT 1');
        $stmt->execute(['id' => $appointmentId, 'doctor_id' => $doctorId]);
        $appointment = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$appointment) {
            throw new RuntimeException('Appointment not found.');
        }
        if ($appointment['status'] !== 'pending') {
            throw new RuntimeException('Only pending appointments can be confirmed.');
        }

        Appointment::setStatus($this->db, $appointmentId, 'confirmed');

        // ---- Notify the patient: appointment confirmed (in-app + email). ----
        $schedule = Schedule::findById($this->db, (int) $appointment['schedule_id']);
        $doctor   = Doctor::findById($this->db, $doctorId);
        $patient  = User::findById($this->db, (int) $appointment['patient_id']);

        try {
            (new NotificationService($this->db))->notify(
                (int) $appointment['patient_id'],
                "Your appointment on {$schedule['slot_date']} has been confirmed by Dr. {$doctor['full_name']}.",
                $appointmentId
            );
        } catch (Throwable $e) {
            error_log('In-app notification (patient) failed: ' . $e->getMessage());
        }

        try {
            $this->mailer->sendAppointmentConfirmedToPatient($patient, $doctor, $schedule);
        } catch (Throwable $e) {
            error_log('Email to patient failed: ' . $e->getMessage());
        }

        return true;
    }

    /**
    * Patient/admin cancels an appointment (pending or confirmed appointments may be cancelled).
    * When $byPatientId is provided, only that patient's appointment can be cancelled; null means admin action without an ownership check.
    * @throws RuntimeException If the appointment does not exist or its status does not allow cancellation.
     */
    public function cancelAppointment(int $appointmentId, ?int $byPatientId = null): bool
    {
        $this->db->beginTransaction();
        try {
            $sql = 'SELECT * FROM appointments WHERE appointment_id = :id';
            $params = ['id' => $appointmentId];
            if ($byPatientId !== null) {
                $sql .= ' AND patient_id = :pid';
                $params['pid'] = $byPatientId;
            }
            $stmt = $this->db->prepare($sql . ' LIMIT 1');
            $stmt->execute($params);
            $appointment = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$appointment) {
                throw new RuntimeException('Appointment not found.');
            }
            if (!in_array($appointment['status'], ['pending', 'confirmed'], true)) {
                throw new RuntimeException('Only pending or confirmed appointments can be cancelled.');
            }

            $wasConfirmed = $appointment['status'] === 'confirmed';

            Appointment::setStatus($this->db, $appointmentId, 'cancelled');
            Schedule::setStatus($this->db, (int) $appointment['schedule_id'], 'available');

            $this->db->commit();

            // Email the patient only when a previously confirmed appointment is cancelled; in-app notification is enough for pending cancellations.
            if ($wasConfirmed) {
                $schedule = Schedule::findById($this->db, (int) $appointment['schedule_id']);
                $doctor   = Doctor::findById($this->db, (int) $appointment['doctor_id']);
                $patient  = User::findById($this->db, (int) $appointment['patient_id']);
                try {
                    $this->mailer->sendAppointmentCancelledToPatient($patient, $doctor, $schedule);
                } catch (Throwable $e) {
                    error_log('Cancellation email failed: ' . $e->getMessage());
                }
            }

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

    public function getAppointmentsByDoctor(int $doctorId): array
    {
        $all = Appointment::byDoctor($this->db, $doctorId);

        return [
            'pending'  => array_values(array_filter($all, fn($a) => $a['status'] === 'pending')),
            'upcoming' => array_values(array_filter($all, fn($a) => $a['status'] === 'confirmed')),
            'past'     => array_values(array_filter($all, fn($a) => in_array($a['status'], ['completed', 'cancelled']))),
        ];
    }

    public function getAvailableSlots(int $doctorId, string $date): array
    {
        return Schedule::availableSlots($this->db, $doctorId, $date);
    }
}
