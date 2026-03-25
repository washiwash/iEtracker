// ===== PASSWORD VISIBILITY TOGGLES =====
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

// ===== REAL-TIME VALIDATION =====
const allowedDomains = ["gmail.com", "yahoo.com", "outlook.com", "company.com"];

function showError(elementId, message) {
  const errorElement = document.getElementById(elementId);
  if (errorElement) {
    errorElement.textContent = message;
    errorElement.style.display = message ? "block" : "none";
  }
}

function validateFullName() {
  const fullName = document.querySelector('input[name="full_name"]');
  const value = fullName.value.trim();
  
  if (value === "") {
    showError('fullNameError', "Full name is required.");
    return false;
  } else if (!/^[A-Za-z]+(\s[A-Za-z]+)*$/.test(value)) {
    showError('fullNameError', "Full name should contain only letters and single spaces between words.");
    return false;
  } else {
    showError('fullNameError', "");
    return true;
  }
}

function validateEmail() {
  const email = document.querySelector('input[name="email"]');
  const value = email.value.trim();
  
  if (value === "") {
    showError('emailError', "Email address is required.");
    return false;
  } else if (!/.+@.+\..+/.test(value)) {
    showError('emailError', "Please enter a valid email address.");
    return false;
  } else {
    const domain = value.substring(value.lastIndexOf("@") + 1);
    if (!allowedDomains.includes(domain)) {
      showError('emailError', "Email domain not allowed. Please use: gmail.com, yahoo.com, outlook.com, or company.com");
      return false;
    } else {
      showError('emailError', "");
      return true;
    }
  }
}

function validateJobTitle() {
  const jobTitle = document.querySelector('select[name="job_title"]');
  const value = jobTitle.value;
  
  if (value === "" || value === null) {
    showError('jobTitleError', "Please select a role/position.");
    return false;
  } else {
    showError('jobTitleError', "");
    return true;
  }
}

function updateRequirement(id, isMet) {
  const element = document.getElementById(id);
  const icon = element.querySelector('.req-icon');
  
  if (isMet) {
    element.classList.add('met');
    icon.className = 'bi bi-check-circle req-icon';
  } else {
    element.classList.remove('met');
    icon.className = 'bi bi-x-circle req-icon';
  }
}

function validatePassword() {
  const password = document.querySelector('input[name="password"]');
  const value = password.value;
  const errors = [];
  
  // Check each requirement
  const hasLength = value.length >= 8;
  const hasUppercase = /[A-Z]/.test(value);
  const hasLowercase = /[a-z]/.test(value);
  const hasNumber = /[0-9]/.test(value);
  const hasSpecial = /[\W_]/.test(value);
  
  // Update requirement indicators
  updateRequirement('req-length', hasLength);
  updateRequirement('req-uppercase', hasUppercase);
  updateRequirement('req-lowercase', hasLowercase);
  updateRequirement('req-number', hasNumber);
  updateRequirement('req-special', hasSpecial);
  
  // Show/hide requirements container
  const requirementsContainer = document.querySelector('.password-requirements');
  if (value.length > 0) {
    requirementsContainer.classList.add('active');
  } else {
    requirementsContainer.classList.remove('active');
  }
  
  if (value === "") {
    showError('passwordError', "Password is required.");
    return false;
  }
  
  if (!hasLength || !hasUppercase || !hasLowercase || !hasNumber || !hasSpecial) {
    showError('passwordError', "");
    return false;
  } else {
    showError('passwordError', "");
    return true;
  }
}

function validateConfirmPassword() {
  const password = document.querySelector('input[name="password"]');
  const confirmPassword = document.querySelector('input[name="confirm_password"]');
  const value = confirmPassword.value;
  
  if (value === "") {
    showError('confirmPasswordError', "Please confirm your password.");
    return false;
  } else if (value !== password.value) {
    showError('confirmPasswordError', "Passwords do not match.");
    return false;
  } else {
    showError('confirmPasswordError', "");
    return true;
  }
}

function validateTerms() {
  const terms = document.querySelector('input[name="terms"]');
  
  if (!terms.checked) {
    showError('termsError', "You must agree to the Terms of Service and Privacy Policy.");
    return false;
  } else {
    showError('termsError', "");
    return true;
  }
}

function validateForm() {
  const isFullNameValid = validateFullName();
  const isEmailValid = validateEmail();
  const isJobTitleValid = validateJobTitle();
  const isPasswordValid = validatePassword();
  const isConfirmPasswordValid = validateConfirmPassword();
  const isTermsValid = validateTerms();
  
  const submitBtn = document.querySelector('button[type="submit"]');
  
  if (isFullNameValid && isEmailValid && isJobTitleValid && isPasswordValid && isConfirmPasswordValid && isTermsValid) {
    submitBtn.disabled = false;
  } else {
    submitBtn.disabled = true;
  }
}

// ===== ATTACH EVENT LISTENERS =====
document.addEventListener('DOMContentLoaded', () => {
  const fullNameInput = document.querySelector('input[name="full_name"]');
  const emailInput = document.querySelector('input[name="email"]');
  const jobTitleSelect = document.querySelector('select[name="job_title"]');
  const passwordInput = document.querySelector('input[name="password"]');
  const confirmPasswordInput = document.querySelector('input[name="confirm_password"]');
  const termsInput = document.querySelector('input[name="terms"]');
  
  fullNameInput.addEventListener('input', () => {
    validateFullName();
    validateForm();
  });
  
  emailInput.addEventListener('input', () => {
    validateEmail();
    validateForm();
  });
  
  jobTitleSelect.addEventListener('change', () => {
    validateJobTitle();
    validateForm();
  });
  
  passwordInput.addEventListener('input', () => {
    validatePassword();
    if (confirmPasswordInput.value) {
      validateConfirmPassword();
    }
    validateForm();
  });
  
  confirmPasswordInput.addEventListener('input', () => {
    validateConfirmPassword();
    validateForm();
  });
  
  termsInput.addEventListener('change', () => {
    validateTerms();
    validateForm();
  });
  
  // Initial validation state
  validateForm();
});


function validateForm() {
  const fullName = document.querySelector('input[name="full_name"]');
  const email = document.querySelector('input[name="email"]');
  const jobTitle = document.querySelector('select[name="job_title"]');
  const password = document.querySelector('input[name="password"]');
  const confirmPassword = document.querySelector('input[name="confirm_password"]');
  const terms = document.querySelector('input[name="terms"]');
  const submitBtn = document.querySelector('button[type="submit"]');

  // Check if all required fields are filled
  const isValid = fullName.value.trim() !== "" &&
                  email.value.trim() !== "" &&
                  jobTitle.value !== "" &&
                  password.value !== "" &&
                  confirmPassword.value !== "" &&
                  terms.checked;

  // Disable button if not valid, enable if valid
  submitBtn.disabled = !isValid;
}

document.addEventListener('DOMContentLoaded', () => {
  const inputs = document.querySelectorAll('input[name="full_name"], input[name="email"], select[name="job_title"], input[name="password"], input[name="confirm_password"], input[name="terms"]');
  
  inputs.forEach(input => {
    input.addEventListener('input', validateForm);
    input.addEventListener('change', validateForm);
  });

  // Initial state - button should be disabled
  validateForm();
});