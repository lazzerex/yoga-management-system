<?php

namespace App\Support\Pdf;

use App\Support\Settings;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class DocumentPdf
{
    /**
     * @param  array<string, mixed>  $data
     */
    public static function download(string $view, array $data, string $title, string $filename, ?string $subtitle = null): Response
    {
        return Pdf::loadView($view, $data + [
            'centreName' => Settings::get('centre.name') ?: config('app.name'),
            'documentTitle' => $title,
            'documentSubtitle' => $subtitle,
            'generatedAt' => now()->format('d/m/Y H:i'),
            'money' => fn (int $amount) => number_format($amount, 0, ',', '.').' ₫',
        ])->download($filename);
    }
}
