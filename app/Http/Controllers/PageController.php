<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class PageController extends Controller
{
    // Serve HTML files (keeping .html extension)
    public function index()
    {
        $htmlPath = resource_path('index.html');
        if (File::exists($htmlPath)) {
            return response()->file($htmlPath);
        }
        return response('Page not found', 404);
    }
    
    public function aboutUs()
    {
        $htmlPath = resource_path('about-us.html');
        if (File::exists($htmlPath)) {
            return response()->file($htmlPath);
        }
        return response('Page not found', 404);
    }
    
    public function contact()
    {
        $htmlPath = resource_path('contact.html');
        if (File::exists($htmlPath)) {
            return response()->file($htmlPath);
        }
        return response('Page not found', 404);
    }
    
    public function rooms()
    {
        $htmlPath = resource_path('rooms.html');
        if (File::exists($htmlPath)) {
            return response()->file($htmlPath);
        }
        return response('Page not found', 404);
    }
    
    public function roomDetails()
    {
        $htmlPath = resource_path('room-details.html');
        if (File::exists($htmlPath)) {
            return response()->file($htmlPath);
        }
        return response('Page not found', 404);
    }
    
    public function login()
    {
        $htmlPath = resource_path('login.html');
        if (File::exists($htmlPath)) {
            return response()->file($htmlPath);
        }
        return response('Page not found', 404);
    }
    
    public function profile()
    {
        $htmlPath = resource_path('profile.html');
        if (File::exists($htmlPath)) {
            return response()->file($htmlPath);
        }
        return response('Page not found', 404);
    }
    
    public function myReservation()
    {
        $htmlPath = resource_path('myreservation.html');
        if (File::exists($htmlPath)) {
            return response()->file($htmlPath);
        }
        return response('Page not found', 404);
    }
    
    // Admin pages
    public function adminIndex()
    {
        $htmlPath = resource_path('views/index.html');
        if (File::exists($htmlPath)) {
            return response()->file($htmlPath);
        }
        return response('Page not found', 404);
    }
    
    public function adminBookings()
    {
        $htmlPath = resource_path('views/bookings.html');
        if (File::exists($htmlPath)) {
            return response()->file($htmlPath);
        }
        return response('Page not found', 404);
    }
    
    public function adminRooms()
    {
        $htmlPath = resource_path('views/rooms.html');
        if (File::exists($htmlPath)) {
            return response()->file($htmlPath);
        }
        return response('Page not found', 404);
    }
    
    public function adminUsers()
    {
        $htmlPath = resource_path('views/users.html');
        if (File::exists($htmlPath)) {
            return response()->file($htmlPath);
        }
        return response('Page not found', 404);
    }
    
    public function adminRoomCategories()
    {
        $htmlPath = resource_path('views/room-categories.html');
        if (File::exists($htmlPath)) {
            return response()->file($htmlPath);
        }
        return response('Page not found', 404);
    }
    
    public function adminProfile()
    {
        $htmlPath = resource_path('views/profile.html');
        if (File::exists($htmlPath)) {
            return response()->file($htmlPath);
        }
        return response('Page not found', 404);
    }
    
    // API methods
    public function getRoomTypes()
    {
        try {
            $roomTypes = DB::table('room_type')->orderBy('category_id')->get();
            return $this->jsonResponse(true, $roomTypes);
        } catch (\Exception $e) {
            return $this->jsonResponse(false, null, 'Error fetching room types: ' . $e->getMessage(), 500);
        }
    }
    
    public function getRooms(Request $request)
    {
        try {
            $itemsPerPage = $request->get('items_per_page', 6);
            $page = $request->get('page', 1);
            $offset = ($page - 1) * $itemsPerPage;
            
            $query = DB::table('rooms as r')
                ->join('room_type as rt', 'r.category_id', '=', 'rt.category_id')
                ->select('r.*', 'rt.category_name', 'rt.price', 'rt.description', 'rt.image', 'rt.capacity', 'rt.bed', 'rt.services')
                ->where('r.status', 'Available');
            
            if ($request->has('search')) {
                $search = $request->get('search');
                $query->where(function($q) use ($search) {
                    $q->where('rt.category_name', 'LIKE', "%{$search}%")
                      ->orWhere('r.room_id', '=', $search);
                });
            }
            
            if ($request->has('room_type')) {
                $query->where('r.category_id', $request->get('room_type'));
            }
            
            // Handle check-in and check-out date filters if provided
            if ($request->has('checkin') && $request->has('checkout')) {
                $checkin = $request->get('checkin');
                $checkout = $request->get('checkout');
                
                // Get room IDs that are booked during this period
                $bookedRoomIds = DB::table('booking_table')
                    ->where('status', 'Approved')
                    ->where(function($q) use ($checkin, $checkout) {
                        $q->whereBetween('check_in', [$checkin, $checkout])
                          ->orWhereBetween('check_out', [$checkin, $checkout])
                          ->orWhere(function($q2) use ($checkin, $checkout) {
                              $q2->where('check_in', '<=', $checkin)
                                 ->where('check_out', '>=', $checkout);
                          });
                    })
                    ->pluck('room_id')
                    ->toArray();
                
                if (!empty($bookedRoomIds)) {
                    $query->whereNotIn('r.room_id', $bookedRoomIds);
                }
            }
            
            $total = $query->count();
            $rooms = $query->offset($offset)->limit($itemsPerPage)->get();
            
            $totalPages = $total > 0 ? ceil($total / $itemsPerPage) : 0;
            
            // Return rooms array directly as data, with pagination info
            // Ensure data is always an array, even if empty
            return response()->json([
                'success' => true,
                'data' => $rooms->toArray(), // Convert collection to array
                'pagination' => [
                    'current_page' => (int)$page,
                    'total_pages' => $totalPages,
                    'total_items' => $total,
                    'items_per_page' => (int)$itemsPerPage
                ]
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse(false, null, 'Error fetching rooms: ' . $e->getMessage(), 500);
        }
    }
    
    public function getRoomDetails(Request $request)
    {
        try {
            $roomId = $request->get('room_id');
            $roomType = $request->get('room_type');
            
            if ($roomId) {
                // Get specific room by ID
                $room = DB::table('rooms as r')
                    ->join('room_type as rt', 'r.category_id', '=', 'rt.category_id')
                    ->where('r.room_id', $roomId)
                    ->select('r.*', 'rt.category_name', 'rt.price', 'rt.description', 'rt.image', 'rt.capacity', 'rt.bed', 'rt.services')
                    ->first();
                
                if ($room) {
                    return $this->jsonResponse(true, $room);
                } else {
                    return $this->jsonResponse(false, null, 'Room not found', 404);
                }
            } elseif ($roomType) {
                // Get first available room of this type, or room type details
                $room = DB::table('rooms as r')
                    ->join('room_type as rt', 'r.category_id', '=', 'rt.category_id')
                    ->where('rt.category_name', $roomType)
                    ->where('r.status', 'Available')
                    ->select('r.*', 'rt.category_name', 'rt.price', 'rt.description', 'rt.image', 'rt.capacity', 'rt.bed', 'rt.services')
                    ->first();
                
                if ($room) {
                    return $this->jsonResponse(true, $room);
                } else {
                    // If no available room, return room type info
                    $roomTypeData = DB::table('room_type')
                        ->where('category_name', $roomType)
                        ->first();
                    
                    if ($roomTypeData) {
                        // Create a mock room object with room type data
                        $roomData = (object) [
                            'room_id' => null,
                            'category_id' => $roomTypeData->category_id,
                            'category_name' => $roomTypeData->category_name,
                            'price' => $roomTypeData->price,
                            'description' => $roomTypeData->description,
                            'image' => $roomTypeData->image,
                            'capacity' => $roomTypeData->capacity ?? null,
                            'bed' => $roomTypeData->bed ?? null,
                            'services' => $roomTypeData->services ?? null,
                            'status' => 'available'
                        ];
                        return $this->jsonResponse(true, $roomData);
                    } else {
                        return $this->jsonResponse(false, null, 'Room type not found', 404);
                    }
                }
            } else {
                return $this->jsonResponse(false, null, 'Room ID or Room Type is required', 400);
            }
        } catch (\Exception $e) {
            return $this->jsonResponse(false, null, 'Error fetching room details: ' . $e->getMessage(), 500);
        }
    }
}

