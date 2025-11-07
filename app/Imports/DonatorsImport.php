<?php

namespace App\Imports;

use App\Models\Donator;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class DonatorsImport implements ToModel, WithHeadingRow
{
    private $rowNumber = 0;
    private $importedCount = 0;
    private $nextDonationNumber;

    public function __construct()
    {
        // Find the next available donation number
        $maxDonationNumber = Donator::max('donation_number');
        if ($maxDonationNumber) {
            // Extract the number from "DON-000108" format
            $number = (int) str_replace('DON-', '', $maxDonationNumber);
            $this->nextDonationNumber = $number + 1;
        } else {
            $this->nextDonationNumber = 1;
        }
    }

    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        $this->rowNumber++;

        // Skip if empty row
        if (empty(array_filter($row))) {
            return null;
        }

        // Find name and phone columns dynamically
        $name = '';
        $phone = '';

        foreach ($row as $key => $value) {
            $keyLower = strtolower(trim($key));
            $value = trim($value);

            // Check for name columns
            if (empty($name)) {
                if (str_contains($keyLower, 'alasm') || str_contains($keyLower, 'اسم') || str_contains($keyLower, 'name') || $keyLower === 'الاسم' || $keyLower === 'name') {
                    $name = $value;
                }
            }

            // Check for phone columns
            if (empty($phone)) {
                if (str_contains($keyLower, 'rkm_almobayl') || str_contains($keyLower, 'هاتف') || str_contains($keyLower, 'موبايل') || str_contains($keyLower, 'phone') || str_contains($keyLower, 'واتساب') || $keyLower === 'رقم الموبايل' || $keyLower === 'phone') {
                    $phone = $value;
                }
            }
        }

        // Skip if both name and phone are empty
        if (empty($name) || empty($phone)) {
            return null;
        }

        // Generate unique donation number starting from next available number
        $donationNumber = 'DON-' . str_pad($this->nextDonationNumber + $this->importedCount, 6, '0', STR_PAD_LEFT);

        $donator = new Donator([
            'name' => $name,
            'phone' => $phone,
            'donation_number' => $donationNumber,
        ]);

        $this->importedCount++;
        return $donator;
    }

    public function getImportedCount(): int
    {
        return $this->importedCount;
    }
}
