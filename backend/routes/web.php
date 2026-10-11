<?php

use Illuminate\Support\Facades\Route;

// This host only serves the JSON API (/api/v1/...) and uploaded media (/uploads/...).
Route::get('/', fn () => response()->json(['name' => config('app.name').' API', 'version' => 'v1', 'docs' => 'See API.md in the project repository.']));
