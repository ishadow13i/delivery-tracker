<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PdfUploadController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'pdf' => 'required|file|mimes:pdf|max:20480',
        ]);

        $dir = storage_path('app/pdf-imports');
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $token = (string) Str::uuid();
        $destination = $dir . '/' . $token . '.pdf';

        $request->file('pdf')->move($dir, $token . '.pdf');

        return response()->json([
            'ok' => true,
            'token' => $token,
            'size' => filesize($destination),
            'name' => $request->file('pdf')?->getClientOriginalName() ?? '',
        ]);
    }
}
