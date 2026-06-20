<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class ReportController extends BaseController
{
    /**
     * Generate ventes PDF using external renderer (headless Chrome)
     */
    public function ventesPdf(Request $request)
    {
        try {
            $ventes = DB::table('ventes')->get();
            return $this->generatePdfFromView('reports.ventes', ['ventes' => $ventes], 'ventes.pdf');
        } catch (\Exception $e) {
            if (config('app.debug')) {
                return response()->json([
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ], 500);
            }
            return response()->json(['message' => 'Erreur interne lors de la génération du PDF ventes.'], 500);
        }
    }

    /**
     * Generate stock PDF using external renderer (headless Chrome)
     */
    public function stockPdf(Request $request)
    {
        try {
            $produits = DB::table('produits')->get();
            return $this->generatePdfFromView('reports.stock', ['produits' => $produits], 'stock.pdf');
        } catch (\Exception $e) {
            if (config('app.debug')) {
                return response()->json([
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ], 500);
            }
            return response()->json(['message' => 'Erreur interne lors de la génération du PDF stock.'], 500);
        }
    }

    // --- HTML fallbacks (existing) ---
    public function stockHtml(Request $request)
    {
        $produits = DB::table('produits')->get();
        return view('reports.stock', compact('produits'));
    }

    public function ventesHtml(Request $request)
    {
        $ventes = DB::table('ventes')->get();
        return view('reports.ventes', compact('ventes'));
    }

    // --- Signed URL generators ---
    public function stockSigned(Request $request)
    {
        $url = URL::temporarySignedRoute('rapports.stock.public', now()->addMinutes(10));
        return response()->json(['url' => $url]);
    }

    public function ventesSigned(Request $request)
    {
        $url = URL::temporarySignedRoute('rapports.ventes.public', now()->addMinutes(10));
        return response()->json(['url' => $url]);
    }

    // --- Public signed endpoints ---
    public function stockHtmlPublic(Request $request)
    {
        $produits = DB::table('produits')->get();
        return view('reports.stock', compact('produits'));
    }

    public function ventesHtmlPublic(Request $request)
    {
        $ventes = DB::table('ventes')->get();
        return view('reports.ventes', compact('ventes'));
    }

    // --- Helper: render a Blade view to PDF using external Node renderer ---
    private function generatePdfFromView(string $view, array $data, string $filename)
    {
        // Ensure reports directory exists
        $reportsDir = storage_path('app/reports');
        if (!File::exists($reportsDir)) {
            File::makeDirectory($reportsDir, 0755, true);
        }

        $uid = Str::uuid()->toString();
        $htmlPath = $reportsDir . DIRECTORY_SEPARATOR . "report_{$uid}.html";
        $pdfPath = $reportsDir . DIRECTORY_SEPARATOR . "report_{$uid}.pdf";

        // Render view to HTML file
        $html = view($view, $data)->render();
        file_put_contents($htmlPath, $html);

        // Call Node renderer
        $nodeRenderer = base_path('report-renderer') . DIRECTORY_SEPARATOR . 'renderer.js';
        $process = new Process(['node', $nodeRenderer, '--input', $htmlPath, '--output', $pdfPath]);
        $process->setTimeout(120);
        $process->run();

        if (!$process->isSuccessful()) {
            // Clean up html
            @unlink($htmlPath);
            throw new \RuntimeException('Renderer failed: ' . $process->getErrorOutput());
        }

        // Stream PDF to client and delete temporary files afterwards
        return response()->download($pdfPath, $filename, [
            'Content-Type' => 'application/pdf'
        ])->deleteFileAfterSend(true);
    }
}
