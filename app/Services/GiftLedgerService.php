<?php

namespace App\Services;

use App\Enums\GiftCurrency;
use App\Enums\GiftEntryType;
use App\Models\GiftRecord;
use App\Models\User;
use App\Models\Wedding;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class GiftLedgerService
{
    /**
     * Record a gift or correction with idempotency on client_uuid.
     *
     * @param  \App\Models\Wedding  $wedding
     * @param  \App\Models\User  $recorder
     * @param  array<string, mixed>  $data
     * @return array{record: \App\Models\GiftRecord, is_duplicate: bool}
     */
    public function record(Wedding $wedding, User $recorder, array $data): array
    {
        $clientUuid = $data['client_uuid'];

        // 1. Check idempotency: if client_uuid already exists, return existing row
        $existing = GiftRecord::withoutGlobalScopes()
            ->where('client_uuid', $clientUuid)
            ->first();

        if ($existing) {
            return [
                'record' => $existing,
                'is_duplicate' => true,
            ];
        }

        $entryType = $data['entry_type'] ?? GiftEntryType::GIFT->value;
        if ($entryType instanceof GiftEntryType) {
            $entryType = $entryType->value;
        }

        $correctsId = $data['corrects_id'] ?? null;
        $currency = $data['currency'] ?? GiftCurrency::USD->value;
        if ($currency instanceof GiftCurrency) {
            $currency = $currency->value;
        }

        // If this is a correction, validate referenced record
        if ($entryType === GiftEntryType::CORRECTION->value) {
            if (! $correctsId) {
                throw new InvalidArgumentException('Corrections must reference an original gift record via corrects_id.');
            }

            $original = GiftRecord::withoutGlobalScopes()
                ->where('wedding_id', $wedding->id)
                ->find($correctsId);

            if (! $original) {
                throw new InvalidArgumentException('Original gift record to correct was not found.');
            }

            // Enforce currency matching the original record
            $originalCurrency = $original->currency instanceof GiftCurrency ? $original->currency->value : (string) $original->currency;
            if ($currency !== $originalCurrency) {
                throw new InvalidArgumentException("Correction currency ({$currency}) must match original record currency ({$originalCurrency}).");
            }
        }

        $record = GiftRecord::create([
            'wedding_id' => $wedding->id,
            'client_uuid' => $clientUuid,
            'guest_id' => $data['guest_id'] ?? null,
            'giver_name' => $data['giver_name'],
            'amount' => $data['amount'],
            'currency' => $currency,
            'method' => $data['method'] ?? 'cash',
            'entry_type' => $entryType,
            'corrects_id' => $correctsId,
            'notes' => $data['notes'] ?? null,
            'recorded_by' => $recorder->id,
            'recorded_at' => $data['recorded_at'] ?? now(),
            'created_at' => now(),
        ]);

        return [
            'record' => $record,
            'is_duplicate' => false,
        ];
    }

    /**
     * Compute totals for a wedding, strictly grouped by currency.
     * KHR and USD are NEVER combined.
     *
     * @param  \App\Models\Wedding  $wedding
     * @return array<string, mixed>
     */
    public function totalsFor(Wedding $wedding): array
    {
        $sums = GiftRecord::withoutGlobalScopes()
            ->where('wedding_id', $wedding->id)
            ->select('currency', DB::raw('SUM(amount) as total_amount'), DB::raw('COUNT(*) as entries_count'))
            ->groupBy('currency')
            ->get();

        $usdTotal = 0.00;
        $usdCount = 0;
        $khrTotal = 0.00;
        $khrCount = 0;

        foreach ($sums as $row) {
            $currency = $row->currency instanceof GiftCurrency ? $row->currency->value : (string) $row->currency;
            if ($currency === GiftCurrency::USD->value) {
                $usdTotal = (float) $row->total_amount;
                $usdCount = (int) $row->entries_count;
            } elseif ($currency === GiftCurrency::KHR->value) {
                $khrTotal = (float) $row->total_amount;
                $khrCount = (int) $row->entries_count;
            }
        }

        return [
            'USD' => $usdTotal,
            'KHR' => $khrTotal,
            'total_entries' => $usdCount + $khrCount,
            'by_currency' => [
                'USD' => [
                    'currency' => 'USD',
                    'total' => $usdTotal,
                    'count' => $usdCount,
                ],
                'KHR' => [
                    'currency' => 'KHR',
                    'total' => $khrTotal,
                    'count' => $khrCount,
                ],
            ],
        ];
    }
}
