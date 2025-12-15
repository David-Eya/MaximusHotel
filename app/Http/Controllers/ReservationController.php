<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReservationController extends Controller
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
            ->select('u.userid', 'u.contact')
            ->first();
    }
    
    public function list(Request $request)
    {
        $user = $this->getUserFromToken($request);
        
        if (!$user) {
            return $this->jsonResponse(false, null, 'Unauthorized', 401);
        }
        
        try {
            $status = $request->get('status', '');
            
            $query = DB::table('booking_table as a')
                ->join('rooms as b', 'a.room_id', '=', 'b.room_id')
                ->join('room_type as c', 'b.category_id', '=', 'c.category_id')
                ->where('a.userid', $user->userid)
                ->select(
                    'a.*',
                    'a.status as book_status',
                    'b.*',
                    'c.*',
                    DB::raw('COALESCE(a.no_of_days, 0) * COALESCE(c.price, 0) as total_price')
                );
            
            if (!empty($status)) {
                $query->where('a.status', $status);
            }
            
            $reservations = $query->orderByRaw("FIELD(a.status, 'Pending', 'Approved', 'Reject', 'Cancelled')")
                ->orderBy('a.datetime', 'desc')
                ->get();
            
            // Return reservations in the expected format
            return response()->json([
                'success' => true,
                'reservations' => $reservations->toArray()
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse(false, null, 'Error: ' . $e->getMessage(), 500);
        }
    }
    
    public function create(Request $request)
    {
        $user = $this->getUserFromToken($request);
        
        if (!$user) {
            return $this->jsonResponse(false, null, 'Unauthorized', 401);
        }
        
        // Accept both check_in/check_out and checkin/checkout for compatibility
        $checkin = $request->input('check_in') ?? $request->input('checkin');
        $checkout = $request->input('check_out') ?? $request->input('checkout');
        $roomId = $request->input('room_id');
        
        // Manual validation to ensure JSON response
        $errors = [];
        
        if (empty($roomId) || !is_numeric($roomId)) {
            $errors[] = 'Room ID is required and must be a number';
        }
        
        if (empty($checkin)) {
            $errors[] = 'Check-in date is required';
        }
        
        if (empty($checkout)) {
            $errors[] = 'Check-out date is required';
        }
        
        if (!empty($errors)) {
            return $this->jsonResponse(false, null, implode(', ', $errors), 400);
        }
        
        // Validate dates
        $checkinDate = strtotime($checkin);
        $checkoutDate = strtotime($checkout);
        
        if ($checkinDate === false || $checkoutDate === false) {
            return $this->jsonResponse(false, null, 'Invalid date format', 400);
        }
        
        if ($checkoutDate <= $checkinDate) {
            return $this->jsonResponse(false, null, 'Check-out date must be after check-in date', 400);
        }
        
        // Calculate number of days
        try {
            $checkinDateTime = new \DateTime($checkin);
            $checkoutDateTime = new \DateTime($checkout);
            $interval = $checkinDateTime->diff($checkoutDateTime);
            $noOfDays = $interval->days;
            
            if ($noOfDays <= 0) {
                return $this->jsonResponse(false, null, 'Number of days must be greater than 0', 400);
            }
        } catch (\Exception $dateError) {
            return $this->jsonResponse(false, null, 'Invalid date format: ' . $dateError->getMessage(), 400);
        }
        
        try {
            // Check availability
            $conflicting = DB::table('booking_table')
                ->where('room_id', $roomId)
                ->where('status', '!=', 'Cancelled')
                ->where('status', '!=', 'Reject')
                ->where(function($query) use ($checkin, $checkout) {
                    $query->whereBetween('check_in', [$checkin, $checkout])
                          ->orWhereBetween('check_out', [$checkin, $checkout])
                          ->orWhere(function($q) use ($checkin, $checkout) {
                              $q->where('check_in', '<=', $checkin)
                                ->where('check_out', '>=', $checkout);
                          });
                })
                ->exists();
            
            if ($conflicting) {
                return $this->jsonResponse(false, null, 'Room is not available for the selected dates', 400);
            }
            
            // Get room price
            $room = DB::table('rooms as r')
                ->join('room_type as rt', 'r.category_id', '=', 'rt.category_id')
                ->where('r.room_id', $roomId)
                ->select('rt.price')
                ->first();
            
            if (!$room) {
                return $this->jsonResponse(false, null, 'Room not found', 404);
            }
            
            // Get user contact info
            $userInfo = DB::table('userinfo')
                ->where('userid', $user->userid)
                ->select('contact')
                ->first();
            
            $contact = $userInfo->contact ?? '';
            
            // Create booking
            $bookingId = DB::table('booking_table')->insertGetId([
                'userid' => $user->userid,
                'room_id' => $roomId,
                'contact' => $contact,
                'check_in' => $checkin,
                'check_out' => $checkout,
                'no_of_days' => $noOfDays,
                'status' => 'Pending',
                'datetime' => now()
            ]);
            
            return $this->jsonResponse(true, ['booking_id' => $bookingId], 'Reservation created successfully', 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $errors = $e->validator ? $e->validator->errors()->all() : [$e->getMessage()];
            return $this->jsonResponse(false, null, 'Validation failed: ' . implode(', ', $errors), 422);
        } catch (\Exception $e) {
            Log::error('Reservation create error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            return $this->jsonResponse(false, null, 'Error: ' . $e->getMessage(), 500);
        }
    }
    
    public function checkAvailability(Request $request)
    {
        // Accept both check_in/check_out and checkin/checkout for compatibility
        $checkin = $request->input('check_in') ?? $request->input('checkin');
        $checkout = $request->input('check_out') ?? $request->input('checkout');
        $roomId = $request->input('room_id');
        
        $request->merge([
            'checkin' => $checkin,
            'checkout' => $checkout
        ]);
        
        $request->validate([
            'room_id' => 'required|integer',
            'checkin' => 'required|date',
            'checkout' => 'required|date|after:checkin'
        ]);
        
        try {
            $conflicting = DB::table('booking_table')
                ->where('room_id', $roomId)
                ->where('status', '!=', 'Cancelled')
                ->where('status', '!=', 'Reject')
                ->where(function($query) use ($checkin, $checkout) {
                    $query->whereBetween('check_in', [$checkin, $checkout])
                          ->orWhereBetween('check_out', [$checkin, $checkout])
                          ->orWhere(function($q) use ($checkin, $checkout) {
                              $q->where('check_in', '<=', $checkin)
                                ->where('check_out', '>=', $checkout);
                          });
                })
                ->exists();
            
            return response()->json([
                'success' => true,
                'available' => !$conflicting
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse(false, null, 'Error: ' . $e->getMessage(), 500);
        }
    }
    
    public function cancel(Request $request)
    {
        $user = $this->getUserFromToken($request);
        
        if (!$user) {
            return $this->jsonResponse(false, null, 'Unauthorized', 401);
        }
        
        $request->validate([
            'booking_id' => 'required|integer'
        ]);
        
        try {
            $bookingId = $request->input('booking_id');
            
            // Check if booking belongs to user
            $booking = DB::table('booking_table')
                ->where('book_id', $bookingId)
                ->where('userid', $user->userid)
                ->first();
            
            if (!$booking) {
                return $this->jsonResponse(false, null, 'Booking not found', 404);
            }
            
            // Update status to Cancelled
            DB::table('booking_table')
                ->where('book_id', $bookingId)
                ->update(['status' => 'Cancelled']);
            
            return $this->jsonResponse(true, null, 'Reservation cancelled successfully');
        } catch (\Exception $e) {
            return $this->jsonResponse(false, null, 'Error: ' . $e->getMessage(), 500);
        }
    }
}

