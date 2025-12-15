<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\OtpMail;

class AuthController extends Controller
{
    private function generateToken($length = 64)
    {
        return bin2hex(random_bytes($length));
    }
    
    private function generateOTP()
    {
        return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    // Helper: detect bcrypt hash
    private static function looksLikeBcrypt($value)
    {
        return is_string($value) && strlen($value) === 60 && str_starts_with($value, '$2y$');
    }
    
    // Helper: detect SHA256 hash (64 hex characters)
    private static function looksLikeSha256($value)
    {
        return is_string($value) && strlen($value) === 64 && preg_match('/^[a-f0-9]{64}$/i', $value);
    }
    
    public function login(Request $request)
    {
        // Manual validation to ensure JSON response
        $email = $request->input('email');
        $password = $request->input('password');
        
        $errors = [];
        if (empty($email)) {
            $errors[] = 'Email is required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        }
        if (empty($password)) {
            $errors[] = 'Password is required';
        }
        
        if (!empty($errors)) {
            return $this->jsonResponse(false, null, implode(', ', $errors), 400);
        }
        
        try {
            $user = DB::table('userinfo')
                ->where('email', $email)
                ->first();
            
            if ($user) {
                $storedPassword = trim($user->password);
                $incomingPassword = trim($password);
                
                $passwordMatches = false;
                
                // Try bcrypt first (only if stored password looks like bcrypt)
                if (self::looksLikeBcrypt($storedPassword)) {
                    try {
                        if (self::looksLikeSha256($incomingPassword)) {
                            // Incoming is SHA256 hash from frontend
                            // If stored is bcrypt, check if it was created from this SHA256 hash
                            // Hash::check will work if the bcrypt was created from the SHA256 hash
                            $passwordMatches = Hash::check($incomingPassword, $storedPassword);
                            
                            // Log for debugging if it fails (might indicate bcrypt was created from plain text)
                            if (!$passwordMatches) {
                                Log::info('Login failed: bcrypt hash does not match SHA256 input. Stored password might have been created from plain text instead of SHA256.');
                            }
                        } else {
                            // Incoming is plain text, check against bcrypt normally
                            $passwordMatches = Hash::check($incomingPassword, $storedPassword);
                        }
                    } catch (\Exception $e) {
                        // If Hash::check fails, continue to fallback methods
                        Log::warning('Hash::check failed: ' . $e->getMessage());
                    }
                }
                
                // If stored password is SHA256 hash (from registration with frontend hashing)
                if (!$passwordMatches && self::looksLikeSha256($storedPassword)) {
                    // Compare SHA256 to SHA256 (case-insensitive)
                    if (strtolower($storedPassword) === strtolower($incomingPassword)) {
                        $passwordMatches = true;
                    }
                }
                
                // Fallback: plain text match
                if (!$passwordMatches && $storedPassword === $incomingPassword) {
                    $passwordMatches = true;
                }
                
                // Fallback: legacy sha256(lowercase) match
                if (!$passwordMatches) {
                    $storedPasswordHash = strtolower(hash('sha256', $storedPassword));
                    $incomingPasswordLower = strtolower($incomingPassword);
                    if ($storedPasswordHash === $incomingPasswordLower) {
                        $passwordMatches = true;
                    }
                }
                
                if (!$passwordMatches) {
                    return $this->jsonResponse(false, null, 'Invalid email or password', 401);
                }
                
                $token = $this->generateToken();
                $tokenType = 'Bearer';
                $expiresAt = now()->addDays(7);
                
                // Delete old tokens
                DB::table('tokens')->where('userid', $user->userid)->delete();
                
                // Insert new token
                DB::table('tokens')->insert([
                    'userid' => $user->userid,
                    'token' => $token,
                    'token_type' => $tokenType,
                    'expires_at' => $expiresAt,
                    'created_at' => now()
                ]);
                
                return $this->jsonResponse(true, [
                    'token' => $token,
                    'user' => [
                        'userid' => $user->userid,
                        'usertype' => $user->usertype,
                        'fname' => $user->fname,
                        'lname' => $user->lname,
                        'username' => $user->username
                    ]
                ], 'Login successful');
            } else {
                return $this->jsonResponse(false, null, 'Invalid email or password', 401);
            }
        } catch (\Exception $e) {
            return $this->jsonResponse(false, null, 'Error: ' . $e->getMessage(), 500);
        }
    }
    
    public function logout(Request $request)
    {
        $token = $request->bearerToken();
        
        if (!$token) {
            return $this->jsonResponse(false, null, 'Token is required', 401);
        }
        
        try {
            DB::table('tokens')->where('token', $token)->delete();
            return $this->jsonResponse(true, null, 'Logout successful');
        } catch (\Exception $e) {
            return $this->jsonResponse(false, null, 'Error: ' . $e->getMessage(), 500);
        }
    }
    
    public function verify(Request $request)
    {
        $token = $request->bearerToken();
        
        if (!$token) {
            return $this->jsonResponse(false, null, 'Token is required', 401);
        }
        
        try {
            $tokenData = DB::table('tokens as t')
                ->join('userinfo as u', 't.userid', '=', 'u.userid')
                ->where('t.token', $token)
                ->where('t.expires_at', '>', now())
                ->select('u.*', 't.token')
                ->first();
            
            if ($tokenData) {
                // Update last_used_at
                DB::table('tokens')->where('token', $token)->update(['last_used_at' => now()]);
                
                return $this->jsonResponse(true, [
                    'user' => [
                        'userid' => $tokenData->userid,
                        'usertype' => $tokenData->usertype,
                        'fname' => $tokenData->fname,
                        'lname' => $tokenData->lname,
                        'username' => $tokenData->username,
                        'email' => $tokenData->email,
                        'image' => $tokenData->image
                    ]
                ]);
            } else {
                return $this->jsonResponse(false, null, 'Invalid or expired token', 401);
            }
        } catch (\Exception $e) {
            return $this->jsonResponse(false, null, 'Error: ' . $e->getMessage(), 500);
        }
    }
    
    public function register(Request $request)
    {
        // Manual validation to ensure JSON response
        $email = $request->input('email');
        $otp = $request->input('otp');
        
        // Registration data can come from request OR from stored OTP record
        $fname = $request->input('fname');
        $lname = $request->input('lname');
        $username = $request->input('username');
        $password = $request->input('password'); // may be null; we will fall back to stored data
        
        // Basic email/OTP validation first
        $errors = [];
        if (empty($email)) {
            $errors[] = 'Email is required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        }
        if (empty($otp)) {
            $errors[] = 'OTP is required';
        }
        
        if (!empty($errors)) {
            return $this->jsonResponse(false, null, implode(', ', $errors), 400);
        }
        
        try {
            // Verify OTP and get the record
            $otp = str_pad(trim($otp), 6, '0', STR_PAD_LEFT);
            $otpRecord = DB::table('otp_verifications')
                ->where('email', $email)
                ->where('purpose', 'registration')
                ->orderBy('created_at', 'desc')
                ->first();
            
            if (!$otpRecord) {
                return $this->jsonResponse(false, null, 'No OTP found for this email. Please request a new OTP.', 400);
            }
            
            $storedOtp = str_pad(trim($otpRecord->otp_code), 6, '0', STR_PAD_LEFT);
            
            if ($storedOtp !== $otp) {
                return $this->jsonResponse(false, null, 'Invalid OTP code. Please check and try again.', 400);
            }
            
            if (strtotime($otpRecord->expires_at) < time()) {
                return $this->jsonResponse(false, null, 'OTP has expired. Please request a new OTP.', 400);
            }
            
            // Check if email already exists BEFORE proceeding (early validation)
            if (DB::table('userinfo')->where('email', $email)->exists()) {
                // Clean up the OTP record since email is already registered
                DB::table('otp_verifications')
                    ->where('email', $email)
                    ->where('purpose', 'registration')
                    ->delete();
                return $this->jsonResponse(false, null, 'This email is already registered. Please use the login page instead.', 400);
            }
            
            // Verify OTP if not already verified
            if ($otpRecord->is_verified == 0) {
                DB::table('otp_verifications')
                    ->where('email', $email)
                    ->where('otp_code', $otpRecord->otp_code)
                    ->where('purpose', 'registration')
                    ->update(['is_verified' => 1]);
            }
            
            // Try to get registration data from OTP record if available
            $registrationData = null;
            if (isset($otpRecord->registration_data) && !empty($otpRecord->registration_data)) {
                $decoded = json_decode($otpRecord->registration_data, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $registrationData = $decoded;
                }
            }
            
            // Use registration data from OTP record if available, otherwise use from request
            if ($registrationData && is_array($registrationData)) {
                $fname = $fname ?: ($registrationData['fname'] ?? null);
                $lname = $lname ?: ($registrationData['lname'] ?? null);
                $username = $username ?: ($registrationData['username'] ?? null);
                // Password from stored registration_data is SHA256 hashed (from frontend)
                // Only use it if password from request is not provided
                if (empty($password) && isset($registrationData['password'])) {
                    $password = trim($registrationData['password']);
                }
            }
            
            // If password comes from request, it might also be SHA256 hashed
            // We need to ensure we don't double-hash it
            
            // Validate required fields (after merging stored data)
            $fieldErrors = [];
            if (empty($fname)) {
                $fieldErrors[] = 'First name is required';
            }
            if (empty($lname)) {
                $fieldErrors[] = 'Last name is required';
            }
            if (empty($username)) {
                $fieldErrors[] = 'Username is required';
            }
            if (empty($password)) {
                $fieldErrors[] = 'Password is required';
            }
            
            if (!empty($fieldErrors)) {
                return $this->jsonResponse(false, null, implode(', ', $fieldErrors), 400);
            }
            
            // Hash password ONCE before storing user
            // Password may be:
            // - Plain text (from registration_data stored during sendOtp - frontend sends plain text to sendOtp)
            // - SHA256 hash (from request - frontend hashes it before sending to register)
            // - Already bcrypt (shouldn't happen, but handle gracefully)
            $password = trim($password); // Clean any whitespace
            
            // IMPORTANT: Frontend sends SHA256 during login, so we must store SHA256 (not bcrypt)
            // to ensure login works correctly.
            
            if (self::looksLikeBcrypt($password)) {
                // Already bcrypt hashed, use as-is
                // This shouldn't happen, but handle gracefully
            } elseif (self::looksLikeSha256($password)) {
                // Password is SHA256 hashed from frontend (when calling register directly)
                // Store it as-is (we'll handle SHA256 comparison in login)
                // This maintains compatibility with frontend that sends SHA256
            } else {
                // Plain text password (from registration_data stored during sendOtp)
                // Frontend sends SHA256 during login, so we need to hash it with SHA256 (not bcrypt)
                // to match what login will send
                $password = strtolower(hash('sha256', $password));
            }
            
            // Check if username exists (email already checked earlier)
            if (DB::table('userinfo')->where('username', $username)->exists()) {
                return $this->jsonResponse(false, null, 'Username already exists', 400);
            }
            
            // Double-check email (shouldn't happen due to earlier check, but safety measure)
            if (DB::table('userinfo')->where('email', $email)->exists()) {
                return $this->jsonResponse(false, null, 'This email is already registered. Please use the login page instead.', 400);
            }
            
            // Insert user (ensure password is trimmed to avoid any whitespace issues)
            DB::table('userinfo')->insert([
                'fname' => trim($fname),
                'lname' => trim($lname),
                'username' => trim($username),
                'email' => trim($email),
                'password' => trim($password), // Ensure no whitespace in stored hash
                'usertype' => 'Client',
                'image' => 'default.jpg'
            ]);
            
            // Delete used OTP
            DB::table('otp_verifications')
                ->where('email', $email)
                ->where('purpose', 'registration')
                ->delete();
            
            return $this->jsonResponse(true, null, 'Account created successfully', 201);
        } catch (\Exception $e) {
            return $this->jsonResponse(false, null, 'Error: ' . $e->getMessage(), 500);
        }
    }
    
    public function sendOtp(Request $request)
    {
        // Manual validation to ensure JSON response
        $email = $request->input('email');
        $purpose = $request->input('purpose');
        
        $errors = [];
        if (empty($email)) {
            $errors[] = 'Email is required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        }
        if (empty($purpose)) {
            $errors[] = 'Purpose is required';
        } elseif (!in_array($purpose, ['registration', 'password_reset'])) {
            $errors[] = 'Invalid purpose. Must be registration or password_reset';
        }
        
        if (!empty($errors)) {
            return $this->jsonResponse(false, null, implode(', ', $errors), 400);
        }
        
        try {
            // For registration, check if email already exists
            if ($purpose === 'registration') {
                if (DB::table('userinfo')->where('email', $email)->exists()) {
                    return $this->jsonResponse(false, null, 'This email is already registered. Please use the login page instead.', 400);
                }
                
                // Validate and store registration data if provided
                $fname = $request->input('fname');
                $lname = $request->input('lname');
                $username = $request->input('username');
                $password = $request->input('password');
                
                $registrationErrors = [];
                if (empty($fname)) {
                    $registrationErrors[] = 'First name is required';
                }
                if (empty($lname)) {
                    $registrationErrors[] = 'Last name is required';
                }
                if (empty($username)) {
                    $registrationErrors[] = 'Username is required';
                } elseif (DB::table('userinfo')->where('username', $username)->exists()) {
                    $registrationErrors[] = 'Username already exists';
                }
                if (empty($password)) {
                    $registrationErrors[] = 'Password is required';
                } elseif (strlen($password) < 4) {
                    $registrationErrors[] = 'Password must be at least 4 characters';
                }
                
                if (!empty($registrationErrors)) {
                    return $this->jsonResponse(false, null, implode(', ', $registrationErrors), 400);
                }
                
                // Store registration data (password kept plain here; will be hashed at register time)
                $registrationData = json_encode([
                    'fname' => $fname,
                    'lname' => $lname,
                    'username' => $username,
                    'password' => $password
                ]);
            } else {
                // For password reset, check if email exists
                if (!DB::table('userinfo')->where('email', $email)->exists()) {
                    // Don't reveal if email exists for security
                    return $this->jsonResponse(true, null, 'If the email exists, an OTP has been sent');
                }
                $registrationData = null;
            }
            
            // Delete old unverified OTPs
            DB::table('otp_verifications')
                ->where('email', $email)
                ->where('purpose', $purpose)
                ->where('is_verified', 0)
                ->delete();
            
            // Generate OTP
            $otp = $this->generateOTP();
            $expiresAt = now()->addMinutes(10);
            
            // Insert OTP with registration data if available
            $insertData = [
                'email' => $email,
                'otp_code' => $otp,
                'purpose' => $purpose,
                'expires_at' => $expiresAt,
                'is_verified' => 0,
                'created_at' => now()
            ];
            
            // Add registration_data if available (column must exist in database)
            if ($registrationData !== null) {
                $insertData['registration_data'] = $registrationData;
            }
            
            try {
                DB::table('otp_verifications')->insert($insertData);
            } catch (\Exception $dbException) {
                // If column doesn't exist, insert without registration_data
                if (strpos($dbException->getMessage(), 'registration_data') !== false || 
                    strpos($dbException->getMessage(), 'Unknown column') !== false) {
                    unset($insertData['registration_data']);
                    DB::table('otp_verifications')->insert($insertData);
                    Log::warning('registration_data column not found in otp_verifications table. Please run migration.');
                } else {
                    throw $dbException;
                }
            }
            
            // Send email
            try {
                Mail::to($email)->send(new OtpMail($otp, $purpose, $email));
                return $this->jsonResponse(true, null, 'OTP sent to your email. Please check your inbox.');
            } catch (\Exception $mailException) {
                // Log the error but still return success (OTP is saved in DB)
                // In production, you might want to handle this differently
                Log::error('Failed to send OTP email: ' . $mailException->getMessage());
                // For development, return OTP in response if email fails
                return $this->jsonResponse(true, ['otp' => $otp, 'email_error' => 'Email sending failed, but OTP is available'], 'OTP generated. Email sending failed, but you can use the OTP code provided.');
            }
        } catch (\Exception $e) {
            return $this->jsonResponse(false, null, 'Error: ' . $e->getMessage(), 500);
        }
    }
    
    public function verifyOtp(Request $request)
    {
        // Manual validation to ensure JSON response
        $email = trim($request->input('email'));
        $otp = trim($request->input('otp'));
        $purpose = trim($request->input('purpose'));
        
        $errors = [];
        if (empty($email)) {
            $errors[] = 'Email is required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        }
        if (empty($otp)) {
            $errors[] = 'OTP is required';
        } elseif (!preg_match('/^\d{6}$/', $otp)) {
            $errors[] = 'OTP must be 6 digits';
        }
        if (empty($purpose) || ($purpose !== 'registration' && $purpose !== 'password_reset')) {
            $errors[] = 'Invalid purpose. Must be registration or password_reset';
        }
        
        if (!empty($errors)) {
            return $this->jsonResponse(false, null, implode(', ', $errors), 400);
        }
        
        try {
            $otp = str_pad($otp, 6, '0', STR_PAD_LEFT);
            
            $otpRecord = DB::table('otp_verifications')
                ->where('email', $email)
                ->where('purpose', $purpose)
                ->orderBy('created_at', 'desc')
                ->first();
            
            if (!$otpRecord) {
                return $this->jsonResponse(false, null, 'No OTP found for this email. Please request a new OTP.', 400);
            }
            
            $storedOtp = str_pad(trim($otpRecord->otp_code), 6, '0', STR_PAD_LEFT);
            
            if ($storedOtp !== $otp) {
                return $this->jsonResponse(false, null, 'Invalid OTP code. Please check and try again.', 400);
            }
            
            if ($otpRecord->is_verified == 1) {
                return $this->jsonResponse(false, null, 'This OTP has already been used. Please request a new OTP.', 400);
            }
            
            if (strtotime($otpRecord->expires_at) < time()) {
                return $this->jsonResponse(false, null, 'OTP has expired. Please request a new OTP.', 400);
            }
            
            // Mark OTP as verified
            DB::table('otp_verifications')
                ->where('email', $email)
                ->where('otp_code', $otpRecord->otp_code)
                ->where('purpose', $purpose)
                ->update(['is_verified' => 1]);
            
            return $this->jsonResponse(true, null, 'OTP verified successfully');
        } catch (\Exception $e) {
            return $this->jsonResponse(false, null, 'Error: ' . $e->getMessage(), 500);
        }
    }
    
    public function forgotPassword(Request $request)
    {
        // Manual validation to ensure JSON response
        $email = $request->input('email');
        
        $errors = [];
        if (empty($email)) {
            $errors[] = 'Email is required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        }
        
        if (!empty($errors)) {
            return $this->jsonResponse(false, null, implode(', ', $errors), 400);
        }
        
        try {
            // Check if email exists
            if (!DB::table('userinfo')->where('email', $email)->exists()) {
                // Don't reveal if email exists for security
                return $this->jsonResponse(true, null, 'If the email exists, an OTP has been sent');
            }
            
            // Delete old unverified OTPs
            DB::table('otp_verifications')
                ->where('email', $email)
                ->where('purpose', 'password_reset')
                ->where('is_verified', 0)
                ->delete();
            
            // Generate OTP
            $otp = $this->generateOTP();
            $expiresAt = now()->addMinutes(10);
            
            // Insert OTP
            DB::table('otp_verifications')->insert([
                'email' => $email,
                'otp_code' => $otp,
                'purpose' => 'password_reset',
                'expires_at' => $expiresAt,
                'is_verified' => 0,
                'created_at' => now()
            ]);
            
            // Send email
            try {
                Mail::to($email)->send(new OtpMail($otp, 'password_reset', $email));
                return $this->jsonResponse(true, null, 'If the email exists, an OTP has been sent. Please check your inbox.');
            } catch (\Exception $mailException) {
                // Log the error but still return success (OTP is saved in DB)
                Log::error('Failed to send password reset OTP email: ' . $mailException->getMessage());
                // For development, return OTP in response if email fails
                return $this->jsonResponse(true, ['otp' => $otp, 'email_error' => 'Email sending failed, but OTP is available'], 'If the email exists, an OTP has been generated. Email sending failed, but you can use the OTP code provided.');
            }
        } catch (\Exception $e) {
            return $this->jsonResponse(false, null, 'Error: ' . $e->getMessage(), 500);
        }
    }
    
    public function resetPassword(Request $request)
    {
        // Manual validation to ensure JSON response
        $email = trim($request->input('email'));
        $otp = trim($request->input('otp'));
        $newPassword = $request->input('new_password');
        
        $errors = [];
        if (empty($email)) {
            $errors[] = 'Email is required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        }
        if (empty($otp)) {
            $errors[] = 'OTP is required';
        } elseif (!preg_match('/^\d{6}$/', $otp)) {
            $errors[] = 'OTP must be 6 digits';
        }
        if (empty($newPassword)) {
            $errors[] = 'New password is required';
        } elseif (strlen($newPassword) < 4) {
            $errors[] = 'Password must be at least 4 characters';
        }
        
        if (!empty($errors)) {
            return $this->jsonResponse(false, null, implode(', ', $errors), 400);
        }
        
        try {
            $otp = str_pad($otp, 6, '0', STR_PAD_LEFT);
            
            // Check OTP
            $otpRecord = DB::table('otp_verifications')
                ->where('email', $email)
                ->where('purpose', 'password_reset')
                ->orderBy('created_at', 'desc')
                ->first();
            
            if (!$otpRecord) {
                return $this->jsonResponse(false, null, 'No OTP found for this email. Please request a new OTP.', 400);
            }
            
            $storedOtp = str_pad(trim($otpRecord->otp_code), 6, '0', STR_PAD_LEFT);
            
            if ($storedOtp !== $otp) {
                return $this->jsonResponse(false, null, 'Invalid OTP code. Please check and try again.', 400);
            }
            
            if ($otpRecord->is_verified != 1) {
                return $this->jsonResponse(false, null, 'OTP not verified yet. Please verify your OTP first.', 400);
            }
            
            if (strtotime($otpRecord->expires_at) < time()) {
                return $this->jsonResponse(false, null, 'OTP has expired. Please request a new OTP.', 400);
            }
            
            // Update password (always hash with bcrypt)
            DB::table('userinfo')
                ->where('email', $email)
                ->update(['password' => Hash::make($newPassword)]);
            
            // Delete used OTP
            DB::table('otp_verifications')
                ->where('email', $email)
                ->where('purpose', 'password_reset')
                ->delete();
            
            return $this->jsonResponse(true, null, 'Password reset successfully');
        } catch (\Exception $e) {
            return $this->jsonResponse(false, null, 'Error: ' . $e->getMessage(), 500);
        }
    }
}

