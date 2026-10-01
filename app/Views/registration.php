<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create an Account | Sun Son Solar</title>
    <link rel="stylesheet" href="<?= base_url('css/R_style.css') ?>">
</head>
<body>
    <main class="registration-container">

        <aside class="welcome-panel">
            <div class="brand">
                <img src="<?= base_url('images/logo.png') ?>" alt="Sun Son Solar logo" class="logo">
                <p>SUN SON SOLAR<br><span>Clean energy. Brighter tomorrows.</span></p>
            </div>
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
                <h1>Create an account</h1>
                <p>Enter your details below to join Sun Son Solar.</p>
            </header>

            <form id="registrationForm" action="<?= base_url('register/save') ?>" method="POST">
                <fieldset class="form-section">
                    <legend><span class="section-number">01</span> Personal details</legend>
                    <div class="form-row">
                        <div class="input-group">
                            <label for="firstname">First name</label>
                            <input id="firstname" name="firstname" placeholder="Enter first name" autocomplete="given-name" required>
                        </div>
                        <div class="input-group">
                            <label for="middlename">Middle name</label>
                            <input id="middlename" name="middlename" placeholder="Enter middle name" autocomplete="additional-name" required>
                            <p class="helper-text" id="departmentHelp">Leave N/A if not applicable</p>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="input-group">
                            <label for="lastname">Last name</label>
                            <input id="lastname" name="lastname" placeholder="Enter last name" autocomplete="family-name" required>
                        </div>
                        <div class="input-group">
                            <label for="birthdate">Birthdate</label>
                            <input type="date" id="birthdate" name="birthdate" autocomplete="bday" required>
                        </div>
                    </div>
                    <fieldset class="gender-group">
                        <legend>Gender</legend>
                        <div class="gender-options">
                            <label><input type="radio" name="gender" value="Male" required> Male</label>
                            <label><input type="radio" name="gender" value="Female"> Female</label>
                            <label><input type="radio" name="gender" value="Prefer not to say"> Prefer not to say</label>
                        </div>
                    </fieldset>
                </fieldset>

                <fieldset class="form-section">
                    <legend><span class="section-number">02</span> Contact information</legend>
                    <div class="form-row">
                        <div class="input-group">
                            <label for="email">Email address</label>
                            <input type="email" id="email" name="email" placeholder="you@example.com" autocomplete="email" required>
                        </div>
                        <div class="input-group">
                            <label for="contact">Contact number</label>
                            <input type="tel" id="contact" name="contact" placeholder="09XXXXXXXXX" autocomplete="tel" pattern="09[0-9]{9}" title="Contact number must start with 09 and have 11 digits" required>
                        </div>
                    </div>
                    <div class="input-group">
                        <label for="address">Address</label>
                        <input id="address" name="address" placeholder="Enter complete address" autocomplete="street-address" required>
                    </div>
                    <div class="input-group">
                        <label for="department">Department</label>
                        <select id="department" name="department" required>
                            <option value="">Select Department</option>
                            <option value="Administration">Administration</option>
                            <option value="IT">IT</option>
                            <option value="Dispatch">Dispatch</option>
                            <option value="Accounting">Accounting</option>
                            <option value="HR">HR</option>
                            <option value="Marketing">Marketing</option>
                            <option value="Sales">Sales</option>
                            <option value="Customer Service">Customer Service</option>
                            <option value="N/A">N/A</option>
                        </select>
                        <p class="helper-text" id="departmentHelp">Select N/A if not applicable.</p>
                    </div>
                </fieldset>

                <fieldset class="form-section account-section">
                    <legend><span class="section-number">03</span> Account details</legend>
                    <div class="input-group">
                        <label for="username">Username</label>
                        <input id="username" name="username" placeholder="Choose a username" autocomplete="username" required>
                    </div>
                    <div class="form-row">
                        <div class="input-group">
                            <label for="password">Password</label>
                            <div class="password-field">
                                <input type="password" id="password" name="password" placeholder="Create a password" autocomplete="new-password" minlength="8" aria-describedby="passwordHelp" required>
                                <button type="button" class="password-toggle" aria-controls="password" aria-label="Show password">Show</button>
                            </div>
                            <p class="helper-text" id="passwordHelp">Use at least 8 characters.</p>
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
                <p class="signin">Already have an account? <a href="#">Sign in</a></p>
            </form>
        </div>
    </main>
    <script src="<?= base_url('js/R_Script.js') ?>"></script>
</body>
</html>
