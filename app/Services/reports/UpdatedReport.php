<?php

class UpdatedReport extends BaseReport
{
    public function __construct()
    {
        $this->fileName = 'updated_report.csv';
        $this->fieldsMapping = [
            'id' => 'ID',
            'full_name' => 'New full name',
            'card_number' => 'New card number',
            'email' => 'New email',
            'full_name_previous' => 'Previous full name',
            'card_number_previous' => 'Previous card number',
            'email_previous' => 'Previous email',
        ];
    }

    public static function make()
    {
        return resolve(self::class);
    }


}
