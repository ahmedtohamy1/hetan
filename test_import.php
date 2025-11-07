<?php

require_once 'vendor/autoload.php';

use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;

echo "Testing Excel import...\n";

// Test reading the Excel file
try {
    $rows = Excel::toArray([], 'donators.xlsx');
    echo "Excel file loaded successfully!\n";
    echo "Sheets found: " . count($rows) . "\n";

    foreach ($rows as $sheetIndex => $sheet) {
        echo "\nSheet " . ($sheetIndex + 1) . ":\n";
        echo "Rows: " . count($sheet) . "\n";

        if (count($sheet) > 0) {
            echo "Headers: " . json_encode($sheet[0], JSON_UNESCAPED_UNICODE) . "\n";

            for ($i = 1; $i < min(5, count($sheet)); $i++) {
                echo "Row " . ($i + 1) . ": " . json_encode($sheet[$i], JSON_UNESCAPED_UNICODE) . "\n";
            }
        }
    }
} catch (Exception $e) {
    echo "Error reading Excel file: " . $e->getMessage() . "\n";
}
