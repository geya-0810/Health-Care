<?php
// src/services/MailService.php
//
// composer require phpmailer/phpmailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

class MailService
{
    /**
    * Log sending failures without throwing; email is supplementary
    * and core operations such as booking/confirmation should not fail when SMTP is unavailable.
     */
    public function send(string $toEmail, string $toName, string $subject, string $htmlBody): bool
    {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = $_ENV['SMTP_HOST'] ?? '';
            $mail->SMTPAuth   = true;
            $mail->Username   = $_ENV['SMTP_USERNAME'] ?? '';
            $mail->Password   = $_ENV['SMTP_PASSWORD'] ?? '';
            $mail->SMTPSecure = $_ENV['SMTP_ENCRYPTION'] ?? PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = (int) ($_ENV['SMTP_PORT'] ?? 587);

            $mail->setFrom($_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@example.com', $_ENV['MAIL_FROM_NAME'] ?? 'Health Center');
            $mail->addAddress($toEmail, $toName);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $htmlBody;
            $mail->AltBody = strip_tags($htmlBody);

            $mail->send();
            return true;

        } catch (PHPMailerException $e) {
            error_log("MailService failed to send to {$toEmail}: " . $mail->ErrorInfo);
            return false;
        }
    }

    public function sendAccountCredentials(array $user, string $plainPassword, string $roleLabel): bool
    {
        $subject = "Your Health Center {$roleLabel} Account";
        $body = "
            <p>Dear {$user['full_name']},</p>
            <p>An account has been created for you on the Health Center system as <strong>{$roleLabel}</strong>.</p>
            <ul>
                <li><strong>Login email:</strong> {$user['email']}</li>
                <li><strong>Temporary password:</strong> {$plainPassword}</li>
            </ul>
            <p>Please log in and change this password from your profile as soon as possible.</p>
        ";
        return $this->send($user['email'], $user['full_name'], $subject, $body);
    }

    public function sendAppointmentRequestedToDoctor(array $doctor, array $patient, array $schedule): bool
    {
        $subject = 'New Appointment Request — ' . $schedule['slot_date'];
        $body = "
            <p>Dear Dr. {$doctor['full_name']},</p>
            <p>You have a new appointment request:</p>
            <ul>
                <li><strong>Patient:</strong> {$patient['full_name']}</li>
                <li><strong>Date:</strong> {$schedule['slot_date']}</li>
                <li><strong>Time:</strong> " . substr($schedule['start_time'], 0, 5) . "</li>
            </ul>
            <p>Please log in to your dashboard to confirm or decline this appointment.</p>
        ";
        return $this->send($doctor['email'], $doctor['full_name'], $subject, $body);
    }

    public function sendAppointmentConfirmedToPatient(array $patient, array $doctor, array $schedule): bool
    {
        $subject = 'Your Appointment is Confirmed — ' . $schedule['slot_date'];
        $body = "
            <p>Dear {$patient['full_name']},</p>
            <p>Your appointment has been <strong>confirmed</strong>:</p>
            <ul>
                <li><strong>Doctor:</strong> Dr. {$doctor['full_name']} ({$doctor['specialty']})</li>
                <li><strong>Date:</strong> {$schedule['slot_date']}</li>
                <li><strong>Time:</strong> " . substr($schedule['start_time'], 0, 5) . "</li>
            </ul>
            <p>Please arrive 10 minutes early. See you then!</p>
        ";
        return $this->send($patient['email'], $patient['full_name'], $subject, $body);
    }

    public function sendAppointmentCancelledToPatient(array $patient, array $doctor, array $schedule): bool
    {
        $subject = 'Your Appointment was Cancelled — ' . $schedule['slot_date'];
        $body = "
            <p>Dear {$patient['full_name']},</p>
            <p>Your appointment with Dr. {$doctor['full_name']} on {$schedule['slot_date']}
               at " . substr($schedule['start_time'], 0, 5) . " has been cancelled.</p>
            <p>Please book another slot at your convenience.</p>
        ";
        return $this->send($patient['email'], $patient['full_name'], $subject, $body);
    }
}
