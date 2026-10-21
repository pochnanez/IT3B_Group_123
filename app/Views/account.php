<?php helper('form'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your account | Sun Son Solar</title>
    <link rel="stylesheet" href="<?= base_url('css/R_Style.css') ?>">
</head>
<body>
<main class="account-container">
    <a class="brand" href="<?= site_url('account') ?>"><img class="logo" src="<?= base_url('images/logo.png') ?>" alt="Sun Son Solar logo"><p>SUN SON SOLAR</p></a>
    <header class="header"><h1>Welcome, <?= esc($account['name']) ?></h1><p>You are signed in as <?= esc($account['role']) ?>.</p></header>
    <?= view('partials/feedback') ?>
    <p>Your account is ready. Booking and dashboard features will be added in the next project phases.</p>
    <?php if (in_array($account['role'], ['admin', 'hr'], true)): ?><p><a href="<?= site_url('employees/create') ?>">Create an employee account</a></p><?php endif ?>
    <?php if ($account['role'] === 'admin'): ?><p><a href="<?= site_url('admin/register') ?>">Create an administrator account</a></p><?php endif ?>
    <form action="<?= site_url('logout') ?>" method="post"><?= csrf_field() ?><button class="signup-btn" type="submit">Sign out</button></form>
</main>
</body>
</html>
