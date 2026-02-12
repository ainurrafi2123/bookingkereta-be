<?php

namespace App\Http\Controllers;

use App\Models\Gerbong;
use App\Models\DetailPembelianTiket;
use Illuminate\Http\Request;

class GerbongController extends Controller
{
    /**
     * GET /gerbong
     * Optional filter: ?id_kereta={id}
     */
    public function index(Request $request)
    {
        $query = Gerbong::with(['kereta', 'kursi'])
            ->withCount('kursi as kursi_count');

        if ($request->has('id_kereta')) {
            $query->where('id_kereta', $request->id_kereta);
        }

        $gerbongs = $query->get();

        //  Compute status jika ada filter jadwal
        if ($request->has('jadwal_id')) {
            $jadwalId = $request->jadwal_id;

            $gerbongs->each(function($gerbong) use ($jadwalId) {
                $kursiIds = $gerbong->kursi->pluck('id');

                // Kursi yang booked untuk jadwal ini
                $bookedCount = DetailPembelianTiket::whereIn('id_kursi', $kursiIds)
                    ->whereHas('pembelianTiket', function($q) use ($jadwalId) {
                        $q->where('id_jadwal_kereta', $jadwalId)
                          ->where('status', 'booked');
                    })
                    ->count();

                $gerbong->available_count = $gerbong->kursi_count - $bookedCount;
                $gerbong->booked_count = $bookedCount;
                $gerbong->jadwal_id = (int) $jadwalId;

                unset($gerbong->kursi);
            });
        } else {
            // Tanpa jadwal: hanya tampilkan total kursi
            $gerbongs->each(function($gerbong) {
                unset($gerbong->kursi);
            });
        }

        return response()->json($gerbongs);
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