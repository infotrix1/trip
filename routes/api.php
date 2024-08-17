<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\Destination;
use Illuminate\Support\Facades\Auth;

Route::middleware('auth:sanctum')->group(function () {
    // Fetch all destinations for the authenticated user
    Route::get('/destinations', function () {
        return Destination::where('user_id', Auth::id())->get();
    });

    // Add a new destination for the authenticated user
    Route::post('/destinations', function (Request $request) {
        $request->validate([
            'name' => 'required|string|max:255',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $destination = Destination::create([
            'name' => $request->name,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'user_id' => Auth::id(),
        ]);

        return response()->json($destination, 201);
    });

    // Delete a destination for the authenticated user
    Route::delete('/destinations/{id}', function ($id) {
        $destination = Destination::where('id', $id)->where('user_id', Auth::id())->first();

        if ($destination) {
            $destination->delete();
            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false], 404);
    });
});
