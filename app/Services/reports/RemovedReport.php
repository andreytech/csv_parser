<?php

class RemovedReport extends BaseReport
{
    public function __construct()
    {
        $this->fileName = 'removed_report.csv';
        $this->fieldsMapping = [
            'id' => 'ID',
            'first_name' => 'First name',
            'last_name' => 'Last name',
            'card_number' => 'Card number',
            'email' => 'Email',
        ];
    }

    public static function make()
    {
        return resolve(self::class);
    }


}
