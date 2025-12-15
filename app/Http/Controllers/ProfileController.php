<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    private function getUserFromToken(Request $request)
    {
        $token = $request->bearerToken();
        if (!$token) {
            return null;
        }
        
        return DB::table('tokens as t')
            ->join('userinfo as u', 't.userid', '=', 'u.userid')
            ->where('t.token', $token)
            ->where('t.expires_at', '>', now())
            ->select('u.*', 't.userid')
            ->first();
    }
    
    public function get(Request $request)
    {
        $user = $this->getUserFromToken($request);
        
        if (!$user) {
            return $this->jsonResponse(false, null, 'Unauthorized', 401);
        }
        
        unset($user->password);
        unset($user->token);
        
        return $this->jsonResponse(true, ['user' => $user]);
    }
    
    public function update(Request $request)
    {
        $user = $this->getUserFromToken($request);
        
        if (!$user) {
            return $this->jsonResponse(false, null, 'Unauthorized', 401);
        }
        
        $request->validate([
            'fname' => 'sometimes|required',
            'lname' => 'sometimes|required',
            'username' => 'sometimes|required',
            'email' => 'sometimes|required|email',
            'contact' => 'sometimes',
            'gender' => 'sometimes'
        ]);
        
        try {
            $updateData = [];
            if ($request->has('fname')) $updateData['fname'] = $request->input('fname');
            if ($request->has('lname')) $updateData['lname'] = $request->input('lname');
            if ($request->has('username')) $updateData['username'] = $request->input('username');
            if ($request->has('email')) $updateData['email'] = $request->input('email');
            if ($request->has('contact')) $updateData['contact'] = $request->input('contact');
            if ($request->has('gender')) $updateData['gender'] = $request->input('gender');
            
            DB::table('userinfo')
                ->where('userid', $user->userid)
                ->update($updateData);
            
            return $this->jsonResponse(true, null, 'Profile updated successfully');
        } catch (\Exception $e) {
            return $this->jsonResponse(false, null, 'Error: ' . $e->getMessage(), 500);
        }
    }
    
    public function updateImage(Request $request)
    {
        $user = $this->getUserFromToken($request);
        
        if (!$user) {
            return $this->jsonResponse(false, null, 'Unauthorized', 401);
        }
        
        $request->validate([
            'profile_img' => 'required|image|mimes:jpeg,jpg,png,gif|max:2048'
        ]);
        
        try {
            // Handle file upload
            if ($request->hasFile('profile_img')) {
                $file = $request->file('profile_img');
                
                // Generate unique filename
                $extension = $file->getClientOriginalExtension();
                $filename = uniqid() . '.' . $extension;
                $path = public_path('profile_img');
                
                // Ensure directory exists
                if (!is_dir($path)) {
                    mkdir($path, 0755, true);
                }
                
                // Move uploaded file
                $file->move($path, $filename);
                
                // Delete old image if exists
                if ($user->image && $user->image !== 'default.jpg' && file_exists(public_path('profile_img/' . $user->image))) {
                    unlink(public_path('profile_img/' . $user->image));
                }
                
                // Update database
                DB::table('userinfo')
                    ->where('userid', $user->userid)
                    ->update(['image' => $filename]);
                
                return $this->jsonResponse(true, ['image' => $filename], 'Image updated successfully');
            } else {
                return $this->jsonResponse(false, null, 'No image file provided', 400);
            }
        } catch (\Exception $e) {
            return $this->jsonResponse(false, null, 'Error: ' . $e->getMessage(), 500);
        }
    }
    
    public function updatePassword(Request $request)
    {
        $user = $this->getUserFromToken($request);
        
        if (!$user) {
            return $this->jsonResponse(false, null, 'Unauthorized', 401);
        }
        
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:4'
        ]);
        
        try {
            // Verify current password
            $userRecord = DB::table('userinfo')
                ->where('userid', $user->userid)
                ->first();
            
            if (!$userRecord) {
                return $this->jsonResponse(false, null, 'User not found', 404);
            }
            
            $currentPassword = trim($request->input('current_password'));
            $storedPassword = trim($userRecord->password);
            $passwordMatches = false;
            
            if ($storedPassword === $currentPassword) {
                $passwordMatches = true;
            } else {
                $storedPasswordHash = strtolower(hash('sha256', $storedPassword));
                $currentPasswordLower = strtolower($currentPassword);
                if ($storedPasswordHash === $currentPasswordLower) {
                    $passwordMatches = true;
                }
            }
            
            if (!$passwordMatches) {
                return $this->jsonResponse(false, null, 'Current password is incorrect', 400);
            }
            
            // Update password
            DB::table('userinfo')
                ->where('userid', $user->userid)
                ->update(['password' => $request->input('new_password')]);
            
            return $this->jsonResponse(true, null, 'Password updated successfully');
        } catch (\Exception $e) {
            return $this->jsonResponse(false, null, 'Error: ' . $e->getMessage(), 500);
        }
    }
}

