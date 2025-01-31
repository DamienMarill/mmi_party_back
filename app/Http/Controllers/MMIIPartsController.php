<?php

namespace App\Http\Controllers;

use App\Models\Mmii;
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

    public function update()
    {
        $mmii = auth()->user()->mmii()->first();
        Mmii::updateOrCreate(['id'=>$mmii->id], request()->all());
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

    public function updateBackgrounds()
    {
        $mmii = auth()->user()->mmii()->get();
        Mmii::createOrUpdate($mmii, request()->all());
    }
}
