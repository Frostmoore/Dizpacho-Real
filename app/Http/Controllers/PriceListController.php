<?php

namespace App\Http\Controllers;

use App\Models\PriceList;

class PriceListController extends Controller
{
    public function index()
    {
        $lists = PriceList::orderBy('updated_at','desc')->paginate(20);
        return view('pricelist.index', compact('lists'));
    }
}
