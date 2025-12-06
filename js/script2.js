const signUpButton=document.getElementById('signUpButton');
const signInButton=document.getElementById('signInButton');
const signInForm=document.getElementById('signIn');
const signUpForm=document.getElementById('signUp');
const otpVerification = document.getElementById('otpVerification');
const forgotPassword = document.getElementById('forgotPassword');
const resetPassword = document.getElementById('resetPassword');

function hideAllForms() {
  signInForm.style.display = "none";
  signUpForm.style.display = "none";
  if (otpVerification) otpVerification.style.display = "none";
  if (forgotPassword) forgotPassword.style.display = "none";
  if (resetPassword) resetPassword.style.display = "none";
  const forgotPasswordOtpVerification = document.getElementById('forgotPasswordOtpVerification');
  if (forgotPasswordOtpVerification) forgotPasswordOtpVerification.style.display = "none";
}

signUpButton.addEventListener('click',function(){
  hideAllForms();
  signUpForm.style.display="block";
})

signInButton.addEventListener('click',function(){
  hideAllForms();
  signInForm.style.display="block";
})

