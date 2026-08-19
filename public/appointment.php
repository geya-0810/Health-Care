<?php
// public/appointment.php
require_once __DIR__ . '/../src/config/config.php';

// 预约必须登录（免得又要在表单里问一次姓名/email，重复存两份用户资料）
AuthMiddleware::requireLogin();

$db = Database::getConnection();
$patientId = $_SESSION['user_id'];

$doctors    = Doctor::all($db);                 // 下拉选医生（取代原本跟db无关的Department下拉）
$errors     = [];
$successMsg = null;

// 当前选中的医生/日期（用GET保存状态，方便"查看空档"时刷新页面不丢失表单）
$selectedDoctorId = isset($_GET['doctor_id']) ? (int) $_GET['doctor_id'] : (isset($_POST['doctor_id']) ? (int) $_POST['doctor_id'] : 0);
$selectedDate     = $_GET['date'] ?? $_POST['date'] ?? '';

$availableSlots = [];
if ($selectedDoctorId && $selectedDate) {
    $availableSlots = Schedule::availableSlots($db, $selectedDoctorId, $selectedDate);
}

// ---------- 提交预约 ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $scheduleId = (int) ($_POST['schedule_id'] ?? 0);
    $visitType  = $_POST['visit_type'] ?? 'new_case';
    $reason     = trim($_POST['message'] ?? '');

    if (!$scheduleId) {
        $errors[] = 'Please select an available time slot.';
    } elseif (!in_array($visitType, ['new_case', 'follow_up', 'specialist_referral'], true)) {
        $errors[] = 'Invalid visit type.';
    } else {
        try {
            $booking = new BookingService($db);
            $booking->bookAppointment($patientId, $scheduleId, $reason, $visitType);
            header('Location: profile.php?booked=1');
            exit;
        } catch (RuntimeException $e) {
            // 例如slot被别人抢先约走了 —— 重新拉一次最新空档给用户看
            $errors[] = $e->getMessage();
            $availableSlots = Schedule::availableSlots($db, $selectedDoctorId, $selectedDate);
        } catch (Throwable $e) {
            error_log('Booking failed: ' . $e->getMessage());
            $errors[] = 'Something went wrong. Please try again.';
        }
    }
}

require_once __DIR__ . '/header.php';
?>

<!-- MAKE AN APPOINTMENT -->
     <section id="appointment" data-stellar-background-ratio="3">
          <div class="container">
               <div class="row">

                    <div class="col-md-6 col-sm-6">
                         <img src="images/appointment-image.jpg" class="img-responsive" alt="">
                    </div>

                    <div class="col-md-6 col-sm-6">

                         <?php if (!empty($errors)): ?>
                              <div class="alert alert-danger">
                                   <?php foreach ($errors as $e): ?>
                                        <div><?= htmlspecialchars($e) ?></div>
                                   <?php endforeach; ?>
                              </div>
                         <?php endif; ?>

                         <!-- STEP 1: 选医生 + 日期，查看空档 -->
                         <form id="check-availability-form" role="form" method="get" action="appointment.php">

                              <div class="section-title wow fadeInUp" data-wow-delay="0.4s">
                                   <h2>Make an appointment</h2>
                              </div>

                              <div class="wow fadeInUp" data-wow-delay="0.8s">
                                   <div class="col-md-6 col-sm-6">
                                        <label for="doctor_id">Select Doctor</label>
                                        <select class="form-control" id="doctor_id" name="doctor_id" required>
                                             <option value="">-- choose a doctor --</option>
                                             <?php foreach ($doctors as $doc): ?>
                                                  <option value="<?= (int) $doc['doctor_id'] ?>"
                                                       <?= $selectedDoctorId === (int) $doc['doctor_id'] ? 'selected' : '' ?>>
                                                       <?= htmlspecialchars($doc['full_name']) ?> — <?= htmlspecialchars($doc['specialty']) ?>
                                                  </option>
                                             <?php endforeach; ?>
                                        </select>
                                   </div>

                                   <div class="col-md-6 col-sm-6">
                                        <label for="date">Select Date</label>
                                        <input type="date" name="date" id="date" class="form-control"
                                               min="<?= date('Y-m-d') ?>"
                                               value="<?= htmlspecialchars($selectedDate) ?>" required>
                                   </div>

                                   <div class="col-md-12 col-sm-12" style="margin-top:10px;">
                                        <button type="submit" class="form-control" style="background:#8BC63F;color:#fff;border:none;">
                                             Check Availability
                                        </button>
                                   </div>
                              </div>
                         </form>

                         <!-- STEP 2: 选空档时段 + 补充资料 + 确认预约 -->
                         <?php if ($selectedDoctorId && $selectedDate): ?>
                              <form id="appointment-form" role="form" method="post" action="appointment.php" style="margin-top:24px;">
                                   <input type="hidden" name="doctor_id" value="<?= $selectedDoctorId ?>">
                                   <input type="hidden" name="date" value="<?= htmlspecialchars($selectedDate) ?>">

                                   <div class="wow fadeInUp" data-wow-delay="0.4s">

                                        <div class="col-md-12 col-sm-12">
                                             <label>Available Time Slots on <?= htmlspecialchars($selectedDate) ?></label>
                                             <?php if (empty($availableSlots)): ?>
                                                  <p class="text-muted">No available slots for this doctor on this date. Please try another date.</p>
                                             <?php else: ?>
                                                  <div class="row">
                                                       <?php foreach ($availableSlots as $slot): ?>
                                                            <div class="col-md-4 col-sm-4" style="margin-bottom:10px;">
                                                                 <label style="font-weight:normal;">
                                                                      <input type="radio" name="schedule_id"
                                                                             value="<?= (int) $slot['schedule_id'] ?>" required>
                                                                      <?= substr($slot['start_time'], 0, 5) ?> - <?= substr($slot['end_time'], 0, 5) ?>
                                                                 </label>
                                                            </div>
                                                       <?php endforeach; ?>
                                                  </div>
                                             <?php endif; ?>
                                        </div>

                                        <div class="col-md-6 col-sm-6">
                                             <label for="visit_type">Visit Type</label>
                                             <select class="form-control" id="visit_type" name="visit_type">
                                                  <option value="new_case">New Case</option>
                                                  <option value="follow_up">Follow-up Visit</option>
                                                  <option value="specialist_referral">Specialist Referral</option>
                                             </select>
                                        </div>

                                        <div class="col-md-6 col-sm-6">
                                             <label>Patient</label>
                                             <input type="text" class="form-control" value="<?= htmlspecialchars($_SESSION['full_name']) ?>" disabled>
                                        </div>

                                        <div class="col-md-12 col-sm-12">
                                             <label for="message">Additional Message</label>
                                             <textarea class="form-control" rows="4" id="message" name="message"
                                                       placeholder="Describe your symptoms or reason for visit"></textarea>
                                             <button type="submit" class="form-control" id="cf-submit" name="submit"
                                                     style="margin-top:10px;">
                                                  Confirm Appointment
                                             </button>
                                        </div>
                                   </div>
                              </form>
                         <?php endif; ?>

                    </div>

               </div>
          </div>
     </section>
<?php require_once __DIR__ . '/footer.php'; ?>