<?php

namespace App\Http\Controllers;

use App\Models\Kursi;
use App\Models\Gerbong;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KursiController extends Controller
{
    /**
     * List kursi (optional filter by gerbong)
     */
    public function index(Request $request)
    {
        $query = Kursi::with('gerbong');

        if ($request->has('id_gerbong')) {
            $query->where('id_gerbong', $request->id_gerbong);
        }

        return response()->json($query->get());
    }

    /**
     * Detail kursi
     */
    public function show($id)
    {
        return response()->json(
            Kursi::with('gerbong')->findOrFail($id)
        );
    }

    /**
     * Generate kursi otomatis berdasarkan gerbong
     */
    public function generateByGerbong($id_gerbong)
    {
        $gerbong = Gerbong::with('kursi')->findOrFail($id_gerbong);

        // Cegah generate ulang
        if ($gerbong->kursi->count() > 0) {
            return response()->json([
                'message' => 'Kursi sudah digenerate',
                'existing_count' => $gerbong->kursi->count()
            ], 400);
        }

        if ($gerbong->kuota <= 0) {
            return response()->json([
                'message' => 'Kuota gerbong tidak valid'
            ], 400);
        }

        $layout = $this->getLayoutByKelas($gerbong->kelas_gerbong);
        $kolom = $layout['kolom'];
        $kursiPerBaris = count($kolom);

        $data = [];

        for ($i = 0; $i < $gerbong->kuota; $i++) {
            $baris = floor($i / $kursiPerBaris) + 1;
            $huruf = $kolom[$i % $kursiPerBaris];

            $data[] = [
                'id_gerbong' => $gerbong->id,
                'no_kursi'   => $baris . $huruf,
                'baris'      => $baris,
                'kolom'      => $huruf,
                'status'     => 'available',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::transaction(function () use ($data) {
            Kursi::insert($data);
        });

        return response()->json([
            'message' => 'Kursi berhasil digenerate',
            'gerbong' => $gerbong->nama_gerbong,
            'kelas' => $gerbong->kelas_gerbong,
            'total_kursi' => count($data),
            'layout' => $layout['name'],
        ], 201);
    }

    /**
     * Reset kursi (aman: tidak boleh jika sudah booked)
     */
    public function resetKursi($id_gerbong)
    {
        $gerbong = Gerbong::with('kursi')->findOrFail($id_gerbong);

        if ($gerbong->kursi()->where('status', 'booked')->exists()) {
            return response()->json([
                'message' => 'Tidak bisa reset, ada kursi yang sudah dibeli'
            ], 400);
        }

        $deleted = $gerbong->kursi()->delete();

        return response()->json([
            'message' => 'Kursi berhasil direset',
            'deleted_count' => $deleted
        ]);
    }

    /**
     * Seat map per gerbong
     */
    public function getSeatMap($id_gerbong)
    {
        $gerbong = Gerbong::with('kursi')->findOrFail($id_gerbong);

        $seatMap = $gerbong->kursi
            ->groupBy('baris')
            ->map(fn ($row) => $row->sortBy('kolom')->values());

        return response()->json([
            'gerbong' => $gerbong->nama_gerbong,
            'kelas' => $gerbong->kelas_gerbong,
            'total_kursi' => $gerbong->kursi->count(),
            'seat_map' => $seatMap
        ]);
    }

    /**
     * Layout kursi berdasarkan kelas gerbong
     */
    private function getLayoutByKelas($kelas)
    {
        $layouts = [
            'eksekutif' => [
                'name' => '2-2',
                'kolom' => ['A', 'B', 'C', 'D']
            ],
            'bisnis' => [
                'name' => '2-2',
                'kolom' => ['A', 'B', 'C', 'D']
            ],
            'ekonomi' => [
                'name' => '3-3',
                'kolom' => ['A', 'B', 'C', 'D', 'E', 'F']
            ],
        ];

        return $layouts[$kelas] ?? $layouts['ekonomi'];
    }
}
