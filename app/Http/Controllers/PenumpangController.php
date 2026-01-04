<?php

namespace App\Http\Controllers;

use App\Models\Penumpang;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PenumpangController extends Controller
{
    // GET semua penumpang
    public function index(): JsonResponse
    {
        $penumpang = Penumpang::with('user')->get();
        
        return response()->json([
            'message' => 'Data penumpang berhasil diambil',
            'data' => $penumpang
        ]);
    }

    // GET penumpang by ID
    public function show($id): JsonResponse
    {
        $penumpang = Penumpang::with('user')->find($id);

        if (!$penumpang) {
            return response()->json([
                'message' => 'Penumpang tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'message' => 'Data penumpang ditemukan',
            'data' => $penumpang
        ]);
    }

        // GET penumpang yang terhubung dengan id
    public function myPenumpang(Request $request)
    {
        $userId = $request->user()->id;

        $penumpang = Penumpang::whereIn('id', function ($query) use ($userId) {
            $query->select('detail_pembelian_tiket.id_penumpang')
                ->from('detail_pembelian_tiket')
                ->join('pembelian_tiket', 'pembelian_tiket.id', '=', 'detail_pembelian_tiket.id_pembelian_tiket')
                ->where('pembelian_tiket.id_user', $userId);
        })->get();

        return response()->json([
            'message' => 'Daftar penumpang milik user',
            'data' => $penumpang
        ]);
    }


    public function me(Request $request): JsonResponse
    {
        $penumpang = $request->user()
            ->penumpang()
            ->with('user')
            ->first();

        if (!$penumpang) {
            return response()->json([
                'message' => 'Profil penumpang tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'message' => 'Profil penumpang berhasil diambil',
            'data' => $penumpang
        ]);
    }


    // UPDATE penumpang
    public function update(Request $request, $id): JsonResponse
    {
        $penumpang = Penumpang::find($id);

        if (!$penumpang) {
            return response()->json([
                'message' => 'Penumpang tidak ditemukan'
            ], 404);
        }

        $validated = $request->validate([
            'nama_penumpang' => 'sometimes|nullable|string|max:255',
            'nik'            => 'sometimes|nullable|string|max:50|unique:penumpang,nik,' . $id,
            'alamat'         => 'sometimes|nullable|string',
            'no_hp'          => 'sometimes|nullable|string|max:20',
        ]);

        $penumpang->update($validated);

        return response()->json([
            'message' => 'Data penumpang berhasil diperbarui',
            'data' => $penumpang->fresh(['user'])
        ]);
    }

    // DELETE penumpang
    public function destroy($id): JsonResponse
    {
        $penumpang = Penumpang::find($id);

        if (!$penumpang) {
            return response()->json([
                'message' => 'Penumpang tidak ditemukan'
            ], 404);
        }

        $penumpang->delete();

        return response()->json([
            'message' => 'Penumpang berhasil dihapus'
        ]);
    }
}