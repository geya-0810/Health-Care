<?php
// public/staff-header.php
// require_once __DIR__ . '/../../src/config/config.php';

$role = $_SESSION['role'] ?? '';

$navByRole = [
    'admin' => [
        'home'  => 'dashboard.php',
        'links' => [
            ['href' => 'dashboard.php',       'label' => 'Dashboard'],
            ['href' => 'manage-accounts.php', 'label' => 'Accounts'],
            ['href' => 'manage-schedules.php','label' => 'Schedules'],
            ['href' => 'reports.php',         'label' => 'Reports'],
            ['href' => '../profile.php',      'label' => 'My Account'],
        ],
    ],
    'assist' => [
        'home'  => 'manage-schedules.php',
        'links' => [
            ['href' => 'manage-schedules.php', 'label' => 'Schedules'],
            ['href' => 'manage-accounts.php',  'label' => 'Patients'],
            ['href' => '../profile.php',       'label' => 'My Account'],
        ],
    ],
    'doctor' => [
        'home'  => '../profile.php',
        'links' => [
            ['href' => '../profile.php', 'label' => 'My Appointments'],
        ],
    ],
];

$current    = $navByRole[$role] ?? $navByRole['doctor'];
$roleHome   = $current['home'];
$navLinks   = $current['links'];
$roleLabel  = ['admin' => 'Admin', 'assist' => 'Assistant', 'doctor' => 'Doctor'][$role] ?? 'Staff';
?>
<!DOCTYPE html>
<html lang="en">
<head>
     <base href="<?php echo APP_URL; ?>/admin/">
     <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' — ' : '' ?>Health Center Staff Portal</title>
     <meta charset="UTF-8">
     <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">

     <link rel="stylesheet" href="../css/bootstrap.min.css">
     <link rel="stylesheet" href="../css/font-awesome.min.css">
     <link rel="stylesheet" href="../css/custom.css">
</head>
<body style="background:#F7F7F7;">

<nav class="navbar navbar-default" style="margin-bottom:0;border-radius:0;background:#22281F;border:none;">
    <div class="container">
        <div class="navbar-header">
            <a class="navbar-brand" href="<?= htmlspecialchars($roleHome) ?>" style="color:#fff;font-weight:600;">
                <i class="fa fa-h-square" style="color:#8BC63F;"></i> Health Center
                <span style="font-size:11px;color:#8BC63F;vertical-align:middle;margin-left:4px;">
                    <?= htmlspecialchars(strtoupper($roleLabel)) ?>
                </span>
            </a>
            <button class="navbar-toggle" data-toggle="collapse" data-target="#staffNav">
                <span class="icon-bar" style="background:#fff;"></span>
                <span class="icon-bar" style="background:#fff;"></span>
                <span class="icon-bar" style="background:#fff;"></span>
            </button>
        </div>
        <div class="collapse navbar-collapse" id="staffNav">
            <ul class="nav navbar-nav navbar-right">
                <?php foreach ($navLinks as $link): ?>
                    <li><a href="<?= htmlspecialchars($link['href']) ?>" style="color:#fff;"><?= htmlspecialchars($link['label']) ?></a></li>
                <?php endforeach; ?>
                <li><a href="../logout.php" style="color:#8BC63F;">Log out</a></li>
            </ul>
        </div>
    </div>
</nav>

<section style="padding:60px 0; min-height:82vh;">
