document.addEventListener("DOMContentLoaded", function () {
  // ─── Notification Panel ───
  const toggleBtn = document.getElementById("notifToggleBtn");
  const panel = document.getElementById("notifPanel");
  const closeBtn = document.getElementById("notifCloseBtn");

  if (toggleBtn && panel) {
    toggleBtn.addEventListener("click", function (e) {
      e.stopPropagation();
      panel.classList.toggle("open");
    });

    if (closeBtn) {
      closeBtn.addEventListener("click", function () {
        panel.classList.remove("open");
      });
    }

    document.addEventListener("click", function (e) {
      if (!panel.contains(e.target) && !toggleBtn.contains(e.target)) {
        panel.classList.remove("open");
      }
    });
  }

  // ─── Help Modal ───
  const helpBtn = document.getElementById("helpToggleBtn");
  const helpModal = document.getElementById("helpModal");
  const helpCloseBtn = document.getElementById("helpCloseBtn");

  if (helpBtn && helpModal) {
    helpBtn.addEventListener("click", function () {
      helpModal.classList.add("open");
    });

    if (helpCloseBtn) {
      helpCloseBtn.addEventListener("click", function () {
        helpModal.classList.remove("open");
      });
    }

    helpModal.addEventListener("click", function (e) {
      if (e.target === helpModal) {
        helpModal.classList.remove("open");
      }
    });
  }

  // ─── Shared: Escape key closes panels/modals ───
  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape") {
      if (panel) panel.classList.remove("open");
      if (helpModal) helpModal.classList.remove("open");
    }
  });

  //Password visibility toggle for profile security page
const button = document.querySelector('.password-btn');
const passwordEye = document.querySelector('.password-eye');
const passwordField = document.querySelector('#password');

const confirm_button = document.querySelector('.confirm_password-btn');
const confirm_passwordEye = document.querySelector('.confirm_password-eye');
const confirmPasswordField = document.querySelector('#confirm_password');

const new_password_button = document.querySelector('.new_password-btn');
const new_passwordEye = document.querySelector('.new_password-eye');
const newPasswordField = document.querySelector('#new_password');

function togglePassword() {
  if (passwordEye) {
    passwordEye.classList.toggle('bi-eye-slash-fill');
    passwordEye.classList.toggle('bi-eye-fill');
  }
}

function toggleConfirmPassword() {
  if (confirm_passwordEye) {
    confirm_passwordEye.classList.toggle('bi-eye-slash-fill');
    confirm_passwordEye.classList.toggle('bi-eye-fill');
  }
}

function toggleNewPassword() {
  if (new_passwordEye) {
    new_passwordEye.classList.toggle('bi-eye-slash-fill');
    new_passwordEye.classList.toggle('bi-eye-fill');
  }
}


function switchPasswordType() {
  if (passwordField && passwordField.type === "password") {
    passwordField.type = "text";
  } else if (passwordField) {
    passwordField.type = "password";
  }
}

function switchConfirmPasswordType() {
  if (confirmPasswordField && confirmPasswordField.type === "password") {
    confirmPasswordField.type = "text";
  } else if (confirmPasswordField) {
    confirmPasswordField.type = "password";
  }
}

function switchNewPasswordType() {
  if (newPasswordField && newPasswordField.type === "password") {
    newPasswordField.type = "text";
  } else if (newPasswordField) {
    newPasswordField.type = "password";
  }
}

if (button) {
  button.addEventListener('click', () => {
    togglePassword();
    switchPasswordType();
  });
}

if (confirm_button) {
  confirm_button.addEventListener('click', () => {
    toggleConfirmPassword();
    switchConfirmPasswordType();
  });
}

if (new_password_button) {
  new_password_button.addEventListener('click', () => {
    toggleNewPassword();
    switchNewPasswordType();
  });
}

// ===== PASSWORD VALIDATION FOR PROFILE SECURITY ===== 
function validateCurrentPassword() {
  const currentPassword = document.querySelector('input[name="current_password"]');
  const value = currentPassword ? currentPassword.value : "";
  
  if (value === "") {
    showError('currentPasswordError', "Current password is required.");
    return false;
  } else if (value.length < 3) {
    showError('currentPasswordError', "");
    return false;
  } else {
    // Verify current password via AJAX
    fetch('../../scripts/verify_password.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ password: value })
    })
    .then(response => response.json())
    .then(data => {
      if (!data.valid) {
        showError('currentPasswordError', "Current password is incorrect.");
      } else {
        showError('currentPasswordError', "");
      }
    })
    .catch(error => {
      console.error('Error:', error);
      showError('currentPasswordError', "");
    });
  }
}

function validateConfirmPassword() {
  const newPassword = document.querySelector('input[name="new_password"]');
  const confirmPassword = document.querySelector('input[name="confirm_password"]');
  const value = confirmPassword ? confirmPassword.value : "";
  const newPass = newPassword ? newPassword.value : "";
  
  let isValid = false;
  
  if (value === "") {
    showError('confirmPasswordError', "Please confirm your password.");
    isValid = false;
  } else if (value !== newPass) {
    showError('confirmPasswordError', "Passwords do not match.");
    isValid = false;
  } else {
    showError('confirmPasswordError', "");
    isValid = true;
  }
  
  validationState.confirmPassword = isValid;
  updateButtonState();
  return isValid;
}

function updateRequirement(id, isMet) {
  const element = document.getElementById(id);
  if (!element) return;
  const icon = element.querySelector('.req-icon');
  if (!icon) return;
  
  if (isMet) {
    element.classList.add('met');
    icon.className = 'bi bi-check-circle req-icon';
  } else {
    element.classList.remove('met');
    icon.className = 'bi bi-x-circle req-icon';
  }
}

function validateNewPassword() {
  const newPassword = document.querySelector('input[name="new_password"]');
  const currentPassword = document.querySelector('input[name="current_password"]');
  const value = newPassword ? newPassword.value : "";
  const currentValue = currentPassword ? currentPassword.value : "";
  
  // Check each requirement
  const hasLength = value.length >= 8;
  const hasUppercase = /[A-Z]/.test(value);
  const hasLowercase = /[a-z]/.test(value);
  const hasNumber = /[0-9]/.test(value);
  const hasSpecial = /[\W_]/.test(value);
  const isDifferent = value !== currentValue || currentValue === "";
  
  // Update requirement indicators
  updateRequirement('req-length', hasLength);
  updateRequirement('req-uppercase', hasUppercase);
  updateRequirement('req-lowercase', hasLowercase);
  updateRequirement('req-number', hasNumber);
  updateRequirement('req-special', hasSpecial);
  
  // Show/hide requirements container
  const requirementsContainer = document.querySelector('.password-requirements');
  if (requirementsContainer) {
    if (value.length > 0) {
      requirementsContainer.classList.add('active');
    } else {
      requirementsContainer.classList.remove('active');
    }
  }
  
  let isValid = false;
  
  if (value === "") {
    showError('newPasswordError', "New password is required.");
    isValid = false;
  } else if (!isDifferent && currentValue !== "") {
    showError('newPasswordError', "New password must be different from current password.");
    isValid = false;
  } else if (!hasLength || !hasUppercase || !hasLowercase || !hasNumber || !hasSpecial) {
    showError('newPasswordError', "");
    isValid = false;
  } else {
    showError('newPasswordError', "");
    isValid = true;
  }
  
  validationState.newPassword = isValid;
  updateButtonState();
  return isValid;
}

function showError(elementId, message) {
  const errorElement = document.getElementById(elementId);
  if (errorElement) {
    errorElement.textContent = message;
    errorElement.style.display = message ? "block" : "none";
  }
}

// Attach listeners for password validation if on profile security page
const currentPasswordInput = document.querySelector('input[name="current_password"]');
if (currentPasswordInput) {
  currentPasswordInput.addEventListener('input', validateCurrentPassword);
}

const newPasswordInput = document.querySelector('input[name="new_password"]');
if (newPasswordInput) {
  newPasswordInput.addEventListener('input', validateNewPassword);
}

const confirmPasswordInput = document.querySelector('input[name="confirm_password"]');
if (confirmPasswordInput) {
  confirmPasswordInput.addEventListener('input', validateConfirmPassword);
}

// Attach listeners for profile settings page
const fullNameInput = document.querySelector('input[name="full_name"]');
if (fullNameInput) {
  fullNameInput.addEventListener('input', validateFullName);
}

const emailInput = document.querySelector('input[name="email"]');
if (emailInput) {
  emailInput.addEventListener('input', validateEmail);
}

function validateFullName() {
  const fullName = document.querySelector('input[name="full_name"]');
  const value = fullName ? fullName.value.trim() : "";
  
  let isValid = false;
  
  if (value === "") {
    showError('fullNameError', "Full name is required.");
    isValid = false;
  } else if (!/^[A-Za-z]+(\s[A-Za-z]+)*$/.test(value)) {
    showError('fullNameError', "Full name should contain only letters and single spaces between words.");
    isValid = false;
  } else {
    showError('fullNameError', "");
    isValid = true;
  }
  
  validationState.fullName = isValid;
  updateButtonState();
  return isValid;
}

function validateEmail() {
  const email = document.querySelector('input[name="email"]');
  const value = email ? email.value.trim() : "";
  const allowedDomains = ["gmail.com", "yahoo.com", "outlook.com", "company.com"];
  
  let isValid = false;
  
  if (value === "") {
    showError('emailError', "Email address is required.");
    isValid = false;
  } else if (!/.+@.+\..+/.test(value)) {
    showError('emailError', "Please enter a valid email address.");
    isValid = false;
  } else {
    const domain = value.substring(value.lastIndexOf("@") + 1);
    if (!allowedDomains.includes(domain)) {
      showError('emailError', "Email domain not allowed. Please use: gmail.com, yahoo.com, outlook.com, or company.com");
      isValid = false;
    } else {
      showError('emailError', "");
      isValid = true;
    }
  }
  
  validationState.email = isValid;
  updateButtonState();
  return isValid;
}

// ===== FORM VALIDATION AND BUTTON STATE MANAGEMENT =====
// Store validation state for each field
const validationState = {
  currentPassword: false,
  newPassword: false,
  confirmPassword: false,
  fullName: false,
  email: false,
  currentPasswordVerified: false
};

function updateButtonState() {
  const submitBtn = document.querySelector('form .profile-save-btn');
  if (!submitBtn) return;
  
  // Check if on security form (profile_security.php)
  const currentPasswordInput = document.getElementById('password');
  if (currentPasswordInput) {
    // Security form - needs all three password fields valid
    if (validationState.currentPasswordVerified && validationState.newPassword && validationState.confirmPassword) {
      submitBtn.disabled = false;
    } else {
      submitBtn.disabled = true;
    }
  } else {
    // Settings form - needs full name and email valid
    if (validationState.fullName && validationState.email) {
      submitBtn.disabled = false;
    } else {
      submitBtn.disabled = true;
    }
  }
}

function validateSecurityForm() {
  const isNewPasswordValid = validateNewPassword();
  const isConfirmPasswordValid = validateConfirmPassword();
  
  validationState.newPassword = isNewPasswordValid;
  validationState.confirmPassword = isConfirmPasswordValid;
  
  updateButtonState();
}

function validateSettingsForm() {
  const isFullNameValid = validateFullName();
  const isEmailValid = validateEmail();
  
  validationState.fullName = isFullNameValid;
  validationState.email = isEmailValid;
  
  updateButtonState();
}

// Update password validation to return boolean checks without AJAX
function validateCurrentPasswordSync() {
  const currentPassword = document.querySelector('input[name="current_password"]');
  const value = currentPassword ? currentPassword.value : "";
  
  if (value === "") {
    return false;
  }
  return true;
}

function validateNewPasswordSync() {
  const newPassword = document.querySelector('input[name="new_password"]');
  const value = newPassword ? newPassword.value : "";
  
  const hasLength = value.length >= 8;
  const hasUppercase = /[A-Z]/.test(value);
  const hasLowercase = /[a-z]/.test(value);
  const hasNumber = /[0-9]/.test(value);
  const hasSpecial = /[\W_]/.test(value);
  
  return hasLength && hasUppercase && hasLowercase && hasNumber && hasSpecial;
}

function validateConfirmPasswordSync() {
  const newPassword = document.querySelector('input[name="new_password"]');
  const confirmPassword = document.querySelector('input[name="confirm_password"]');
  const newPass = newPassword ? newPassword.value : "";
  const confirmPass = confirmPassword ? confirmPassword.value : "";
  
  if (confirmPass === "") {
    return false;
  }
  
  return confirmPass === newPass;
}

// Update validateCurrentPassword to include real-time server check
function validateCurrentPasswordWithServerCheck() {
  const currentPassword = document.querySelector('input[name="current_password"]');
  const value = currentPassword ? currentPassword.value : "";
  
  if (value === "") {
    showError('currentPasswordError', "Current password is required.");
    validationState.currentPasswordVerified = false;
    updateButtonState();
    return false;
  } else if (value.length < 3) {
    showError('currentPasswordError', "");
    validationState.currentPasswordVerified = false;
    updateButtonState();
    return false;
  } else {
    // Verify current password via AJAX
    fetch('../../scripts/verify_password.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ password: value })
    })
    .then(response => response.json())
    .then(data => {
      if (!data.valid) {
        showError('currentPasswordError', "Current password is incorrect.");
        validationState.currentPasswordVerified = false;
      } else {
        showError('currentPasswordError', "");
        validationState.currentPasswordVerified = true;
      }
      updateButtonState();
    })
    .catch(error => {
      console.error('Error:', error);
      showError('currentPasswordError', "");
      validationState.currentPasswordVerified = false;
      updateButtonState();
    });
  }
}

// Attach listeners for security form
const currentPasswordInputSecurity = document.getElementById('password');
if (currentPasswordInputSecurity) {
  currentPasswordInputSecurity.addEventListener('input', () => {
    validateCurrentPasswordWithServerCheck();
  });
}

const newPasswordInputSecurity = document.getElementById('new_password');
if (newPasswordInputSecurity) {
  newPasswordInputSecurity.addEventListener('input', () => {
    validateNewPassword();
    validateSecurityForm();
  });
}

const confirmPasswordInputSecurity = document.getElementById('confirm_password');
if (confirmPasswordInputSecurity) {
  confirmPasswordInputSecurity.addEventListener('input', () => {
    validateConfirmPassword();
    validateSecurityForm();
  });
}

// Attach listeners for settings form
const fullNameInputSettings = document.querySelector('input[name="full_name"]');
if (fullNameInputSettings) {
  fullNameInputSettings.addEventListener('input', () => {
    validateFullName();
    validateSettingsForm();
  });
}

const emailInputSettings = document.querySelector('input[name="email"]');
if (emailInputSettings) {
  emailInputSettings.addEventListener('input', () => {
    validateEmail();
    validateSettingsForm();
  });
}

});


