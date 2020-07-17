<?php

namespace Tests\Unit;

use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CSVTest extends TestCase
{
    use RefreshDatabase;

    private $data = [];

    public function setUp(): void
    {
        parent::setUp();

        factory(User::class, 10)->create();
        factory(User::class, 2)->create(['deleted_at' => now()]);

        $this->addNewData();
        $this->addUpdatedData();
        $this->addRestoredData();

        $this->createCsvFile();
    }

    private function addNewData()
    {
        $this->data[] = [
            User::query()->withTrashed()->count() + 1,
            'First name CSV Only 1',
            'Last name CSV Only 1',
            '1111222233334444',
            'new1@test.com'
        ];
    }

    private function addUpdatedData()
    {
        $this->data[] = [
            User::query()->withTrashed(false)->first()->getKey(),
            'First name Existing 1',
            'Last name Existing 1',
            '1111222233335555',
            'updated1@test.com'
        ];
    }

    private function addRestoredData()
    {
        $this->data[] = [
            User::query()->onlyTrashed()->first()->getKey(),
            'First name Restored 1',
            'Last name Restored 1',
            '1111222233336666',
            'restored1@test.com'
        ];
    }

    private function createCsvFile()
    {
        $file = fopen("storage/framework/testing/test.csv", 'w');
        foreach ($this->data as $row) {
            fputcsv($file, $row);
        }
        fclose($file);
    }

    /**
     * Test added values
     *
     * @return void
     */
    public function testAddedValues()
    {
        Artisan::call('csv:process');

        $this->assertDatabaseHas('users', [
            'card_number' => '1111222233334444',
            'email' => 'new1@test.com',
        ]);

    }
}
