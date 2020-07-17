<?php

namespace App\Console\Commands;

use App\User;
use Illuminate\Console\Command;

class ProcessCsv extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'csv:process';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    private function parseRow($data): array
    {
        $id = $data[0];
        $firstName = $data[1];
        $lastName = $data[2];
        $cardNumber = $data[3];
        $email = $data[4];

        $fullName = $firstName.' '.$lastName;

        // TODO Add validation

        $duplicates = DB::table('temp')
            ->where('id', $id)
            ->orWhere('card_number', $cardNumber)
            ->pluck('id');
        if($duplicates) {
            foreach ($duplicates as $duplicateId) {
                // TODO Seperate fail reasons for id and card number
                // TODO Append new fail reason instead of overwriting it
                DB::table('temp')
                    ->where('id', $duplicateId)
                    ->update([
                        'is_failed' => true,
                        'fail_reason' => 'Duplicate id or card number'
                    ]);
            }
        }

        $existingRow = User::query()
            ->withTrashed()
            ->where('id', $id)
            ->first();
        if($existingRow) {
            // Existing id, update value in db
            $action = 'updated';
            if($existingRow->trashed()) {
                $action = 'restored';
            }
            DB::table('temp')->insert([
                'id' => $id,
                'full_name_previous' => $existingRow->full_name,
                'card_number_previous' => $existingRow->card_number,
                'email_previous' => $existingRow->email,
                'full_name' => $fullName,
                'card_number' => $cardNumber,
                'email' => $email,
                'action' => $action,
                'is_failed' => false,
                'fail_reason' => '',
            ]);
        }else {
            // New id, insert value in db
            DB::table('temp')->insert([
                'id' => $id,
                'full_name_previous' => '',
                'card_number_previous' => '',
                'email_previous' => '',
                'full_name' => $fullName,
                'card_number' => $cardNumber,
                'email' => $email,
                'action' => 'added',
                'is_failed' => false,
                'fail_reason' => '',
            ]);
        }
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if (($handle = fopen('storage/framework/testing/test.csv', "r")) === false) {
            $this->comment('Can not open csv');
            return false;
        }

        while (($data = fgetcsv($handle)) !== false) {
            if($this->parseRow($data) === false) {
                $this->comment('Row can not be parsed');
                return false;
            }
        }
        fclose($handle);

        // Generating CSV report for removed rows
        $removedReport = \RemovedReport::make();
        $removedRowsCount = 0;

        DB::table('users')
            ->select('users.*')
            ->leftJoin('temp', 'users.id', '=', 'temp.id')
            ->leftJoin('temp', function($join)
            {
                $join->on('users.id', '=', 'temp.id');
                $join->on('temp.is_failed', '=', false);
            })
            ->whereNull('temp.full_name')
            ->chunkById(100, function ($rows) use ($removedReport, $removedRowsCount) {
                foreach ($rows as $row) {
                    $removedRowsCount++;
                    $removedReport->write([
                        'id' => $row->id,
                        'full_name' => $row->full_name,
                        'card_number' => $row->card_number,
                        'email' => $row->email,
                    ]);
                    $row->delete();
                }
            });

        unset($removedReport);
        $this->comment('Removed rows: '.$removedRowsCount);

        // Generating CSV report for failed rows
        $failedReport = \FailedReport::make();
        $failedRowsCount = 0;

        DB::table('temp')
            ->where('is_failed', true)
            ->chunkById(100, function ($rows) use ($failedReport, $failedRowsCount) {
                foreach ($rows as $row) {
                    $failedRowsCount++;
                    $failedReport->write([
                        'id' => $row->id,
                        'full_name' => $row->full_name,
                        'card_number' => $row->card_number,
                        'email' => $row->email,
                        'reason' => $row->reason
                    ]);
                }
            });

        unset($failedReport);
        $this->comment('Failed rows: '.$failedRowsCount);

        // Generating CSV report for added rows
        $addedReport = \AddedReport::make();
        $addedRowsCount = 0;

        DB::table('temp')
            ->where('action', 'added')
            ->chunkById(100, function ($rows) use ($addedReport, $addedRowsCount) {
                foreach ($rows as $row) {
                    $addedRowsCount++;
                    $addedReport->write([
                        'id' => $row->id,
                        'full_name' => $row->full_name,
                        'card_number' => $row->card_number,
                        'email' => $row->email,
                    ]);
                }
            });

        unset($addedReport);
        $this->comment('Added rows: '.$addedRowsCount);

        // Generating CSV report for updated rows
        $updatedReport = \UpdatedReport::make();
        $updatedRowsCount = 0;

        DB::table('temp')
            ->where('action', 'updated')
            ->chunkById(100, function ($rows) use ($updatedReport, $updatedRowsCount) {
                foreach ($rows as $row) {
                    $updatedRowsCount++;
                    $updatedReport->write([
                        'id' => $row->id,
                        'full_name' => $row->full_name,
                        'card_number' => $row->card_number,
                        'email' => $row->email,
                        'previous_full_name' => $row->previous_full_name,
                        'previous_card_number' => $row->previous_card_number,
                        'previous_email' => $row->previous_email,
                    ]);
                }
            });

        unset($updatedReport);
        $this->comment('Updated rows: '.$updatedRowsCount);

        // Generating CSV report for restored rows
        $restoredReport = \RestoredReport::make();
        $restoredRowsCount = 0;

        DB::table('temp')
            ->where('action', 'restored')
            ->chunkById(100, function ($rows) use ($restoredReport, $restoredRowsCount) {
                foreach ($rows as $row) {
                    $restoredRowsCount++;
                    $restoredReport->write([
                        'id' => $row->id,
                        'full_name' => $row->full_name,
                        'card_number' => $row->card_number,
                        'email' => $row->email,
                        'previous_full_name' => $row->previous_full_name,
                        'previous_card_number' => $row->previous_card_number,
                        'previous_email' => $row->previous_email,
                    ]);
                }
            });

        unset($restoredReport);
        $this->comment('Restored rows: '.$restoredRowsCount);
    }
}
