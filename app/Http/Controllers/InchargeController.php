<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InchargeController extends Controller
{
    public function dashboard(Request $request)
    {
        try {
            $stats = [
                'total_bookings' => DB::table('booking_table')->count(),
                'pending_bookings' => DB::table('booking_table')->where('status', 'Pending')->count(),
                'approved_bookings' => DB::table('booking_table')->where('status', 'Approved')->count()
            ];
            
            return $this->jsonResponse(true, $stats);
        } catch (\Exception $e) {
            return $this->jsonResponse(false, null, 'Error: ' . $e->getMessage(), 500);
        }
    }
}




