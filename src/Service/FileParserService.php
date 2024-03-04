<?php

namespace App\Service;

use Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileParserService
{

    public function parseFile(UploadedFile $file){
        $filePath = $file->getRealPath();
        $fileExtension = strtolower($file->getClientOriginalExtension());

        switch ($fileExtension) {
            case 'csv':
                $data = $this->getDatasByLineCsv($filePath);
                break;
            case 'xls':
            case 'xlsx':
                $data = $this->getDatasByLineXls($filePath);
                break;
            default:
                throw new Exception("Unsupported file type: {$fileExtension}");
        }
        return $this->parseDatas($data);
    }

    public function parseDatas($datas): array
    {
        $dataStartIndex = $this->detectDataStart($datas);
        $headers = $datas[$dataStartIndex-1] ?? [];
        $dataRows = array_slice($datas, $dataStartIndex);
        $formattedData = array_map(function($row) use ($headers) {
            $rowWithKeys = array_combine($headers, $row);
    
            $rowWithKeys["id"] = implode("_", $rowWithKeys);
            return array_map(function($cell) {
                if ($this->looksLikeDate($cell)) {
                    $date = \DateTime::createFromFormat('Y-m-d', $cell) ?: \DateTime::createFromFormat('d/m/Y', $cell) ?: \DateTime::createFromFormat('d-m-Y', $cell);
                    return $date->format('d/m/Y');
                } elseif ($this->looksLikeAmount($cell)) {
                    $amount = preg_replace('/[^\d,.-]/', '', $cell);
                    $amount = str_replace(',', '.', $amount);
                    return floatval($amount);
                }
                return $cell;
            }, $rowWithKeys);
        }, $dataRows);
    
        return ['headers' => $headers, 'datas' => $formattedData];
    }

    private function getDatasByLineCsv($filePath){
        $csvContent = file_get_contents($filePath);
    
        $encoding = mb_detect_encoding($csvContent, mb_list_encodings(), true);
    
        if ($encoding && strtoupper($encoding) !== 'UTF-8') {
            $csvContent = mb_convert_encoding($csvContent, 'UTF-8', $encoding);
            file_put_contents($filePath, $csvContent);
        }
        $datasByLine = [];
        $fileHandle = fopen($filePath, 'r');
        while (($line = fgets($fileHandle)) !== false) {
            $datasByLine[] = str_getcsv($line, ';');
        }
        fclose($fileHandle);
        return $datasByLine;
    }

    private function getDatasByLineXls($filePath){
        $datasByLine = [];
        $spreadsheet = IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        
        foreach ($worksheet->getRowIterator() as $row) {
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);
            $rowData = [];
            foreach ($cellIterator as $cell) {
                if ($cell !== null) {
                    $rowData[] = $cell->getValue();
                }
            }
            $datasByLine[] = $rowData;
        }
    
        return $datasByLine;
    }

    private function detectDataStart($datas): int
    {
        $lineNumber = 0;
        foreach($datas as $line){
            if ($this->isDataLine($line)) {
                return $lineNumber;
            }
            $lineNumber++;
        }
        return -1;
    }

    private function isDataLine(array $line): bool
    {
        $looksLikeDate = false;
        $looksLikeAmount = false;
        foreach ($line as $cell) {
            $looksLikeDate = $looksLikeDate || $this->looksLikeDate($cell);
            $looksLikeAmount = $looksLikeAmount || $this->looksLikeAmount($cell);
        }

        return $looksLikeDate && $looksLikeAmount;
    }

    private function looksLikeDate($value): bool
    {
        $date = \DateTime::createFromFormat('Y-m-d', $value) ?: \DateTime::createFromFormat('d/m/Y', $value) ?: \DateTime::createFromFormat('d-m-Y', $value);
        return $date !== false;
    }

    private function looksLikeAmount($amount): bool
    {
        if ($amount === null) {
            return false;
        }
        $amount = str_replace(['€', '$', 'EUR', ' '], '', $amount);
        $amount = str_replace(',', '.', $amount);

        return is_numeric($amount);
    }
}
