<?php

namespace App\Console\Commands;

use App\Jobs\RecalculateRankingsJob;
use App\Services\ExcelImportService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('quiniela:import {file : Path to the Excel file}')]
#[Description('Import participants and predictions from an Excel file')]
class ImportParticipants extends Command
{
    public function handle(ExcelImportService $importService): int
    {
        $file = $this->argument('file');

        if (! file_exists($file)) {
            $this->error("File not found: {$file}");

            return self::FAILURE;
        }

        $this->info("Importing from: {$file}");

        $stats = $importService->import($file);

        $this->info("Import complete:");
        $this->table(['Metric', 'Count'], [
            ['Participants', $stats['participants']],
            ['Predictions', $stats['predictions']],
            ['Skipped', $stats['skipped']],
        ]);

        if ($this->confirm('Recalculate rankings now?', true)) {
            $this->info('Dispatching ranking recalculation...');
            RecalculateRankingsJob::dispatch();
            $this->info('Job dispatched.');
        }

        return self::SUCCESS;
    }
}
