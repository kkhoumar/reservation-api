<?php

namespace App\Support;

use DateTimeInterface;
use RuntimeException;
use ZipArchive;

/**
 * Petit fabricant de fichiers Excel (.xlsx), sans bibliothèque en plus.
 *
 * Un vrai fichier Excel s'ouvre correctement partout : Excel, Google Sheets,
 * WPS et Excel sur téléphone (un CSV, lui, tombe souvent dans une seule colonne).
 * Les lignes sont écrites au fil de l'eau dans un fichier temporaire : même
 * un export de 2 ans ne remplit pas la mémoire.
 *
 * Types de colonnes : text, int, money, date, time, datetime.
 * Les montants et dates restent de vrais nombres (calculables et triables).
 */
class XlsxWriter
{
    // Styles (index dans cellXfs de styles.xml)
    private const S_TEXT = 0;
    private const S_HEAD = 1;
    private const S_MONEY = 2;
    private const S_DATE = 3;
    private const S_DATETIME = 4;
    private const S_TIME = 5;
    private const S_INT = 6;
    private const S_TITLE = 7;
    private const S_BOLD_MONEY = 8;
    private const S_BOLD = 9;
    private const S_NOTE = 10;

    /** @var list<array{name: string, file: string}> */
    private array $sheets = [];

    private string $dir;

    public function __construct()
    {
        $this->dir = sys_get_temp_dir() . '/xlsx-' . bin2hex(random_bytes(6));

        if (! @mkdir($this->dir, 0700, true) && ! is_dir($this->dir)) {
            throw new RuntimeException('Dossier temporaire impossible à créer.');
        }
    }

    /**
     * Onglet en tableau : une ligne d'en-têtes, puis les lignes.
     *
     * @param  list<array{0: string, 1: string}>  $columns  [libellé, type]
     * @param  iterable<array<int, mixed>>  $rows
     */
    public function addTable(string $name, array $columns, iterable $rows): void
    {
        $file = $this->newSheetFile($name);
        $fh = fopen($file, 'w');
        $count = count($columns);

        // Largeur de chaque colonne d'après le type et le libellé
        $cols = '';
        foreach ($columns as $i => [$label, $type]) {
            $width = max(mb_strlen($label) + 3, match ($type) {
                'money' => 13, 'int' => 9, 'date' => 12, 'time' => 8, 'datetime' => 17, default => 18,
            });
            $cols .= '<col min="' . ($i + 1) . '" max="' . ($i + 1) . '" width="' . min($width, 45) . '" customWidth="1"/>';
        }

        fwrite($fh, $this->sheetHead(frozen: true) . '<cols>' . $cols . '</cols><sheetData>');

        $r = 1;
        fwrite($fh, $this->row($r, array_map(fn ($c) => [$c[0], self::S_HEAD, 'text'], $columns)));

        foreach ($rows as $values) {
            $r++;
            $cells = [];
            foreach ($columns as $i => [, $type]) {
                $cells[] = [$values[$i] ?? null, $this->styleFor($type), $type];
            }
            fwrite($fh, $this->row($r, $cells));
        }

        $ref = 'A1:' . self::col($count - 1) . max(1, $r);
        fwrite($fh, '</sheetData><autoFilter ref="' . $ref . '"/>' . $this->sheetFoot());
        fclose($fh);
    }

    /**
     * Onglet « Résumé » : un titre, puis des lignes libellé / valeur.
     *
     * @param  list<array{0: string, 1?: mixed, 2?: string}>  $lines  [libellé, valeur, style: money|int|text|title|bold|note|blank]
     */
    public function addSummary(string $name, array $lines): void
    {
        $file = $this->newSheetFile($name);
        $xml = $this->sheetHead(frozen: false)
            . '<cols><col min="1" max="1" width="46" customWidth="1"/><col min="2" max="2" width="22" customWidth="1"/></cols><sheetData>';

        foreach ($lines as $i => $line) {
            $label = $line[0] ?? '';
            $value = $line[1] ?? null;
            $kind = $line[2] ?? (is_int($value) ? 'money' : 'text');
            $r = $i + 1;

            $cells = match ($kind) {
                'title' => [[$label, self::S_TITLE, 'text']],
                'note' => [[$label, self::S_NOTE, 'text']],
                'blank' => [],
                'bold' => [[$label, self::S_BOLD, 'text'], [$value, self::S_BOLD_MONEY, 'money']],
                'int' => [[$label, self::S_TEXT, 'text'], [$value, self::S_INT, 'int']],
                'text' => [[$label, self::S_TEXT, 'text'], [$value, self::S_TEXT, 'text']],
                default => [[$label, self::S_TEXT, 'text'], [$value, self::S_MONEY, 'money']],
            };
            $xml .= $this->row($r, $cells);
        }

        file_put_contents($file, $xml . '</sheetData>' . $this->sheetFoot());
    }

    /** Assemble le fichier .xlsx et renvoie son chemin (à supprimer après l'envoi). */
    public function save(): string
    {
        if ($this->sheets === []) {
            $this->addSummary('Vide', [['Aucune donnée']]);
        }

        $path = $this->dir . '/export.xlsx';
        $zip = new ZipArchive;

        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Fichier Excel impossible à créer.');
        }

        $overrides = '';
        $sheetsXml = '';
        $rels = '';
        foreach ($this->sheets as $i => $sheet) {
            $n = $i + 1;
            $zip->addFile($sheet['file'], "xl/worksheets/sheet{$n}.xml");
            $overrides .= '<Override PartName="/xl/worksheets/sheet' . $n . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
            $sheetsXml .= '<sheet name="' . self::esc($sheet['name']) . '" sheetId="' . $n . '" r:id="rId' . $n . '"/>';
            $rels .= '<Relationship Id="rId' . $n . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $n . '.xml"/>';
        }
        $stylesId = count($this->sheets) + 1;

        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . $overrides . '</Types>');

        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>');

        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<bookViews><workbookView activeTab="0"/></bookViews><sheets>' . $sheetsXml . '</sheets></workbook>');

        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' . $rels
            . '<Relationship Id="rId' . $stylesId . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>');

        $zip->addFromString('xl/styles.xml', self::styles());
        $zip->close();

        // Le fichier final sort du dossier de travail, qui est vidé tout de suite
        $final = sys_get_temp_dir() . '/maquis-export-' . bin2hex(random_bytes(8)) . '.xlsx';
        rename($path, $final);
        $this->cleanup();

        return $final;
    }

    /** Supprime les fichiers temporaires. */
    public function cleanup(): void
    {
        foreach (glob($this->dir . '/*') ?: [] as $f) {
            @unlink($f);
        }
        @rmdir($this->dir);
    }

    public function __destruct()
    {
        $this->cleanup();
    }

    // ------------------------------------------------------------------

    private function newSheetFile(string $name): string
    {
        // Nom d'onglet : 31 caractères au plus, sans : \ / ? * [ ], unique
        $clean = trim(mb_substr(str_replace([':', '\\', '/', '?', '*', '[', ']'], ' ', $name), 0, 31)) ?: 'Feuille';
        $base = $clean;
        $k = 2;
        while (in_array(mb_strtolower($clean), array_map(fn ($s) => mb_strtolower($s['name']), $this->sheets), true)) {
            $clean = mb_substr($base, 0, 28) . ' ' . $k++;
        }

        $file = $this->dir . '/sheet' . (count($this->sheets) + 1) . '.xml';
        $this->sheets[] = ['name' => $clean, 'file' => $file];

        return $file;
    }

    private function sheetHead(bool $frozen): string
    {
        $pane = $frozen ? '<pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/><selection pane="bottomLeft" activeCell="A2" sqref="A2"/>' : '';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheetViews><sheetView workbookViewId="0"' . (count($this->sheets) === 1 ? ' tabSelected="1"' : '') . '>' . $pane . '</sheetView></sheetViews>'
            . '<sheetFormatPr defaultRowHeight="16"/>';
    }

    private function sheetFoot(): string
    {
        return '<pageMargins left="0.5" right="0.5" top="0.6" bottom="0.6" header="0.3" footer="0.3"/></worksheet>';
    }

    private function styleFor(string $type): int
    {
        return match ($type) {
            'money' => self::S_MONEY,
            'int' => self::S_INT,
            'date' => self::S_DATE,
            'time' => self::S_TIME,
            'datetime' => self::S_DATETIME,
            default => self::S_TEXT,
        };
    }

    /** @param  list<array{0: mixed, 1: int, 2: string}>  $cells */
    private function row(int $r, array $cells): string
    {
        $xml = '<row r="' . $r . '">';

        foreach ($cells as $i => [$value, $style, $type]) {
            $ref = self::col($i) . $r;

            if ($value === null || $value === '') {
                $xml .= $style !== self::S_TEXT ? '<c r="' . $ref . '" s="' . $style . '"/>' : '';

                continue;
            }

            if ($value instanceof DateTimeInterface) {
                $serial = self::serial($value);
                if ($type === 'time') {
                    $serial -= floor($serial);
                } elseif ($type === 'date') {
                    $serial = floor($serial);
                }
                $xml .= '<c r="' . $ref . '" s="' . $style . '"><v>' . self::num($serial) . '</v></c>';

                continue;
            }

            if ((is_int($value) || is_float($value)) && $type !== 'text') {
                $xml .= '<c r="' . $ref . '" s="' . $style . '"><v>' . self::num($value) . '</v></c>';

                continue;
            }

            // Texte : jamais interprété comme une formule (pas d'injection possible)
            $xml .= '<c r="' . $ref . '" t="inlineStr" s="' . ($style === self::S_MONEY || $style === self::S_INT ? self::S_TEXT : $style) . '"><is><t xml:space="preserve">'
                . self::esc((string) $value) . '</t></is></c>';
        }

        return $xml . '</row>';
    }

    /** Date et heure affichées (heure locale du maquis) en nombre de jours Excel. */
    private static function serial(DateTimeInterface $d): float
    {
        $wall = gmmktime((int) $d->format('H'), (int) $d->format('i'), (int) $d->format('s'), (int) $d->format('n'), (int) $d->format('j'), (int) $d->format('Y'));

        return $wall / 86400 + 25569;
    }

    private static function num(int|float $v): string
    {
        return is_int($v) ? (string) $v : rtrim(rtrim(sprintf('%.10F', $v), '0'), '.');
    }

    /** Colonne 0 → A, 25 → Z, 26 → AA. */
    public static function col(int $i): string
    {
        $s = '';
        for ($i++; $i > 0; $i = intdiv($i - 1, 26)) {
            $s = chr(65 + ($i - 1) % 26) . $s;
        }

        return $s;
    }

    private static function esc(string $s): string
    {
        // Retire les caractères de contrôle interdits en XML
        $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $s) ?? '';

        return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private static function styles(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<numFmts count="4">'
            . '<numFmt numFmtId="164" formatCode="#,##0"/>'
            . '<numFmt numFmtId="165" formatCode="dd/mm/yyyy"/>'
            . '<numFmt numFmtId="166" formatCode="dd/mm/yyyy hh:mm"/>'
            . '<numFmt numFmtId="167" formatCode="hh:mm"/>'
            . '</numFmts>'
            . '<fonts count="4">'
            . '<font><sz val="11"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="14"/><color rgb="FF0B4D3C"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><name val="Calibri"/></font>'
            . '</fonts>'
            . '<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FF0B4D3C"/><bgColor indexed="64"/></patternFill></fill></fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="11">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'                                                  // 0 texte
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/>'                     // 1 en-tête
            . '<xf numFmtId="164" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'                         // 2 montant
            . '<xf numFmtId="165" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'                         // 3 date
            . '<xf numFmtId="166" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'                         // 4 date + heure
            . '<xf numFmtId="167" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'                         // 5 heure
            . '<xf numFmtId="1" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'                           // 6 entier
            . '<xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1"/>'                                   // 7 titre
            . '<xf numFmtId="164" fontId="3" fillId="0" borderId="0" xfId="0" applyFont="1" applyNumberFormat="1"/>'           // 8 montant gras
            . '<xf numFmtId="0" fontId="3" fillId="0" borderId="0" xfId="0" applyFont="1"/>'                                   // 9 texte gras
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment wrapText="1"/></xf>' // 10 note
            . '</cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>';
    }
}
