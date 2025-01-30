<?php

namespace App\Http\Controllers;

use App\Models\CardInstance;
use App\Models\CardVersion;
use App\Models\User;
use Illuminate\Http\Request;

class CollectionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return auth()->user()
                    ->collection()
                    ->groupByCardVersion()
                    ->orderByCardAttributes()
                    ->get();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(CardVersion $cardVersion)
    {
        return auth()->user()->collection()
                            ->where('card_version_id', $cardVersion->id)
                            ->first()
                            ->cardVersion()
                            ->with('cardTemplate', 'cardTemplate.mmii')
                            ->first();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CardInstance $cardInstance)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CardInstance $cardInstance)
    {
        //
    }
}
