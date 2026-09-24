<?php

namespace App\Services;

use App\Models\Guest;
use App\Models\GuestGroup;
use App\Models\Wedding;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GuestImportService
{
    /**
     * Parse CSV and return row-by-row preview with validation.
     *
     * @param  \Illuminate\Http\UploadedFile  $file
     * @param  \App\Models\Wedding  $wedding
     * @return array<string, mixed>
     */
    public function preview(UploadedFile $file, Wedding $wedding): array
    {
        $handle = fopen($file->getRealPath(), 'r');
        if (! $handle) {
            return [
                'total_rows' => 0,
                'valid_rows' => 0,
                'invalid_rows' => 0,
                'has_errors' => true,
                'rows' => [],
            ];
        }

        $header = fgetcsv($handle);
        if (! $header) {
            fclose($handle);
            return [
                'total_rows' => 0,
                'valid_rows' => 0,
                'invalid_rows' => 0,
                'has_errors' => true,
                'rows' => [],
            ];
        }

        // Normalize header
        $header = array_map(fn ($h) => strtolower(trim((string) $h)), $header);

        $rows = [];
        $seenPhones = [];
        $rowNumber = 1;
        $invalidCount = 0;
        $validCount = 0;

        while (($line = fgetcsv($handle)) !== false) {
            $rowNumber++;
            if (empty(array_filter($line))) {
                continue; // Skip empty rows
            }

            $rowData = [];
            foreach ($header as $idx => $colName) {
                $rowData[$colName] = isset($line[$idx]) ? trim($line[$idx]) : '';
            }

            $errors = [];
            $name = $rowData['name'] ?? $rowData['guest_name'] ?? '';
            $phone = $rowData['phone'] ?? $rowData['telephone'] ?? null;
            $email = $rowData['email'] ?? null;
            $side = strtolower($rowData['side'] ?? 'groom');
            $seats = isset($rowData['seats']) && is_numeric($rowData['seats']) ? (int) $rowData['seats'] : 1;
            $groupName = $rowData['group'] ?? $rowData['group_name'] ?? null;

            // Validate Name
            if (empty($name)) {
                $errors['name'] = 'Guest name is required.';
            }

            // Validate Side
            if (! in_array($side, ['groom', 'bride', 'mutual'], true)) {
                $side = 'groom';
            }

            // Validate Seats
            if ($seats < 1 || $seats > 50) {
                $errors['seats'] = 'Seats must be between 1 and 50.';
            }

            // Validate Email format
            if (! empty($email) && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Email format is invalid.';
            }

            // Deduplicate within the same import batch by phone number
            if (! empty($phone)) {
                $cleanPhone = preg_replace('/[^0-9+]/', '', $phone);
                if (isset($seenPhones[$cleanPhone])) {
                    $errors['phone'] = "Duplicate phone number {$phone} in import batch (first seen at row {$seenPhones[$cleanPhone]}).";
                } else {
                    $seenPhones[$cleanPhone] = $rowNumber;
                }
            }

            $isValid = empty($errors);
            if ($isValid) {
                $validCount++;
            } else {
                $invalidCount++;
            }

            $rows[] = [
                'row_number' => $rowNumber,
                'data' => [
                    'name' => $name,
                    'phone' => $phone ?: null,
                    'email' => $email ?: null,
                    'side' => $side,
                    'seats' => $seats,
                    'group_name' => $groupName ?: null,
                    'notes' => $rowData['notes'] ?? null,
                ],
                'errors' => $errors,
                'is_valid' => $isValid,
            ];
        }

        fclose($handle);

        return [
            'total_rows' => count($rows),
            'valid_rows' => $validCount,
            'invalid_rows' => $invalidCount,
            'has_errors' => $invalidCount > 0,
            'rows' => $rows,
        ];
    }

    /**
     * Commit validated rows to the database.
     *
     * @param  \App\Models\Wedding  $wedding
     * @param  array<int, array<string, mixed>>  $rows
     * @return int Count of imported guests
     */
    public function commit(Wedding $wedding, array $rows): int
    {
        return DB::transaction(function () use ($wedding, $rows) {
            $importedCount = 0;
            $groupsCache = [];

            foreach ($rows as $row) {
                $data = $row['data'] ?? $row;

                $groupId = null;
                $groupName = trim($data['group_name'] ?? '');
                if (! empty($groupName)) {
                    if (! isset($groupsCache[$groupName])) {
                        $group = GuestGroup::withoutGlobalScopes()
                            ->firstOrCreate(
                                ['wedding_id' => $wedding->id, 'name' => $groupName],
                                ['order' => 0]
                            );
                        $groupsCache[$groupName] = $group->id;
                    }
                    $groupId = $groupsCache[$groupName];
                }

                Guest::create([
                    'wedding_id' => $wedding->id,
                    'group_id' => $groupId,
                    'name' => $data['name'],
                    'phone' => $data['phone'] ?? null,
                    'email' => $data['email'] ?? null,
                    'side' => $data['side'] ?? 'groom',
                    'seats' => isset($data['seats']) ? (int) $data['seats'] : 1,
                    'token' => Str::random(32),
                    'notes' => $data['notes'] ?? null,
                ]);

                $importedCount++;
            }

            return $importedCount;
        });
    }
}
