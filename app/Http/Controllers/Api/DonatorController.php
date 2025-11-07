<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Donator;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DonatorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Donator::query();

        // Search functionality for donation page
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('phone', 'LIKE', "%{$search}%")
                  ->orWhere('donation_number', 'LIKE', "%{$search}%");
            });
        }

        $donators = $query->paginate(20);

        return response()->json($donators);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'donation_number' => 'required|string|max:50|unique:donators',
        ]);

        $donator = Donator::create($validated);

        return response()->json($donator, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Donator $donator): JsonResponse
    {
        return response()->json($donator->load('donations'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Donator $donator): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'phone' => 'sometimes|required|string|max:20',
            'donation_number' => 'sometimes|required|string|max:50|unique:donators,donation_number,' . $donator->id,
        ]);

        $donator->update($validated);

        return response()->json($donator);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Donator $donator): JsonResponse
    {
        $donator->delete();

        return response()->json(['message' => 'Donator deleted successfully']);
    }
}
