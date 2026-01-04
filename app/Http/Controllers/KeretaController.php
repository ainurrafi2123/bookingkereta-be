<?php

namespace App\Http\Controllers;

use App\Models\Kereta;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class KeretaController extends Controller
{
    public function index()
    {
        return response()->json(
            Kereta::all(),
            200
        );
    }

    public function show($id)
    {
        $kereta = Kereta::findOrFail($id);
        return response()->json($kereta, 200);
    }

  
    public function store(Request $request)
    {
        $request->validate([
            'nama_kereta' => 'required|string|max:255',
            'kelas_kereta' => 'required|in:ekonomi,bisnis,eksekutif',
            'deskripsi' => 'nullable|string',
        ]);

        $prefix = strtoupper(substr(str_replace(' ', '', $request->nama_kereta), 0, 3));
        $count = Kereta::count() + 1;
        $kodeKereta = $prefix . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);

        $kereta = Kereta::create([
            'kode_kereta' => $kodeKereta,
            'nama_kereta' => $request->nama_kereta,
            'kelas_kereta' => $request->kelas_kereta,
            'deskripsi' => $request->deskripsi,
        ]);

        return response()->json([
            'message' => 'Kereta berhasil dibuat',
            'data' => $kereta
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $kereta = Kereta::findOrFail($id);

        $request->validate([
            'nama_kereta' => 'required|string|max:255',
            'kelas_kereta' => 'required|in:ekonomi,bisnis,eksekutif',
            'deskripsi' => 'nullable|string',
        ]);

        $kereta->update([
            'nama_kereta' => $request->nama_kereta,
            'kelas_kereta' => $request->kelas_kereta,
            'deskripsi' => $request->deskripsi,
        ]);

        return response()->json([
            'message' => 'Kereta berhasil diperbarui',
            'data' => $kereta
        ], 200);
    }

    public function destroy($id)
    {
        $kereta = Kereta::findOrFail($id);
        $kereta->delete();

        return response()->json([
            'message' => 'Kereta berhasil dihapus'
        ], 200);
    }
}
