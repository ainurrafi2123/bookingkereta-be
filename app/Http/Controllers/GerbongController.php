<?php

namespace App\Http\Controllers;

use App\Models\Gerbong;
use Illuminate\Http\Request;

class GerbongController extends Controller
{
    public function index()
    {
        return response()->json(
            Gerbong::with('kereta')->get()
        );
    }

    public function show($id)
    {
        $gerbong = Gerbong::with('kereta')->findOrFail($id);
        return response()->json($gerbong);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_kereta'      => 'required|exists:kereta,id',
            'nama_gerbong'   => 'required|string|max:50',
            'kelas_gerbong'  => 'required|in:ekonomi,bisnis,eksekutif',
            'kuota'          => 'required|integer|min:1',
        ]);

        $gerbong = Gerbong::create($validated);

        return response()->json($gerbong, 201);
    }

    public function update(Request $request, $id)
    {
        $gerbong = Gerbong::findOrFail($id);

        $validated = $request->validate([
            'nama_gerbong'  => 'sometimes|string|max:50',
            'kelas_gerbong' => 'sometimes|in:ekonomi,bisnis,eksekutif',
            'kuota'         => 'sometimes|integer|min:1',
        ]);

        $gerbong->update($validated);

        return response()->json($gerbong);
    }

    public function destroy($id)
    {
        $gerbong = Gerbong::findOrFail($id);
        $gerbong->delete();

        return response()->json(['message' => 'Gerbong deleted']);
    }
}
