<?php

class FailedReport extends BaseReport
{
    public function __construct()
    {
        $this->fileName = 'failed_report.csv';
        $this->fieldsMapping = [
            'id' => 'ID',
            'first_name' => 'First name',
            'last_name' => 'Last name',
            'card_number' => 'Card number',
            'email' => 'Email',
            'reason' => 'Fail reason',
        ];
    }

    public static function make()
    {
        return resolve(self::class);
    }


}
