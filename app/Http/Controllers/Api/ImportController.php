<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Imports\DonatorsImport;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;

class ImportController extends Controller
{
    /**
     * Import donators from XLSX file.
     */
    public function importDonators(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'file' => 'required|file|mimes:xlsx,xls,csv|max:10240', // 10MB max
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'File validation failed',
                'errors' => $e->errors(),
            ], 422);
        }

        try {
            $import = new DonatorsImport();
            Excel::import($import, $request->file('file'));

            return response()->json([
                'message' => 'Donators imported successfully',
                'imported_count' => $import->getImportedCount(),
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Import failed due to validation errors',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Import failed',
                'error' => $e->getMessage(),
                'file_info' => [
                    'name' => $request->file('file')->getClientOriginalName(),
                    'size' => $request->file('file')->getSize(),
                    'mime' => $request->file('file')->getMimeType(),
                ]
            ], 500);
        }
    }
}
