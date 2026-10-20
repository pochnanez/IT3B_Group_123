<?php
helper('form');
$accountType = $accountType ?? 'customer';
$title = $accountType === 'customer' ? 'Create an account' : 'Create ' . $accountType . ' account';
$action = ['customer' => 'register/save', 'employee' => 'employees/create', 'admin' => 'admin/register'][$accountType];
$formInput = session()->getFlashdata('formInput') ?? [];
$field = static fn (string $name): string => esc($formInput[$name] ?? '', 'attr');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title) ?> | Sun Son Solar</title>
    <link rel="stylesheet" href="<?= base_url('css/R_Style.css') ?>">
</head>
<body>
    <main class="registration-container">

        <aside class="welcome-panel">
            <a class="brand" href="<?= site_url(session()->get('account') ? 'account' : '/') ?>">
                <img src="<?= base_url('images/logo.png') ?>" alt="Sun Son Solar logo" class="logo">
                <p>SUN SON SOLAR<br><span>Clean energy. Brighter tomorrows.</span></p>
            </a>
            <div class="welcome-message">
                <p class="eyebrow">A BRIGHTER BEGINNING</p>
                <h2>Your next chapter<br>starts with sunshine.</h2>
                <p>Welcome to Sun Son Solar. Create your account and take the first step toward a brighter tomorrow.</p>
            </div>

            <div class="sun-illustration" aria-hidden="true"><div class="sun"></div></div>
        </aside>

        <div class="form-panel">
            <header class="header">
                <p class="eyebrow">LET'S GET STARTED</p>
                <h1><?= esc($title) ?></h1>
                <p><?= $accountType === 'customer' ? 'Enter your details below to join Sun Son Solar.' : 'Create an account for an authorized team member.' ?></p>
            </header>
            <?= view('partials/feedback') ?>
            <form id="registrationForm" action="<?= base_url($action) ?>" method="POST">
                <?= csrf_field() ?>
                <fieldset class="form-section">
                    <legend><span class="section-number">01</span> Personal details</legend>
                    <div class="form-row">
                        <div class="input-group">
                            <label for="firstname">First name</label>
                            <input id="firstname" name="firstname" value="<?= $field('firstname') ?>" maxlength="50" placeholder="Enter first name" autocomplete="given-name" required>
                        </div>
                        <div class="input-group">
                            <label for="middlename">Middle name</label>
                            <input id="middlename" name="middlename" value="<?= $field('middlename') ?>" maxlength="50" placeholder="Enter middle name" autocomplete="additional-name" aria-describedby="middleNameHelp">
                            <p class="helper-text" id="middleNameHelp">Optional. Leave blank if not applicable.</p>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="input-group">
                            <label for="lastname">Last name</label>
                            <input id="lastname" name="lastname" value="<?= $field('lastname') ?>" maxlength="50" placeholder="Enter last name" autocomplete="family-name" required>
                        </div>
                        <div class="input-group">
                            <label for="birthdate">Birthdate</label>
                            <input type="date" id="birthdate" name="birthdate" value="<?= $field('birthdate') ?>" max="<?= date('Y-m-d') ?>" autocomplete="bday" required>
                        </div>
                    </div>
                    <fieldset class="gender-group">
                        <legend>Gender</legend>
                        <div class="gender-options">
                            <?php foreach (['Male', 'Female', 'Prefer not to say'] as $gender): ?>
                            <label><input type="radio" name="gender" value="<?= esc($gender, 'attr') ?>" <?= ($formInput['gender'] ?? '') === $gender ? 'checked' : '' ?> required> <?= esc($gender) ?></label>
                            <?php endforeach ?>
                        </div>
                    </fieldset>
                </fieldset>

                <fieldset class="form-section">
                    <legend><span class="section-number">02</span> Contact information</legend>
                    <div class="form-row">
                        <div class="input-group">
                            <label for="email">Email address</label>
                            <input type="email" id="email" name="email" value="<?= $field('email') ?>" maxlength="100" placeholder="you@example.com" autocomplete="email" required>
                        </div>
                        <div class="input-group">
                            <label for="contact">Contact number</label>
                            <input type="tel" id="contact" name="contact" value="<?= $field('contact') ?>" placeholder="09XXXXXXXXX" autocomplete="tel" pattern="09[0-9]{9}" title="Contact number must start with 09 and have 11 digits" required>
                        </div>
                    </div>
                    <div class="input-group">
                        <label for="address">Address</label>
                        <input id="address" name="address" value="<?= $field('address') ?>" maxlength="255" placeholder="Enter complete address" autocomplete="street-address" required>
                    </div>
                    <?php if ($accountType === 'employee'): ?>
                    <div class="input-group">
                        <label for="department">Department</label>
                        <select id="department" name="department" required>
                            <option value="">Select Department</option>
                            <?php foreach (\App\Libraries\Accounts::DEPARTMENTS as $department): ?>
                            <option value="<?= esc($department, 'attr') ?>" <?= ($formInput['department'] ?? '') === $department ? 'selected' : '' ?>><?= esc($department) ?></option>
                            <?php endforeach ?>
                        </select>
                        <p class="helper-text" id="departmentHelp">Department does not grant administrative permissions.</p>
                    </div>
                    <?php endif ?>
                </fieldset>

                <fieldset class="form-section account-section">
                    <legend><span class="section-number">03</span> Account details</legend>
                    <div class="input-group">
                        <label for="username">Username</label>
                        <input id="username" name="username" value="<?= $field('username') ?>" minlength="4" maxlength="50" pattern="[A-Za-z0-9_.\-]+" title="Use letters, numbers, underscores, dots or hyphens" placeholder="Choose a username" autocomplete="username" required>
                    </div>
                    <div class="form-row">
                        <div class="input-group">
                            <label for="password">Password</label>
                            <div class="password-field">
                                <input type="password" id="password" name="password" placeholder="Create a password" autocomplete="new-password" minlength="8" aria-describedby="passwordHelp" required>
                                <button type="button" class="password-toggle" aria-controls="password" aria-label="Show password">Show</button>
                            </div>
                            <p class="helper-text" id="passwordHelp">Use at least 8 characters, up to 72 bytes.</p>
                        </div>
                        <div class="input-group">
                            <label for="confirmPassword">Confirm password</label>
                            <div class="password-field">
                                <input type="password" id="confirmPassword" name="confirmPassword" placeholder="Re-enter password" autocomplete="new-password" minlength="8" aria-describedby="passwordMessage" required>
                                <button type="button" class="password-toggle" aria-controls="confirmPassword" aria-label="Show confirm password">Show</button>
                            </div>
                            <p id="passwordMessage" aria-live="polite"></p>
                        </div>
                    </div>
                </fieldset>
                <button type="submit" class="signup-btn">Create account <span aria-hidden="true">&rarr;</span></button>
                <?php if ($accountType === 'customer'): ?>
                <p class="signin">Already have an account? <a href="<?= site_url('login') ?>">Sign in</a></p>
                <?php else: ?>
                <p class="signin"><a href="<?= site_url('account') ?>">Back to your account</a></p>
                <?php endif ?>
            </form>
        </div>
    </main>
    <script src="<?= base_url('js/R_Script.js') ?>"></script>
</body>
</html>
