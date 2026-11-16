<?php

namespace App\Http\Controllers;

class PriceIndexController extends Controller
{
    /**
     * Show the price index module.
     *
     * The comparison board (price board, competitiveness scorecard, item
     * comparison, trend chart, savings opportunities) was removed together
     * with all of its computation. The module is being repurposed into a
     * consolidated items summary; nothing has been built yet.
     */
    public function index()
    {
        return view('Priceindex.index');
    }
}
