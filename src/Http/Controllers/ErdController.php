<?php

namespace Looaf\LaravelErd\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Looaf\LaravelErd\Services\ErdDataGenerator;

class ErdController extends Controller
{
    protected ErdDataGenerator $erdDataGenerator;

    public function __construct(ErdDataGenerator $erdDataGenerator)
    {
        $this->erdDataGenerator = $erdDataGenerator;
    }

    /**
     * Display the ERD interface
     * 
     * @return View
     */
    public function index(): View
    {
        return view('erd::erd');
    }

    /**
     * Return ERD data as JSON for frontend consumption
     * 
     * @return JsonResponse
     */
    public function data(): JsonResponse
    {
        \Log::info('🔄 ERD data endpoint called');
        \Log::info('📍 Request URL: ' . request()->fullUrl());
        \Log::info('🔑 Request headers: ' . json_encode(request()->headers->all()));

        try {
            \Log::info('⚙️ Calling ErdDataGenerator->getErdDataSafely()');
            $erdData = $this->erdDataGenerator->getErdDataSafely();

            \Log::info('✅ ERD data generated successfully');
            \Log::info('📊 Tables count: ' . count($erdData['tables'] ?? []));
            \Log::info('🔗 Relationships count: ' . count($erdData['relationships'] ?? []));
            \Log::info('📋 Sample table names: ' . json_encode(array_slice(array_column($erdData['tables'] ?? [], 'name'), 0, 5)));

            $response = response()->json([
                'success' => true,
                'data' => $erdData,
            ]);

            \Log::info('📤 Sending successful response');
            return $response;
        } catch (\Exception $e) {
            \Log::error('💥 Exception in ERD data endpoint: ' . $e->getMessage());
            \Log::error('📍 Exception file: ' . $e->getFile() . ':' . $e->getLine());
            \Log::error('🔍 Exception trace: ' . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate ERD data',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Refresh ERD data by clearing cache and regenerating
     * 
     * @return JsonResponse
     */
    public function refresh(): JsonResponse
    {
        \Log::info('🔄 ERD refresh endpoint called');
        \Log::info('📍 Request method: ' . request()->method());
        \Log::info('🔑 CSRF token present: ' . (request()->header('X-CSRF-TOKEN') ? 'Yes' : 'No'));

        try {
            \Log::info('🧹 Calling ErdDataGenerator->refreshErdData() to clear cache and regenerate');
            $erdData = $this->erdDataGenerator->refreshErdData();

            \Log::info('✅ ERD data refreshed successfully');
            \Log::info('📊 Refreshed tables count: ' . count($erdData['tables'] ?? []));
            \Log::info('🔗 Refreshed relationships count: ' . count($erdData['relationships'] ?? []));

            return response()->json([
                'success' => true,
                'message' => 'ERD data refreshed successfully',
                'data' => $erdData,
            ]);
        } catch (\Exception $e) {
            \Log::error('💥 Exception in ERD refresh endpoint: ' . $e->getMessage());
            \Log::error('📍 Exception file: ' . $e->getFile() . ':' . $e->getLine());
            \Log::error('🔍 Exception trace: ' . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Failed to refresh ERD data',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }
}
