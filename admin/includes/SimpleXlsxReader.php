<?php
declare(strict_types=1);

/**
 * Minimal XLSX reader: extracts the FIRST sheet, by actual tab order (not by
 * filename inside the archive — xl/worksheets/sheet1.xml is not reliably the
 * first tab; the real order lives in xl/workbook.xml, resolved through
 * xl/_rels/workbook.xml.rels).
 *
 * No external dependencies — just ZipArchive and SimpleXML, both part of a
 * standard PHP build. Reads cell text/numeric values only: no formulas,
 * formatting, or merged-cell handling.
 */
class SimpleXlsxReader
{
    /** @return array<int, array<int, string|null>> Rows of cell values, 0-indexed columns. */
    public static function readFirstSheet(string $path): array
    {
        return self::readSheetByIndex($path, 0);
    }

    /**
     * Finds the first sheet (by tab order) whose name starts with $prefix
     * (case-insensitive) and reads it — e.g. "Approved" matches "Approved (116)"
     * regardless of the live count in the tab name.
     *
     * @return array<int, array<int, string|null>>
     * @throws RuntimeException if no sheet name matches.
     */
    public static function readSheetByNamePrefix(string $path, string $prefix): array
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new RuntimeException('Could not open the file as a .xlsx archive.');
        }

        try {
            $names = self::listSheetNames($zip);
            $needle = strtolower($prefix);
            $matchIndex = null;
            foreach ($names as $i => $name) {
                if (str_starts_with(strtolower(trim($name)), $needle)) {
                    $matchIndex = $i;
                    break;
                }
            }

            if ($matchIndex === null) {
                throw new RuntimeException("No sheet found starting with \"{$prefix}\". Sheets in this file: " . implode(', ', $names));
            }

            $sheetTarget = self::resolveSheetTargetByIndex($zip, $matchIndex);
            $sharedStrings = self::readSharedStrings($zip);
            return self::readSheetRows($zip, $sheetTarget, $sharedStrings);
        } finally {
            $zip->close();
        }
    }

    /** @return array<int, array<int, string|null>> */
    private static function readSheetByIndex(string $path, int $index): array
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new RuntimeException('Could not open the file as a .xlsx archive.');
        }

        try {
            $sheetTarget = self::resolveSheetTargetByIndex($zip, $index);
            $sharedStrings = self::readSharedStrings($zip);
            return self::readSheetRows($zip, $sheetTarget, $sharedStrings);
        } finally {
            $zip->close();
        }
    }

    /** @return array<int, string> Sheet names in actual tab order. */
    private static function listSheetNames(ZipArchive $zip): array
    {
        $workbook = self::xml($zip, 'xl/workbook.xml');
        if ($workbook === null) {
            throw new RuntimeException('xl/workbook.xml not found — is this a valid .xlsx file?');
        }

        $names = [];
        foreach ($workbook->sheets->sheet as $sheet) {
            $names[] = (string) $sheet['name'];
        }
        return $names;
    }

    private static function xml(ZipArchive $zip, string $name): ?SimpleXMLElement
    {
        $content = $zip->getFromName($name);
        if ($content === false) {
            return null;
        }
        return new SimpleXMLElement($content);
    }

    private static function resolveSheetTargetByIndex(ZipArchive $zip, int $index): string
    {
        $workbook = self::xml($zip, 'xl/workbook.xml');
        if ($workbook === null) {
            throw new RuntimeException('xl/workbook.xml not found — is this a valid .xlsx file?');
        }

        $namespaces = $workbook->getNamespaces(true);
        $rNamespace = $namespaces['r'] ?? 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';

        $sheets = $workbook->sheets->sheet;
        if (!isset($sheets[$index])) {
            throw new RuntimeException("No sheet at tab position {$index}.");
        }

        $targetSheet = $sheets[$index];
        $rId = (string) $targetSheet->attributes($rNamespace)['id'];

        $rels = self::xml($zip, 'xl/_rels/workbook.xml.rels');
        if ($rels === null) {
            throw new RuntimeException('Workbook relationships not found.');
        }

        foreach ($rels->Relationship as $rel) {
            if ((string) $rel['Id'] === $rId) {
                return 'xl/' . ltrim((string) $rel['Target'], '/');
            }
        }

        throw new RuntimeException('Could not resolve the target sheet.');
    }

    /** @return array<int, string> */
    private static function readSharedStrings(ZipArchive $zip): array
    {
        $xml = self::xml($zip, 'xl/sharedStrings.xml');
        if ($xml === null) {
            return [];
        }

        $strings = [];
        foreach ($xml->si as $si) {
            if (isset($si->t)) {
                $strings[] = (string) $si->t;
            } else {
                $text = '';
                foreach ($si->r as $run) {
                    $text .= (string) $run->t;
                }
                $strings[] = $text;
            }
        }

        return $strings;
    }

    private static function columnLetterToIndex(string $ref): int
    {
        preg_match('/^([A-Z]+)/', $ref, $m);
        $letters = $m[1] ?? 'A';
        $index = 0;
        foreach (str_split($letters) as $char) {
            $index = $index * 26 + (ord($char) - ord('A') + 1);
        }
        return $index - 1;
    }

    /** @param array<int, string> $sharedStrings @return array<int, array<int, string|null>> */
    private static function readSheetRows(ZipArchive $zip, string $sheetPath, array $sharedStrings): array
    {
        $xml = self::xml($zip, $sheetPath);
        if ($xml === null) {
            throw new RuntimeException("Sheet not found: {$sheetPath}");
        }

        $rows = [];
        foreach ($xml->sheetData->row as $row) {
            $rowValues = [];
            foreach ($row->c as $cell) {
                $ref = (string) $cell['r'];
                $colIndex = self::columnLetterToIndex($ref);
                $type = (string) $cell['t'];

                $value = null;
                if ($type === 's') {
                    $idx = (int) $cell->v;
                    $value = $sharedStrings[$idx] ?? null;
                } elseif ($type === 'inlineStr') {
                    $value = (string) $cell->is->t;
                } elseif (isset($cell->v)) {
                    $value = (string) $cell->v;
                }

                $rowValues[$colIndex] = $value;
            }

            if (empty($rowValues)) {
                $rows[] = [];
                continue;
            }

            $maxCol = max(array_keys($rowValues));
            $normalized = [];
            for ($i = 0; $i <= $maxCol; $i++) {
                $normalized[] = $rowValues[$i] ?? null;
            }
            $rows[] = $normalized;
        }

        return $rows;
    }
}