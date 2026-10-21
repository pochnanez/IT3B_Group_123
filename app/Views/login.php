<?php helper('form'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign in | Sun Son Solar</title>
    <link rel="stylesheet" href="<?= base_url('css/R_Style.css') ?>">
</head>
<body>
<main class="account-container">
    <a class="brand" href="<?= site_url(session()->get('account') ? 'account' : '/') ?>"><img class="logo" src="<?= base_url('images/logo.png') ?>" alt="Sun Son Solar logo"><p>SUN SON SOLAR</p></a>
    <header class="header"><h1>Welcome back</h1><p>Sign in to your Sun Son Solar account.</p></header>
    <?= view('partials/feedback') ?>
    <form action="<?= site_url('login') ?>" method="post">
        <?= csrf_field() ?>
        <div class="input-group"><label for="identifier">Email or username</label><input id="identifier" name="identifier" value="<?= esc(session()->getFlashdata('identifier') ?? '', 'attr') ?>" maxlength="100" autocomplete="username" required></div>
        <div class="input-group"><label for="password">Password</label><input id="password" name="password" type="password" autocomplete="current-password" required></div>
        <button class="signup-btn" type="submit">Sign in <span aria-hidden="true">&rarr;</span></button>
    </form>
    <p class="signin">New to Sun Son Solar? <a href="<?= site_url('register') ?>">Create an account</a></p>
</main>
</body>
</html>
