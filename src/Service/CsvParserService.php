<?php

namespace App\Service;

use League\Csv\Reader;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class CsvParserService
{
    public function parseCsvFile(UploadedFile $file): array
    {
        $filePath = $file->getRealPath();
        $csvContent = file_get_contents($filePath);

        $encoding = mb_detect_encoding($csvContent, mb_list_encodings(), true);

        if ($encoding && strtoupper($encoding) !== 'UTF-8') {
            $csvContent = mb_convert_encoding($csvContent, 'UTF-8', $encoding);
            file_put_contents($filePath, $csvContent);
        }

        $csv = Reader::createFromPath($filePath, 'r');
        $csv->setDelimiter(';');

        $dataStartIndex = $this->detectDataStart($filePath);
        if ($dataStartIndex > 0) {
            $csv->setHeaderOffset($dataStartIndex - 1);
        } else {
            $csv->setHeaderOffset(null);
        }

        $headers = $csv->getHeader();
        
        $data = iterator_to_array($csv->getRecords());

        $data = array_filter($data, function ($value, $key) use ($dataStartIndex){
            return $key >= $dataStartIndex;
        }, ARRAY_FILTER_USE_BOTH);

        $formattedData = array_values(array_map(function($row) {
            
            $row["id"] = implode("_", $row);
            return array_map(function($cell) {
                if ($this->looksLikeDate($cell)) {
                    $date = \DateTime::createFromFormat('Y-m-d', $cell) ?: \DateTime::createFromFormat('d/m/Y', $cell);
                    return $date->format('d/m/Y');
                } elseif ($this->looksLikeAmount($cell)) {
                    $amount = preg_replace('/[^\d,.-]/', '', $cell);
                    $amount = str_replace(',', '.', $amount);
                    return floatval($amount);
                }
                return $cell;
            }, $row);
        }, $data));
    
        return ['headers' => $headers, 'datas' => $formattedData];
    }

    private function detectDataStart($filePath): int
    {
        $lineNumber = 0;
        $fileHandle = fopen($filePath, 'r');
    
        while (($line = fgets($fileHandle)) !== false) {
            $cells = str_getcsv($line, ';');
    
            if ($this->isDataLine($cells)) {
                fclose($fileHandle);
                return $lineNumber;
            }
    
            $lineNumber++;
        }
    
        fclose($fileHandle);
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
        $date = \DateTime::createFromFormat('Y-m-d', $value) ?: \DateTime::createFromFormat('d/m/Y', $value);
        return $date !== false;
    }

    private function looksLikeAmount($amount): bool
    {
        if ($amount === null) {
            return false;
        }
    
        $amount = preg_replace('/[^\d,.-]/', '', $amount);
        $amount = str_replace(',', '.', $amount);

        return is_numeric($amount) && strpos($amount, '.');
    }
}
