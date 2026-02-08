<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;

class DocumentTextExtractor
{
    /**
     * Extract plain text from uploaded file. Supports .txt, .pdf, .docx.
     * Returns trimmed string or empty string on failure.
     */
    public function extract(UploadedFile $file): string
    {
        $ext = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?? '');

        return match ($ext) {
            'txt' => $this->extractTxt($file),
            'pdf' => $this->extractPdf($file),
            'docx' => $this->extractDocx($file),
            default => '',
        };
    }

    private function extractTxt(UploadedFile $file): string
    {
        $content = file_get_contents($file->getRealPath());
        if ($content === false) {
            return '';
        }
        return $this->normalizeText($content);
    }

    private function extractPdf(UploadedFile $file): string
    {
        if (!class_exists(\Smalot\PdfParser\Parser::class)) {
            return '';
        }
        try {
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($file->getRealPath());
            $text = $pdf->getText();
            return $this->normalizeText($text ?? '');
        } catch (\Throwable $e) {
            return '';
        }
    }

    private function extractDocx(UploadedFile $file): string
    {
        $path = $file->getRealPath();
        if (!$path || !class_exists(\ZipArchive::class)) {
            return '';
        }
        $zip = new \ZipArchive();
        if ($zip->open($path, \ZipArchive::RDONLY) !== true) {
            return '';
        }
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();
        if ($xml === false || $xml === '') {
            return '';
        }
        if (preg_match_all('/<w:t[^>]*>([^<]*)<\/w:t>/u', $xml, $m)) {
            $text = implode(' ', $m[1]);
        } else {
            $text = strip_tags($xml);
        }
        $text = preg_replace('/\s+/u', ' ', $text);
        return $this->normalizeText($text);
    }

    private function normalizeText(string $text): string
    {
        $text = trim($text);
        $text = preg_replace('/\s+/u', ' ', $text);
        return mb_substr($text, 0, 120000);
    }
}
