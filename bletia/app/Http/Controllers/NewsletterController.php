<?php

namespace App\Http\Controllers;

use App\Models\NewsletterSuscriptor;
use Illuminate\Http\Request;

class NewsletterController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate(['email' => 'required|email|max:191']);
        NewsletterSuscriptor::firstOrCreate(['email' => $data['email']]);
        return back()->with('ok', '¡Gracias! Te suscribiste al boletín.');
    }
}
