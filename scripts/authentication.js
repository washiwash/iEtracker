const button = document.querySelector('.password-btn');
const passwordEye = document.querySelector('.password-eye');
const passwordField = document.querySelector('#password');

const confirm_button = document.querySelector('.confirm_password-btn');
const confirm_passwordEye = document.querySelector('.confirm_password-eye');
const confirmPasswordField = document.querySelector('#confirm_password');

function togglePassword() {
  passwordEye.classList.toggle('bi-eye-slash-fill');
  passwordEye.classList.toggle('bi-eye-fill');
}

function toggleConfirmPassword() {
  confirm_passwordEye.classList.toggle('bi-eye-slash-fill');
  confirm_passwordEye.classList.toggle('bi-eye-fill');
}

function switchPasswordType() {
  if (passwordField.type === "password") {
    passwordField.type = "text";
  } else {
    passwordField.type = "password";
  }
}

function switchConfirmPasswordType() {
  if (confirmPasswordField.type === "password") {
    confirmPasswordField.type = "text";
  } else {
    confirmPasswordField.type = "password";
  }
}

button.addEventListener('click', () => {
  togglePassword();
  switchPasswordType();
});

confirm_button.addEventListener('click', () => {
  toggleConfirmPassword();
  switchConfirmPasswordType();
});