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
        try {
            $erdData = $this->erdDataGenerator->getErdDataSafely();
            
            return response()->json([
                'success' => true,
                'data' => $erdData,
            ]);
            
        } catch (\Exception $e) {
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
        try {
            $erdData = $this->erdDataGenerator->refreshErdData();
            
            return response()->json([
                'success' => true,
                'message' => 'ERD data refreshed successfully',
                'data' => $erdData,
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to refresh ERD data',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }
}