<?php

namespace App\Services;

use App\Models\Backup;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class BackupService
{
    /**
     * Create a backup archive.
     */
    public function createBackup(?User $creator = null): Backup
    {
        $fileName = 'backup-' . date('Y-m-d-His') . '.json';
        $disk = 'local';

        $backup = Backup::create([
            'file_name' => $fileName,
            'disk' => $disk,
            'file_size' => 0,
            'status' => 'pending',
            'created_by' => $creator?->id,
            'created_at' => now(),
        ]);

        try {
            // Snapshot essential tables into JSON
            $tables = [
                'users',
                'weddings',
                'wedding_members',
                'wedding_details',
                'schedules',
                'guest_groups',
                'guests',
                'invitations',
                'gift_records',
                'plans',
                'subscriptions',
                'payments',
                'settings',
            ];

            $data = [];
            foreach ($tables as $table) {
                try {
                    $data[$table] = DB::table($table)->get()->toArray();
                } catch (Throwable $e) {
                    // skip if table missing
                }
            }

            $content = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            Storage::disk($disk)->put('backups/' . $fileName, $content);
            $size = Storage::disk($disk)->size('backups/' . $fileName);

            $backup->update([
                'file_size' => $size,
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            return $backup;
        } catch (Throwable $e) {
            $backup->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            return $backup;
        }
    }

    /**
     * Restore database from backup record.
     */
    public function restore(Backup $backup): bool
    {
        $path = 'backups/' . $backup->file_name;
        if (! Storage::disk($backup->disk)->exists($path)) {
            throw new \RuntimeException('Backup file does not exist on disk.');
        }

        $content = Storage::disk($backup->disk)->get($path);
        $data = json_decode($content, true);

        if (! is_array($data)) {
            throw new \RuntimeException('Invalid backup file content.');
        }

        // Restore verified tables within transaction
        DB::transaction(function () use ($data) {
            foreach ($data as $table => $rows) {
                if (in_array($table, ['settings', 'plans', 'templates'], true)) {
                    foreach ($rows as $row) {
                        $row = (array) $row;
                        DB::table($table)->upsert($row, ['id']);
                    }
                }
            }
        });

        return true;
    }
}
