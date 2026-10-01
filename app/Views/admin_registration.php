<?php
$old = session()->getFlashdata('admin_old') ?? [];
$errors = session()->getFlashdata('admin_errors') ?? [];
$value = static fn (string $field): string => esc($old[$field] ?? '', 'attr');
$error = static function (string $field) use ($errors): void {
    echo '<p class="field-error" id="' . esc($field, 'attr') . 'Error" aria-live="polite">'
        . esc($errors[$field] ?? '') . '</p>';
};
$input = static function (string $field, string $label, string $type, string $attributes = '') use ($value, $errors, $error): void {
    $sensitive = in_array($field, ['password', 'confirmPassword'], true);
    ?>
    <div class="input-group">
        <label for="<?= esc($field, 'attr') ?>"><?= esc($label) ?></label>
        <?php if ($sensitive): ?><div class="password-field"><?php endif ?>
        <input type="<?= esc($type, 'attr') ?>" id="<?= esc($field, 'attr') ?>" name="<?= esc($field, 'attr') ?>"
            <?php if (!$sensitive): ?>value="<?= $value($field) ?>"<?php endif ?>
            aria-describedby="<?= esc($field, 'attr') ?>Error" aria-invalid="<?= isset($errors[$field]) ? 'true' : 'false' ?>"
            <?= $attributes ?>>
        <?php if ($sensitive): ?>
            <button type="button" class="password-toggle" aria-controls="<?= esc($field, 'attr') ?>"
                aria-label="Show <?= esc(strtolower($label), 'attr') ?>" aria-pressed="false">Show</button>
        </div>
        <?php endif ?>
        <?php $error($field) ?>
    </div>
    <?php
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Registration | Sun Son Solar</title>
    <link rel="stylesheet" href="<?= base_url('css/AR_Style.css') ?>">
</head>
<body>
    <main class="registration-container">
        <aside class="welcome-panel">
            <div class="brand">
                <img src="<?= base_url('images/logo.png') ?>" alt="Sun Son Solar logo" class="logo">
                <p>SUN SON SOLAR<br><span>Administrator account creation</span></p>
            </div>
            <div class="welcome-message">
                <p class="eyebrow">AUTHORIZED ADMINISTRATOR ACCESS</p>
                <h2>Administrator access<br>starts here.</h2>
                <p>Register authorized Sun Son Solar personnel for administrator access.</p>
            </div>
            <div class="sun-illustration" aria-hidden="true"><div class="sun"></div></div>
        </aside>
        <div class="form-panel">
            <header class="header">
                <p class="eyebrow">ADMINISTRATOR REGISTRATION</p>
                <h1>Admin Registration</h1>
                <p>Create an administrator account for authorized Sun Son Solar personnel.</p>
                <p class="required-note">For authorized administrator account creation only. All fields are required unless marked optional.</p>
            </header>
            <?php if ($success = session()->getFlashdata('success')): ?>
                <p class="registration-notice" role="status"><?= esc($success) ?></p>
            <?php endif ?>
            <?php if ($message = session()->getFlashdata('admin_error')): ?>
                <p class="registration-notice" role="alert"><?= esc($message) ?></p>
            <?php endif ?>
            <?php if (session()->getFlashdata('error')): ?>
                <p class="registration-notice" role="alert">Your session token expired. Please submit the admin registration form again.</p>
            <?php endif ?>
            <?php if ($errors): ?>
                <p class="registration-notice" role="alert">Please correct the administrator details marked below.</p>
            <?php endif ?>
            <form id="adminRegistrationForm" action="<?= site_url('admin/register/save') ?>" method="POST">
                <?= csrf_field() ?>
                <fieldset class="form-section">
                    <legend><span class="section-number">01</span> Personal Information</legend>
                    <div class="form-row">
                        <?php $input('firstname', 'First Name', 'text', 'placeholder="Enter first name" autocomplete="given-name" maxlength="50" data-name required'); ?>
                        <?php $input('middlename', 'Middle Name (optional)', 'text', 'placeholder="Enter middle name (optional)" autocomplete="additional-name" maxlength="50" data-name'); ?>
                    </div>
                    <div class="form-row">
                        <?php $input('lastname', 'Last Name', 'text', 'placeholder="Enter last name" autocomplete="family-name" maxlength="50" data-name required'); ?>
                        <?php $input('birthdate', 'Birthdate', 'date', 'autocomplete="bday" max="' . date('Y-m-d') . '" required'); ?>
                    </div>
                    <fieldset class="gender-group" aria-describedby="genderError">
                        <legend>Gender</legend>
                        <div class="gender-options">
                            <?php foreach (['Male', 'Female', 'Prefer not to say'] as $gender): ?>
                                <label><input type="radio" name="gender" value="<?= esc($gender, 'attr') ?>"
                                    aria-describedby="genderError" <?= ($old['gender'] ?? '') === $gender ? 'checked' : '' ?> required> <?= esc($gender) ?></label>
                            <?php endforeach ?>
                        </div>
                        <?php $error('gender') ?>
                    </fieldset>
                </fieldset>
                <fieldset class="form-section">
                    <legend><span class="section-number">02</span> Contact Information</legend>
                    <div class="form-row">
                        <?php $input('email', 'Email Address', 'email', 'placeholder="Enter admin email address" autocomplete="email" maxlength="254" required'); ?>
                        <?php $input('contact', 'Contact Number', 'tel', 'placeholder="09XXXXXXXXX" autocomplete="tel" inputmode="numeric" pattern="09[0-9]{9}" minlength="11" maxlength="11" title="Enter 11 digits starting with 09" required'); ?>
                    </div>
                    <?php $input('address', 'Address', 'text', 'placeholder="Enter complete address" autocomplete="street-address" maxlength="255" required'); ?>
                </fieldset>
                <fieldset class="form-section account-section">
                    <legend><span class="section-number">03</span> Account Credentials</legend>
                    <?php $input('username', 'Username', 'text', 'placeholder="Create admin username" autocomplete="username" minlength="4" maxlength="50" required'); ?>
                    <div class="form-row">
                        <?php $input('password', 'Password', 'password', 'placeholder="Create admin password" autocomplete="new-password" minlength="8" maxlength="72" required'); ?>
                        <?php $input('confirmPassword', 'Confirm Password', 'password', 'placeholder="Confirm admin password" autocomplete="new-password" minlength="8" maxlength="72" required'); ?>
                    </div>
                    <p class="helper-text">Use at least 8 characters for the admin password (maximum 72 bytes).</p>
                </fieldset>
                <button type="submit" class="signup-btn">Create Admin Account <span aria-hidden="true">&rarr;</span></button>
            </form>
        </div>
    </main>
    
    <script src="<?= base_url('js/R_Script.js') ?>" defer></script>
</body>
</html>
