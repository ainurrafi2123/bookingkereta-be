<?php

namespace App\Http\Controllers;

use App\Models\Petugas;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PetugasController extends Controller
{
    // GET semua petugas
    public function index()
    {
        return response()->json(Petugas::with('user')->get());
    }

    // GET petugas ID
    public function show($id)
    {
        $petugas = Petugas::with('user')->find($id);

        if (!$petugas) {
            return response()->json(['message' => 'Petugas not found'], 404);
        }

        return response()->json($petugas);
    }

    public function me(Request $request): JsonResponse
    {
        $petugas = $request->user()
            ->petugas()
            ->with('user')
            ->first();

        if (!$petugas) {
            return response()->json([
                'message' => 'Profil petugas tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'message' => 'Profil petugas berhasil diambil',
            'data' => $petugas
        ]);
    }

    public function update(Request $request, $id)
    {
        $petugas = Petugas::find($id);

        if (!$petugas) {
            return response()->json(['message' => 'Petugas not found'], 404);
        }

        $request->validate([
            'nama_petugas' => 'sometimes|nullable|string|max:255',
            'nik'          => 'sometimes|nullable|string|max:50|unique:petugas,nik,' . $id, 
            'alamat'       => 'sometimes|nullable|string',
            'no_hp'        => 'sometimes|nullable|string|max:20',
        ]);

        $petugas->update($request->all());

        return response()->json([
            'message' => 'Data petugas berhasil diperbarui',
            'data'    => $petugas->refresh()
        ], 200);
    }

    public function destroy($id)
    {
        $petugas = Petugas::find($id);

        if (!$petugas) {
            return response()->json(['message' => 'Petugas not found'], 404);
        }

        $petugas->delete();

        return response()->json(['message' => 'Petugas deleted successfully']);
    }
}
