<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminController extends Controller
{
    private function getUserFromToken(Request $request)
    {
        $token = $request->bearerToken();
        if (!$token) {
            return null;
        }
        
        $user = DB::table('tokens as t')
            ->join('userinfo as u', 't.userid', '=', 'u.userid')
            ->where('t.token', $token)
            ->where('t.expires_at', '>', now())
            ->select('u.*')
            ->first();
        
        // Check if user is Admin or Incharge
        if ($user && ($user->usertype === 'Admin' || $user->usertype === 'Incharge')) {
            return $user;
        }
        
        return null;
    }
    
    public function dashboard(Request $request)
    {
        $user = $this->getUserFromToken($request);
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }
        
        try {
            $search = $request->get('search', '');
            $limit = $request->get('limit', 10);
            $page = $request->get('page', 1);
            $offset = ($page - 1) * $limit;
            
            // Get stats
            $totalRooms = DB::table('rooms')->count();
            $totalClients = DB::table('userinfo')->where('usertype', 'Client')->count();
            $bookedRooms = DB::table('booking_table')->where('status', 'Approved')->count();
            $availableRooms = DB::table('rooms')->whereRaw("LOWER(status) = 'available'")->count();
            
            $stats = [
                'totalRooms' => (int)$totalRooms,
                'totalClients' => (int)$totalClients,
                'bookedRooms' => (int)$bookedRooms,
                'availableRooms' => (int)$availableRooms
            ];
            
            // Get recent bookings
            $bookingsQuery = DB::table('booking_table as b')
                ->join('userinfo as u', 'b.userid', '=', 'u.userid')
                ->join('rooms as r', 'b.room_id', '=', 'r.room_id')
                ->join('room_type as rt', 'r.category_id', '=', 'rt.category_id')
                ->select(
                    'b.*',
                    'u.fname',
                    'u.lname',
                    'u.email',
                    'r.room_id',
                    'rt.category_name',
                    'rt.price',
                    DB::raw('COALESCE((b.no_of_days * rt.price), 0) as total_price')
                );
            
            if (!empty($search)) {
                $bookingsQuery->where(function($q) use ($search) {
                    $q->where('u.fname', 'LIKE', "%{$search}%")
                      ->orWhere('u.lname', 'LIKE', "%{$search}%")
                      ->orWhere('rt.category_name', 'LIKE', "%{$search}%")
                      ->orWhere('r.room_id', 'LIKE', "%{$search}%");
                });
            }
            
            $bookings = $bookingsQuery
                ->orderByRaw("FIELD(b.status, 'Pending', 'Approved', 'Reject', 'Cancelled')")
                ->orderBy('b.datetime', 'desc')
                ->offset($offset)
                ->limit($limit)
                ->get();
            
            // Return in the format expected by frontend
            return response()->json([
                'success' => true,
                'stats' => $stats,
                'bookings' => $bookings->toArray()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function bookings(Request $request)
    {
        $user = $this->getUserFromToken($request);
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }
        
        try {
            $search = $request->get('search', '');
            $status = $request->get('status', '');
            $limit = $request->get('limit', 10);
            $page = $request->get('page', 1);
            $offset = ($page - 1) * $limit;
            
            $bookingsQuery = DB::table('booking_table as b')
                ->join('userinfo as u', 'b.userid', '=', 'u.userid')
                ->join('rooms as r', 'b.room_id', '=', 'r.room_id')
                ->join('room_type as rt', 'r.category_id', '=', 'rt.category_id')
                ->select(
                    'b.*',
                    'u.fname',
                    'u.lname',
                    'u.email',
                    'r.room_id',
                    'rt.category_name',
                    'rt.price',
                    DB::raw('COALESCE((b.no_of_days * rt.price), 0) as total_price')
                );
            
            if (!empty($search)) {
                $bookingsQuery->where(function($q) use ($search) {
                    $q->where('u.fname', 'LIKE', "%{$search}%")
                      ->orWhere('u.lname', 'LIKE', "%{$search}%")
                      ->orWhere('rt.category_name', 'LIKE', "%{$search}%")
                      ->orWhere('r.room_id', 'LIKE', "%{$search}%");
                });
            }
            
            if (!empty($status)) {
                $bookingsQuery->where('b.status', $status);
            }
            
            $total = $bookingsQuery->count();
            $bookings = $bookingsQuery
                ->orderByRaw("FIELD(b.status, 'Pending', 'Approved', 'Reject', 'Cancelled')")
                ->orderBy('b.datetime', 'desc')
                ->offset($offset)
                ->limit($limit)
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $bookings->toArray(),
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => ceil($total / $limit),
                    'total_items' => $total,
                    'items_per_page' => $limit
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function rooms(Request $request)
    {
        $user = $this->getUserFromToken($request);
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }
        
        try {
            $search = $request->get('search', '');
            $categoryId = $request->get('category_id', '');
            $status = $request->get('status', '');
            $limit = $request->get('limit', 10);
            $page = $request->get('page', 1);
            $offset = ($page - 1) * $limit;
            
            // Use LEFT JOIN to ensure all rooms are included even if category is missing
            // (though all rooms should have categories based on schema)
            $roomsQuery = DB::table('rooms as r')
                ->leftJoin('room_type as rt', 'r.category_id', '=', 'rt.category_id')
                ->select('r.*', 'rt.category_name', 'rt.price', 'rt.description', 'rt.image');
            
            if (!empty($search)) {
                $roomsQuery->where(function($q) use ($search) {
                    $q->where('r.room_id', 'LIKE', "%{$search}%")
                      ->orWhere('rt.category_name', 'LIKE', "%{$search}%");
                });
            }
            
            // Filter by category_id if provided (and not empty string or zero)
            // Convert to integer to handle both string and integer inputs
            if (!empty($categoryId) && $categoryId !== '' && $categoryId !== '0') {
                $categoryIdInt = (int)$categoryId;
                if ($categoryIdInt > 0) {
                    $roomsQuery->where('r.category_id', $categoryIdInt);
                }
            }
            
            // Filter by status if provided (and not empty string)
            if (!empty($status) && $status !== '') {
                $roomsQuery->whereRaw("LOWER(r.status) = ?", [strtolower($status)]);
            }
            
            $total = $roomsQuery->count();
            $rooms = $roomsQuery
                ->orderBy('r.room_id')
                ->offset($offset)
                ->limit($limit)
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $rooms->toArray(),
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => ceil($total / $limit),
                    'total_items' => $total,
                    'items_per_page' => $limit
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function users(Request $request)
    {
        $user = $this->getUserFromToken($request);
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }
        
        // Only Admin can access all users
        if ($user->usertype !== 'Admin') {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Admin access required.'
            ], 403);
        }
        
        try {
            $search = $request->get('search', '');
            $limit = $request->get('limit', 10);
            $page = $request->get('page', 1);
            $offset = ($page - 1) * $limit;
            
            $usersQuery = DB::table('userinfo')
                ->select('userid', 'fname', 'midname', 'lname', 'username', 'email', 'usertype', 'contact', 'gender', 'image');
            
            if (!empty($search)) {
                $usersQuery->where(function($q) use ($search) {
                    $q->where('fname', 'LIKE', "%{$search}%")
                      ->orWhere('lname', 'LIKE', "%{$search}%")
                      ->orWhere('email', 'LIKE', "%{$search}%")
                      ->orWhere('username', 'LIKE', "%{$search}%");
                });
            }
            
            $total = $usersQuery->count();
            $users = $usersQuery
                ->orderBy('userid', 'desc')
                ->offset($offset)
                ->limit($limit)
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $users->toArray(),
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => ceil($total / $limit),
                    'total_items' => $total,
                    'items_per_page' => $limit
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get client users (for booking purposes - accessible by both Admin and Incharge)
     */
    public function getClients(Request $request)
    {
        $user = $this->getUserFromToken($request);
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }
        
        // Admin and Incharge can access client users for booking
        if ($user->usertype !== 'Admin' && $user->usertype !== 'Incharge') {
            return response()->json([
                'success' => false,
                'message' => 'Access denied.'
            ], 403);
        }
        
        try {
            $search = $request->get('search', '');
            $limit = $request->get('limit', 1000);
            $page = $request->get('page', 1);
            $offset = ($page - 1) * $limit;
            
            $usersQuery = DB::table('userinfo')
                ->where('usertype', 'Client')
                ->select('userid', 'fname', 'midname', 'lname', 'username', 'email', 'usertype', 'contact', 'gender', 'image');
            
            if (!empty($search)) {
                $usersQuery->where(function($q) use ($search) {
                    $q->where('fname', 'LIKE', "%{$search}%")
                      ->orWhere('lname', 'LIKE', "%{$search}%")
                      ->orWhere('email', 'LIKE', "%{$search}%")
                      ->orWhere('username', 'LIKE', "%{$search}%");
                });
            }
            
            $total = $usersQuery->count();
            $users = $usersQuery
                ->orderBy('userid', 'desc')
                ->offset($offset)
                ->limit($limit)
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $users->toArray(),
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => ceil($total / $limit),
                    'total_items' => $total,
                    'items_per_page' => $limit
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function roomCategories(Request $request)
    {
        $user = $this->getUserFromToken($request);
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }
        
        // Admin and Incharge can read room categories (needed for room management and bookings)
        // Write operations (create/update/delete) are restricted to Admin only
        if ($user->usertype !== 'Admin' && $user->usertype !== 'Incharge') {
            return response()->json([
                'success' => false,
                'message' => 'Access denied.'
            ], 403);
        }
        
        try {
            $search = $request->get('search', '');
            $limit = $request->get('limit', 10);
            $page = $request->get('page', 1);
            $offset = ($page - 1) * $limit;
            
            $categoriesQuery = DB::table('room_type');
            
            if (!empty($search)) {
                $categoriesQuery->where(function($q) use ($search) {
                    $q->where('category_name', 'LIKE', "%{$search}%")
                      ->orWhere('description', 'LIKE', "%{$search}%");
                });
            }
            
            $total = $categoriesQuery->count();
            $categories = $categoriesQuery
                ->orderBy('category_id')
                ->offset($offset)
                ->limit($limit)
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $categories->toArray(),
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => ceil($total / $limit),
                    'total_items' => $total,
                    'items_per_page' => $limit
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function createUser(Request $request)
    {
        $user = $this->getUserFromToken($request);
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }
        
        // Only Admin can create users
        if ($user->usertype !== 'Admin') {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Admin access required.'
            ], 403);
        }
        
        $request->validate([
            'fname' => 'required',
            'lname' => 'required',
            'username' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:4',
            'usertype' => 'sometimes|in:Admin,Incharge,Client'
        ]);
        
        try {
            // Check if username exists
            if (DB::table('userinfo')->where('username', $request->input('username'))->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Username already exists'
                ], 400);
            }
            
            // Check if email exists
            if (DB::table('userinfo')->where('email', $request->input('email'))->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email already exists'
                ], 400);
            }
            
            // Insert user
            DB::table('userinfo')->insert([
                'fname' => $request->input('fname'),
                'midname' => $request->input('midname', ''),
                'lname' => $request->input('lname'),
                'username' => $request->input('username'),
                'email' => $request->input('email'),
                'password' => $request->input('password'),
                'contact' => $request->input('contact', ''),
                'gender' => $request->input('gender', ''),
                'usertype' => $request->input('usertype', 'Client'),
                'image' => 'default.jpg'
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'User created successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function updateUser(Request $request, $userid)
    {
        $user = $this->getUserFromToken($request);
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }
        
        // Only Admin can update users
        if ($user->usertype !== 'Admin') {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Admin access required.'
            ], 403);
        }
        
        $request->validate([
            'fname' => 'sometimes|required',
            'lname' => 'sometimes|required',
            'username' => 'sometimes|required',
            'email' => 'sometimes|required|email',
            'usertype' => 'sometimes|in:Admin,Incharge,Client'
        ]);
        
        try {
            // Check if user exists
            $existingUser = DB::table('userinfo')->where('userid', $userid)->first();
            if (!$existingUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }
            
            // If changing usertype from Admin to non-Admin, check admin count
            if ($request->has('usertype') && $existingUser->usertype === 'Admin' && $request->input('usertype') !== 'Admin') {
                $adminCount = DB::table('userinfo')
                    ->where('usertype', 'Admin')
                    ->where('userid', '!=', $userid)
                    ->count();
                
                if ($adminCount < 1) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cannot change user type. At least one admin must remain in the system.'
                    ], 400);
                }
            }
            
            // Check if username exists (excluding current user)
            if ($request->has('username')) {
                if (DB::table('userinfo')->where('username', $request->input('username'))->where('userid', '!=', $userid)->exists()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Username already exists'
                    ], 400);
                }
            }
            
            // Check if email exists (excluding current user)
            if ($request->has('email')) {
                if (DB::table('userinfo')->where('email', $request->input('email'))->where('userid', '!=', $userid)->exists()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Email already exists'
                    ], 400);
                }
            }
            
            // Build update data
            $updateData = [];
            if ($request->has('fname')) $updateData['fname'] = $request->input('fname');
            if ($request->has('midname')) $updateData['midname'] = $request->input('midname');
            if ($request->has('lname')) $updateData['lname'] = $request->input('lname');
            if ($request->has('username')) $updateData['username'] = $request->input('username');
            if ($request->has('email')) $updateData['email'] = $request->input('email');
            if ($request->has('contact')) $updateData['contact'] = $request->input('contact');
            if ($request->has('gender')) $updateData['gender'] = $request->input('gender');
            if ($request->has('usertype')) $updateData['usertype'] = $request->input('usertype');
            if ($request->has('resetpassword') && !empty($request->input('resetpassword'))) {
                $updateData['password'] = $request->input('resetpassword');
            }
            
            // Update user
            DB::table('userinfo')
                ->where('userid', $userid)
                ->update($updateData);
            
            return response()->json([
                'success' => true,
                'message' => 'User updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function deleteUser(Request $request, $userid)
    {
        $user = $this->getUserFromToken($request);
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }
        
        // Only Admin can delete users
        if ($user->usertype !== 'Admin') {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Admin access required.'
            ], 403);
        }
        
        try {
            // Check if user exists
            $userToDelete = DB::table('userinfo')->where('userid', $userid)->first();
            if (!$userToDelete) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }
            
            // If deleting an Admin, check if there's at least one other Admin
            if ($userToDelete->usertype === 'Admin') {
                $adminCount = DB::table('userinfo')
                    ->where('usertype', 'Admin')
                    ->where('userid', '!=', $userid)
                    ->count();
                
                if ($adminCount < 1) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cannot delete the last admin. At least one admin must remain in the system.'
                    ], 400);
                }
            }
            
            // Delete user
            DB::table('userinfo')->where('userid', $userid)->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function createBooking(Request $request)
    {
        $user = $this->getUserFromToken($request);
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401)->header('Content-Type', 'application/json');
        }
        
        // Manual validation to ensure JSON response
        $userid = $request->input('userid');
        $room_id = $request->input('room_id');
        $check_in = $request->input('check_in');
        $check_out = $request->input('check_out');
        $no_of_days = $request->input('no_of_days');
        $contact = $request->input('contact', '');
        $status = $request->input('status', 'Approved');
        
        $errors = [];
        
        if (empty($userid) || !is_numeric($userid)) {
            $errors[] = 'User ID is required and must be a number';
        }
        
        if (empty($room_id) || !is_numeric($room_id)) {
            $errors[] = 'Room ID is required and must be a number';
        }
        
        if (empty($check_in)) {
            $errors[] = 'Check-in date is required';
        }
        
        if (empty($check_out)) {
            $errors[] = 'Check-out date is required';
        }
        
        if (!empty($errors)) {
            return response()->json([
                'success' => false,
                'message' => implode(', ', $errors)
            ], 400)->header('Content-Type', 'application/json');
        }
        
        // Validate dates
        $checkinDate = strtotime($check_in);
        $checkoutDate = strtotime($check_out);
        
        if ($checkinDate === false || $checkoutDate === false) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid date format'
            ], 400)->header('Content-Type', 'application/json');
        }
        
        if ($checkoutDate <= $checkinDate) {
            return response()->json([
                'success' => false,
                'message' => 'Check-out date must be after check-in date'
            ], 400)->header('Content-Type', 'application/json');
        }
        
        // Calculate number of days if not provided or invalid
        if (empty($no_of_days) || !is_numeric($no_of_days) || $no_of_days <= 0) {
            try {
                $checkinDateTime = new \DateTime($check_in);
                $checkoutDateTime = new \DateTime($check_out);
                $interval = $checkinDateTime->diff($checkoutDateTime);
                $no_of_days = $interval->days;
                
                if ($no_of_days <= 0) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Number of days must be greater than 0'
                    ], 400)->header('Content-Type', 'application/json');
                }
            } catch (\Exception $dateError) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid date format: ' . $dateError->getMessage()
                ], 400)->header('Content-Type', 'application/json');
            }
        }
        
        try {
            // Check if user exists and get contact if not provided
            $userInfo = DB::table('userinfo')->where('userid', $userid)->first();
            if (!$userInfo) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404)->header('Content-Type', 'application/json');
            }
            
            // Use user's contact from database if not provided
            if (empty($contact)) {
                $contact = $userInfo->contact ?? '';
            }
            
            // Check if room exists
            $roomExists = DB::table('rooms')->where('room_id', $room_id)->exists();
            if (!$roomExists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Room not found'
                ], 404)->header('Content-Type', 'application/json');
            }
            
            // Check for booking conflicts (only check non-cancelled/rejected bookings)
            // A room is unavailable if:
            // 1. New check-in date falls within an existing booking (excluding checkout date - same day checkout/checkin is allowed)
            // 2. New check-out date falls within an existing booking (excluding checkin date - same day checkout/checkin is allowed)
            // 3. New booking completely encompasses an existing booking
            $conflicting = DB::table('booking_table')
                ->where('room_id', $room_id)
                ->where('status', '!=', 'Cancelled')
                ->where('status', '!=', 'Reject')
                ->where(function($query) use ($check_in, $check_out) {
                    // Check if new check-in falls within existing booking (but allow same day as existing checkout)
                    $query->where(function($q) use ($check_in, $check_out) {
                        $q->where('check_in', '<', $check_in)
                          ->where('check_out', '>', $check_in);
                    })
                    // Check if new check-out falls within existing booking (but allow same day as existing checkin)
                    ->orWhere(function($q) use ($check_in, $check_out) {
                        $q->where('check_in', '<', $check_out)
                          ->where('check_out', '>', $check_out);
                    })
                    // Check if new booking completely encompasses an existing booking
                    ->orWhere(function($q) use ($check_in, $check_out) {
                        $q->where('check_in', '>=', $check_in)
                          ->where('check_out', '<=', $check_out);
                    });
                })
                ->exists();
            
            if ($conflicting) {
                return response()->json([
                    'success' => false,
                    'message' => 'Room is not available for the selected dates. There is an existing booking that overlaps with these dates.'
                ], 400)->header('Content-Type', 'application/json');
            }
            
            // Create booking
            $bookingId = DB::table('booking_table')->insertGetId([
                'userid' => $userid,
                'room_id' => $room_id,
                'contact' => $contact,
                'check_in' => $check_in,
                'check_out' => $check_out,
                'no_of_days' => $no_of_days,
                'status' => $status,
                'datetime' => now()
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Booking created successfully',
                'booking_id' => $bookingId
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating booking: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500)->header('Content-Type', 'application/json');
        }
    }
    
    public function updateBookingStatus(Request $request, $book_id)
    {
        $user = $this->getUserFromToken($request);
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }
        
        $request->validate([
            'status' => 'required|in:Pending,Approved,Reject,Cancelled,checked_in'
        ]);
        
        try {
            // Check if booking exists
            $booking = DB::table('booking_table')->where('book_id', $book_id)->first();
            if (!$booking) {
                return response()->json([
                    'success' => false,
                    'message' => 'Booking not found'
                ], 404);
            }
            
            // Update booking status
            DB::table('booking_table')
                ->where('book_id', $book_id)
                ->update(['status' => $request->input('status')]);
            
            return response()->json([
                'success' => true,
                'message' => 'Booking status updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function getNextRoomId(Request $request)
    {
        $user = $this->getUserFromToken($request);
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }
        
        try {
            // Get the maximum room_id from the database
            $maxRoomId = DB::table('rooms')->max('room_id');
            
            // If no rooms exist, start from 1, otherwise increment by 1
            $nextRoomId = $maxRoomId ? ($maxRoomId + 1) : 1;
            
            return response()->json([
                'success' => true,
                'next_room_id' => $nextRoomId
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function createRoom(Request $request)
    {
        $user = $this->getUserFromToken($request);
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }
        
        $request->validate([
            'room_id' => 'required',
            'category_id' => 'required|integer|exists:room_type,category_id',
            'status' => 'sometimes|in:Available,available,Occupied,occupied,Maintenance,maintenance'
        ]);
        
        try {
            // Check if room_id already exists
            if (DB::table('rooms')->where('room_id', $request->input('room_id'))->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Room ID already exists'
                ], 400);
            }
            
            // Insert room
            DB::table('rooms')->insert([
                'room_id' => $request->input('room_id'),
                'category_id' => $request->input('category_id'),
                'status' => $request->input('status', 'Available')
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Room created successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function updateRoom(Request $request, $room_id)
    {
        $user = $this->getUserFromToken($request);
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }
        
        $request->validate([
            'room_id' => 'sometimes|required',
            'category_id' => 'sometimes|required|integer|exists:room_type,category_id',
            'status' => 'sometimes|in:Available,available,Occupied,occupied,Maintenance,maintenance'
        ]);
        
        try {
            // Check if original room exists
            $existingRoom = DB::table('rooms')->where('room_id', $room_id)->first();
            if (!$existingRoom) {
                return response()->json([
                    'success' => false,
                    'message' => 'Room not found'
                ], 404);
            }
            
            // If room_id is being changed, check if new room_id exists
            if ($request->has('room_id') && $request->input('room_id') !== $room_id) {
                if (DB::table('rooms')->where('room_id', $request->input('room_id'))->exists()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'New Room ID already exists'
                    ], 400);
                }
            }
            
            // Build update data
            $updateData = [];
            if ($request->has('room_id')) $updateData['room_id'] = $request->input('room_id');
            if ($request->has('category_id')) $updateData['category_id'] = $request->input('category_id');
            if ($request->has('status')) $updateData['status'] = $request->input('status');
            
            // Update room
            DB::table('rooms')
                ->where('room_id', $room_id)
                ->update($updateData);
            
            return response()->json([
                'success' => true,
                'message' => 'Room updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function deleteRoom(Request $request, $room_id)
    {
        $user = $this->getUserFromToken($request);
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }
        
        try {
            // Check if room exists
            $room = DB::table('rooms')->where('room_id', $room_id)->first();
            if (!$room) {
                return response()->json([
                    'success' => false,
                    'message' => 'Room not found'
                ], 404);
            }
            
            // Check if room has active bookings
            $activeBookings = DB::table('booking_table')
                ->where('room_id', $room_id)
                ->whereIn('status', ['Pending', 'Approved', 'checked_in'])
                ->count();
            
            if ($activeBookings > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete room. It has active bookings.'
                ], 400);
            }
            
            // Delete room
            DB::table('rooms')->where('room_id', $room_id)->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Room deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function createRoomCategory(Request $request)
    {
        $user = $this->getUserFromToken($request);
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401)->header('Content-Type', 'application/json');
        }
        
        // Only Admin can create room categories
        if ($user->usertype !== 'Admin') {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Admin access required.'
            ], 403)->header('Content-Type', 'application/json');
        }
        
        try {
            // Get all inputs from FormData
            $allInputs = $request->all();
            
            Log::info('Create room category request', [
                'all_inputs' => $allInputs,
                'has_file' => $request->hasFile('image'),
                'method' => $request->method(),
                'content_type' => $request->header('Content-Type')
            ]);
            
            $categoryName = $allInputs['category_name'] ?? null;
            $description = $allInputs['description'] ?? null;
            $price = $allInputs['price'] ?? null;
            
            // Validate required fields
            if (empty($categoryName) || trim($categoryName) === '') {
                Log::warning('Category creation failed: category name is empty');
                return response()->json([
                    'success' => false,
                    'message' => 'Category name is required'
                ], 400)->header('Content-Type', 'application/json');
            }
            
            if (empty($description) || trim($description) === '') {
                Log::warning('Category creation failed: description is empty');
                return response()->json([
                    'success' => false,
                    'message' => 'Description is required'
                ], 400)->header('Content-Type', 'application/json');
            }
            
            if ($price === null || $price === '' || !is_numeric($price) || floatval($price) < 0) {
                Log::warning('Category creation failed: invalid price', ['price' => $price]);
                return response()->json([
                    'success' => false,
                    'message' => 'Valid price is required'
                ], 400)->header('Content-Type', 'application/json');
            }
            
            // Check if category name already exists
            $existingCategory = DB::table('room_type')
                ->where('category_name', trim($categoryName))
                ->first();
            if ($existingCategory) {
                Log::warning('Category creation failed: duplicate category name', ['category_name' => $categoryName]);
                return response()->json([
                    'success' => false,
                    'message' => 'Category name already exists'
                ], 400)->header('Content-Type', 'application/json');
            }
            
            // Validate image if provided
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $allowedMimes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                if (!in_array($file->getMimeType(), $allowedMimes)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid image format. Only JPEG, JPG, PNG, and GIF are allowed.'
                    ], 400)->header('Content-Type', 'application/json');
                }
                if ($file->getSize() > 2048000) { // 2MB in bytes
                    return response()->json([
                        'success' => false,
                        'message' => 'Image size must be less than 2MB'
                    ], 400)->header('Content-Type', 'application/json');
                }
            }
            
            $imageName = '';
            
            // Handle image upload
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $extension = $file->getClientOriginalExtension();
                $imageName = uniqid() . '_' . time() . '.' . $extension;
                $path = public_path('img/room');
                
                // Ensure directory exists
                if (!is_dir($path)) {
                    mkdir($path, 0755, true);
                }
                
                $file->move($path, $imageName);
            }
            
            // Insert category
            try {
                DB::table('room_type')->insert([
                    'category_name' => trim($categoryName),
                    'description' => trim($description),
                    'price' => floatval($price),
                    'capacity' => $allInputs['capacity'] ?? '',
                    'bed' => $allInputs['bed'] ?? '',
                    'services' => $allInputs['services'] ?? '',
                    'image' => $imageName
                ]);
                
                Log::info('Room category created successfully', [
                    'category_name' => $categoryName,
                    'price' => $price
                ]);
            } catch (\Exception $insertError) {
                Log::error('Database insert error: ' . $insertError->getMessage(), [
                    'trace' => $insertError->getTraceAsString()
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Error saving category: ' . $insertError->getMessage()
                ], 500)->header('Content-Type', 'application/json');
            }
            
            // Ensure we return JSON with proper headers
            return response()->json([
                'success' => true,
                'message' => 'Room category created successfully'
            ], 201)->header('Content-Type', 'application/json');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . implode(', ', $e->errors())
            ], 422)->header('Content-Type', 'application/json');
        } catch (\Exception $e) {
            Log::error('Create room category error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'inputs' => $request->all()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500)->header('Content-Type', 'application/json');
        }
    }
    
    public function updateRoomCategory(Request $request, $category_id)
    {
        $user = $this->getUserFromToken($request);
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }
        
        // Only Admin can update room categories
        if ($user->usertype !== 'Admin') {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Admin access required.'
            ], 403);
        }
        
        try {
            // Check if category exists
            $category = DB::table('room_type')->where('category_id', $category_id)->first();
            if (!$category) {
                return response()->json([
                    'success' => false,
                    'message' => 'Category not found'
                ], 404);
            }
            
            // Get all input values - FormData sends all fields
            // Use all() to get all FormData values, then filter
            $allInputs = $request->all();
            $categoryName = $allInputs['category_name'] ?? null;
            $description = $allInputs['description'] ?? null;
            $price = $allInputs['price'] ?? null;
            $capacity = $allInputs['capacity'] ?? null;
            $bed = $allInputs['bed'] ?? null;
            $services = $allInputs['services'] ?? null;
            
            // Log for debugging
            Log::info('Update room category', [
                'category_id' => $category_id,
                'all_inputs' => $allInputs,
                'method' => $request->method(),
                'content_type' => $request->header('Content-Type'),
                'category_name' => $categoryName,
                'description' => $description,
                'price' => $price,
                'has_file' => $request->hasFile('image')
            ]);
            
            // Validate only if fields are provided and not empty
            $rules = [];
            if ($categoryName !== null && trim($categoryName) !== '') {
                $rules['category_name'] = 'required|string|max:255';
            }
            if ($description !== null && trim($description) !== '') {
                $rules['description'] = 'required|string';
            }
            if ($price !== null && $price !== '') {
                $rules['price'] = 'numeric|min:0';
            }
            if ($request->hasFile('image')) {
                $rules['image'] = 'image|mimes:jpeg,jpg,png,gif|max:2048';
            }
            
            if (!empty($rules)) {
                $request->validate($rules);
            }
            
            // Build update data - include all fields that are sent in FormData
            // FormData always sends all fields, so we update all of them
            $updateData = [];
            
            // Always update these fields if they're in the request (FormData sends all fields)
            if (isset($allInputs['category_name'])) {
                $updateData['category_name'] = trim($categoryName ?? '');
            }
            if (isset($allInputs['description'])) {
                $updateData['description'] = trim($description ?? '');
            }
            if (isset($allInputs['price']) && $price !== null && $price !== '') {
                $updateData['price'] = floatval($price);
            }
            if (isset($allInputs['capacity'])) {
                $updateData['capacity'] = $capacity ?? '';
            }
            if (isset($allInputs['bed'])) {
                $updateData['bed'] = $bed ?? '';
            }
            if (isset($allInputs['services'])) {
                $updateData['services'] = $services ?? '';
            }
            
            // Handle image upload
            if ($request->hasFile('image')) {
                // Delete old image if exists
                if ($category->image && file_exists(public_path('img/room/' . $category->image))) {
                    @unlink(public_path('img/room/' . $category->image));
                }
                
                $file = $request->file('image');
                $extension = $file->getClientOriginalExtension();
                $imageName = uniqid() . '_' . time() . '.' . $extension;
                $path = public_path('img/room');
                
                // Ensure directory exists
                if (!is_dir($path)) {
                    mkdir($path, 0755, true);
                }
                
                $file->move($path, $imageName);
                $updateData['image'] = $imageName;
            }
            
            // Log update data for debugging
            Log::info('Update data before query', [
                'updateData' => $updateData,
                'updateData_count' => count($updateData)
            ]);
            
            // Only update if there's data to update
            if (!empty($updateData)) {
                $updated = DB::table('room_type')
                    ->where('category_id', $category_id)
                    ->update($updateData);
                
                Log::info('Update result', [
                    'updated' => $updated,
                    'category_id' => $category_id
                ]);
                
                if ($updated === 0 && !empty($updateData)) {
                    // No rows were updated, but we had data - this might indicate the data is the same
                    Log::warning('No rows updated despite having update data', [
                        'category_id' => $category_id,
                        'updateData' => $updateData,
                        'current_category' => $category
                    ]);
                }
            } else {
                Log::warning('Update data is empty', [
                    'category_id' => $category_id,
                    'all_inputs' => $allInputs
                ]);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Room category updated successfully'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . implode(', ', $e->errors())
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Update room category error: ' . $e->getMessage(), [
                'category_id' => $category_id,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function deleteRoomCategory(Request $request, $category_id)
    {
        $user = $this->getUserFromToken($request);
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }
        
        // Only Admin can delete room categories
        if ($user->usertype !== 'Admin') {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Admin access required.'
            ], 403);
        }
        
        try {
            // Check if category exists
            $category = DB::table('room_type')->where('category_id', $category_id)->first();
            if (!$category) {
                return response()->json([
                    'success' => false,
                    'message' => 'Category not found'
                ], 404);
            }
            
            // Check if rooms are associated with this category
            $roomsCount = DB::table('rooms')->where('category_id', $category_id)->count();
            if ($roomsCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete room type. Rooms are associated with this category.'
                ], 400);
            }
            
            // Delete image file if exists
            if ($category->image && file_exists(public_path('img/room/' . $category->image))) {
                unlink(public_path('img/room/' . $category->image));
            }
            
            // Delete category
            DB::table('room_type')->where('category_id', $category_id)->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Room category deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}

