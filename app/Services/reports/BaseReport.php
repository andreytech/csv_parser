<?php

class BaseReport
{
    private $reportsPath = 'storage/framework/testing/';
    private $fileHandler = null;
    protected $fileName = '';
    protected $fieldsMapping = [];

    private function createHandler(): void
    {
        $this->fileHandler = fopen($this->reportsPath.$this->fileName, 'w');
    }

    private function writeHeader():void
    {
        fputcsv($this->fileHandler, [$this->fieldsMapping]);
    }

    public function write($data): bool
    {
        if(!$this->fileHandler) {
            $this->createHandler();
            $this->writeHeader();
        }

        $csvData = [];

        foreach($this->fieldsMapping as $field => $heading) {
            $value = '';
            if(isset($data[$field])) {
                $value = $data[$field];
            }
            $csvData[] = $value;
        }

        if(fputcsv($this->fileHandler, $csvData) === false) {
            return false;
        }

        return true;
    }

    function __destruct()
    {
        fclose($this->fileHandler);
    }
}
