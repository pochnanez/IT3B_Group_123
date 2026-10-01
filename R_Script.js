var password = document.getElementById("password");
var confirmPassword = document.getElementById("confirmPassword");
var passwordMessage = document.getElementById("passwordMessage");

confirmPassword.addEventListener("input", function() {
    if (confirmPassword.value === "") {
        passwordMessage.textContent = "";
        return;
    }

    var matches = password.value === confirmPassword.value;
    passwordMessage.textContent = matches ? "Passwords match." : "Passwords do not match.";
    passwordMessage.style.color = matches ? "green" : "red";
});
