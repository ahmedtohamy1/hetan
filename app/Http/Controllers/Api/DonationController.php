<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Donation;
use App\Models\Donator;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DonationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Donation::with('donator');

        // Filter by status if provided
        if ($request->has('status') && !empty($request->status)) {
            $query->where('status', $request->status);
        }

        // Filter by donator if provided
        if ($request->has('donator_id') && !empty($request->donator_id)) {
            $query->where('donator_id', $request->donator_id);
        }

        $donations = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json($donations);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'donator_id' => 'required|exists:donators,id',
            'amount' => 'required|numeric|min:0.01|max:999999.99',
            'donor_phone_number' => 'required|string|max:20',
            'global_donation_number' => 'required|string|max:50',
            'status' => 'sometimes|in:pending,confirmed,completed',
        ]);

        // Set default status if not provided
        $validated['status'] = $validated['status'] ?? 'pending';

        $donation = Donation::create($validated);

        return response()->json($donation->load('donator'), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Donation $donation): JsonResponse
    {
        return response()->json($donation->load('donator'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Donation $donation): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'sometimes|numeric|min:0.01|max:999999.99',
            'donor_phone_number' => 'sometimes|string|max:20',
            'status' => 'sometimes|in:pending,confirmed,completed',
        ]);

        $donation->update($validated);

        return response()->json($donation->load('donator'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Donation $donation): JsonResponse
    {
        $donation->delete();

        return response()->json(['message' => 'Donation deleted successfully']);
    }

    /**
     * Update donation number for a donator.
     */
    public function updateDonationNumber(Request $request, Donator $donator): JsonResponse
    {
        $validated = $request->validate([
            'donation_number' => 'required|string|max:50|unique:donators,donation_number,' . $donator->id,
        ]);

        $donator->update($validated);

        return response()->json($donator);
    }
}
