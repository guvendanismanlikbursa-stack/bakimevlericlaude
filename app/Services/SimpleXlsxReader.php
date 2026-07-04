<?php

namespace App\Services;

use RuntimeException;
use ZipArchive;

class SimpleXlsxReader
{
    public function rows(string $path): array
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('PHP ZipArchive eklentisi gerekli.');
        }

        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            throw new RuntimeException('Excel dosyasÇ aÇÇlamadÇ.');
        }

        $sharedStrings = $this->sharedStrings($zip);
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        if ($sheetXml === false) {
            $zip->close();
            throw new RuntimeException('Excel iÇinde sheet1 bulunamadÇ.');
        }

        $xml = simplexml_load_string($sheetXml);
        $rows = [];

        foreach ($xml->sheetData->row as $row) {
            $values = [];
            foreach ($row->c as $cell) {
                $ref = (string) $cell['r'];
                $index = $this->columnIndex($ref);
                $type = (string) $cell['t'];
                $values[$index] = $this->cellValue($cell, $type, $sharedStrings);
            }
            if ($values) {
                ksort($values);
                $rows[] = $values;
            }
        }

        $zip->close();

        return $rows;
    }


    private function cellValue(\SimpleXMLElement $cell, string $type, array $sharedStrings): string
    {
        if ($type === 'inlineStr') {
            if (isset($cell->is->t)) {
                return (string) $cell->is->t;
            }

            $parts = [];
            foreach ($cell->is->r ?? [] as $run) {
                $parts[] = (string) $run->t;
            }

            return implode('', $parts);
        }

        $raw = isset($cell->v) ? (string) $cell->v : '';

        return $type === 's' ? ($sharedStrings[(int) $raw] ?? '') : $raw;
    }

    private function sharedStrings(ZipArchive $zip): array
    {
        $xmlString = $zip->getFromName('xl/sharedStrings.xml');
        if ($xmlString === false) {
            return [];
        }

        $xml = simplexml_load_string($xmlString);
        $strings = [];
        foreach ($xml->si as $si) {
            if (isset($si->t)) {
                $strings[] = (string) $si->t;
                continue;
            }
            $parts = [];
            foreach ($si->r as $run) {
                $parts[] = (string) $run->t;
            }
            $strings[] = implode('', $parts);
        }

        return $strings;
    }

    private function columnIndex(string $cellRef): int
    {
        preg_match('/^[A-Z]+/', $cellRef, $matches);
        $letters = $matches[0] ?? 'A';
        $index = 0;
        foreach (str_split($letters) as $letter) {
            $index = ($index * 26) + (ord($letter) - 64);
        }

        return $index - 1;
    }
}
