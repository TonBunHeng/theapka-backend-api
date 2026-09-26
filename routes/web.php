<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function (Request $request) {
    if ($request->wantsJson()) {
        return response()->json([
            'name' => config('app.name', 'TheapKa Online API'),
            'version' => '1.0.0',
            'status' => 'operational',
            'docs' => url('/api/docs.yaml'),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    return view('welcome');
});
