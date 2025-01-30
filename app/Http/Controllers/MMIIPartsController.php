<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\MMIIService;

class MMIIPartsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $mmiiService = new MMIIService();

        return $mmiiService->getAvailablePartsWithAssets();
    }

    public function indexBackgrounds()
    {
        $mmiiService = new MMIIService();

        return $mmiiService->getBackgroundsFiles();
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }
}
