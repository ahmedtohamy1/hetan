<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SettingsController extends Controller
{
    /**
     * Get the global donation number
     */
    public function getGlobalDonationNumber(): JsonResponse
    {
        $donationNumber = Setting::getValue('global_donation_number', 'DON-000001');

        return response()->json([
            'global_donation_number' => $donationNumber
        ]);
    }

    /**
     * Update the global donation number
     */
    public function updateGlobalDonationNumber(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'global_donation_number' => 'required|string|max:50',
        ]);

        Setting::setValue('global_donation_number', $validated['global_donation_number']);

        return response()->json([
            'message' => 'تم تحديث رقم التبرع العام بنجاح',
            'global_donation_number' => $validated['global_donation_number']
        ]);
    }
}
