<?php

namespace App\Http\Controllers;

use App\Models\Kursi;
use App\Models\Gerbong;
use App\Models\DetailPembelianTiket;
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
     * Reset kursi (aman: tidak boleh jika sudah booked untuk jadwal manapun)
     */
    public function resetKursi($id_gerbong)
    {
        $gerbong = Gerbong::with('kursi')->findOrFail($id_gerbong);

        // UPDATE: Cek apakah ada kursi yang sedang di-booking (untuk jadwal manapun)
        $kursiIds = $gerbong->kursi->pluck('id');
        
        $hasActiveBooking = DetailPembelianTiket::whereIn('id_kursi', $kursiIds)
            ->whereHas('pembelianTiket', function($q) {
                $q->where('status', 'booked'); // Hanya cek booking aktif
            })
            ->exists();

        if ($hasActiveBooking) {
            return response()->json([
                'message' => 'Tidak bisa reset, ada kursi yang masih di-booking untuk jadwal tertentu'
            ], 400);
        }

        $deleted = $gerbong->kursi()->delete();

        return response()->json([
            'message' => 'Kursi berhasil direset',
            'deleted_count' => $deleted
        ]);
    }

    /**
     * Seat map per gerbong (MASTER DATA - untuk petugas monitoring)
     * Tanpa filter jadwal, cuma tampilkan layout kursi
     */
    public function getSeatMap($id_gerbong, Request $request)
    {
        $gerbong = Gerbong::with('kursi')->findOrFail($id_gerbong);

        if ($gerbong->kursi->count() === 0) {
            return response()->json([
                'message' => 'Kursi belum di-generate untuk gerbong ini',
                'gerbong' => $gerbong->nama_gerbong,
                'kelas' => $gerbong->kelas_gerbong,
                'total_kursi' => 0,
                'seat_map' => []
            ], 404);
        }

        // ⭐ Cek apakah ada filter jadwal
        $jadwalId = $request->get('jadwal_id');

        if ($jadwalId) {
            // MODE: Dengan filter jadwal (tampilkan status)
            
            // Get kursi yang sudah di-booking untuk jadwal ini
            $bookedSeatIds = DetailPembelianTiket::whereHas('pembelianTiket', function($q) use ($jadwalId) {
                $q->where('id_jadwal_kereta', $jadwalId)
                ->where('status', 'booked');
            })->pluck('id_kursi')->toArray();

            // Map status untuk setiap kursi
            $seatMapWithStatus = $gerbong->kursi->map(function($kursi) use ($bookedSeatIds) {
                return [
                    'id' => $kursi->id,
                    'no_kursi' => $kursi->no_kursi,
                    'baris' => $kursi->baris,
                    'kolom' => $kursi->kolom,
                    'status' => in_array($kursi->id, $bookedSeatIds) ? 'booked' : 'available'
                ];
            })
            ->groupBy('baris')
            ->map(fn ($row) => $row->sortBy('kolom')->values());

            $totalKursi = $gerbong->kursi->count();
            $kursiBooked = count($bookedSeatIds);
            $kursiAvailable = $totalKursi - $kursiBooked;

            return response()->json([
                'gerbong' => $gerbong->nama_gerbong,
                'kelas' => $gerbong->kelas_gerbong,
                'jadwal_id' => (int) $jadwalId,
                'total_kursi' => $totalKursi,
                'kursi_available' => $kursiAvailable,
                'kursi_booked' => $kursiBooked,
                'seat_map' => $seatMapWithStatus
            ]);
            
        } else {
            // MODE: Tanpa filter jadwal (master data saja)
            
            $seatMap = $gerbong->kursi
                ->map(function($kursi) {
                    return [
                        'id' => $kursi->id,
                        'no_kursi' => $kursi->no_kursi,
                        'baris' => $kursi->baris,
                        'kolom' => $kursi->kolom,
                        // ❌ Tidak ada field 'status'
                    ];
                })
                ->groupBy('baris')
                ->map(fn ($row) => $row->sortBy('kolom')->values());

            return response()->json([
                'gerbong' => $gerbong->nama_gerbong,
                'kelas' => $gerbong->kelas_gerbong,
                'total_kursi' => $gerbong->kursi->count(),
                'seat_map' => $seatMap
            ]);
        }
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