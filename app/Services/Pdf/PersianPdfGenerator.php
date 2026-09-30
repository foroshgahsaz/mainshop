<?php

namespace App\Services\Pdf;

use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;

class PersianPdfGenerator
{
    public function download(ViewContract|string $view, array $data, string $filename): Response
    {
        $html = is_string($view) ? view($view, $data)->render() : $view->with($data)->render();

        $mpdf = $this->makeEngine();
        $mpdf->WriteHTML($html);

        $binary = $mpdf->Output('', Destination::STRING_RETURN);

        return response($binary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    protected function makeEngine(): Mpdf
    {
        $fontDir = resource_path('fonts');

        return new Mpdf([
            'tempDir' => $this->mpdfTempDirectory(),
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 12,
            'margin_bottom' => 12,
            'fontDir' => array_merge((new ConfigVariables)->getDefaults()['fontDir'], [$fontDir]),
            'fontdata' => array_merge((new FontVariables)->getDefaults()['fontdata'], [
                'vazirmatn' => [
                    'R' => 'Vazirmatn-Regular.ttf',
                ],
            ]),
            'default_font' => 'vazirmatn',
            'directionality' => 'rtl',
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
        ]);
    }

    /**
     * mPDF needs a writable temp directory; vendor/mpdf/tmp is not writable on many hosts.
     */
    protected function mpdfTempDirectory(): string
    {
        $directory = storage_path('app/mpdf');

        File::ensureDirectoryExists($directory, 0755, true);

        return $directory;
    }
}
