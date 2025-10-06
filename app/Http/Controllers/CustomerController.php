<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        // opzionale: ricerca veloce per nome/email/telefono
        $q = trim((string)$request->get('q', ''));

        $customers = User::whereNotIn('role', ['admin','operator'])
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($qq) use ($q) {
                    $qq->where('name', 'like', "%{$q}%")
                       ->orWhere('email', 'like', "%{$q}%")
                       ->orWhere('phone', 'like', "%{$q}%");
                });
            })
            ->orderBy('created_at','desc')
            ->paginate(20)
            ->withQueryString();

        return view('customers.index', compact('customers', 'q'));
    }
}
