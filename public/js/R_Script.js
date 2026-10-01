const form = document.getElementById("registrationForm");
const password = document.getElementById("password");
const confirmPassword = document.getElementById("confirmPassword");

document.querySelectorAll(".password-toggle").forEach(function(button) {
    const input = document.getElementById(button.getAttribute("aria-controls"));
    const fieldLabel = input.id === "confirmPassword" ? "confirm password" : "password";

    button.addEventListener("click", function() {
        const showPassword = input.type === "password";
        input.type = showPassword ? "text" : "password";
        button.textContent = showPassword ? "Hide" : "Show";
        button.setAttribute("aria-label", (showPassword ? "Hide " : "Show ") + fieldLabel);
    });
});

form.addEventListener("submit", function(event) {

    if (password.value.length < 8) {
        alert("Password must be at least 8 characters.");
        event.preventDefault();
        return;
    }

    if (password.value !== confirmPassword.value) {
        alert("Passwords do not match.");
        event.preventDefault();
        return;
    }

});
