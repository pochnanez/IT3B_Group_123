var password = document.getElementById("password");
var confirmPassword = document.getElementById("confirmPassword");
var passwordMessage = document.getElementById("passwordMessage");

confirmPassword.addEventListener("input", function() {

    if (confirmPassword.value === "") {
        passwordMessage.textContent = "";
    }
    else if (password.value === confirmPassword.value) {
        passwordMessage.textContent = "Passwords match.";
        passwordMessage.style.color = "green";
    }
    else {
        passwordMessage.textContent = "Passwords do not match.";
        passwordMessage.style.color = "red";
    }

});
