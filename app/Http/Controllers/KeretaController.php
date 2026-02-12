<?php

namespace App\Http\Controllers;

use App\Models\Kereta;
use App\Models\DetailPembelianTiket;
use Illuminate\Http\Request;
use Illuminate\Support\Str;


class KeretaController extends Controller
{
    public function index()
    {
        $keretas = Kereta::with('gerbong')->get();

        //  Compute stats untuk setiap kereta
        $keretas->each(function($kereta) {
            $totalKursi = 0;
            $totalBooked = 0;

            foreach ($kereta->gerbong as $gerbong) {
                // Total kursi di gerbong ini
                $kursiCount = $gerbong->kursi()->count();
                $totalKursi += $kursiCount;
                
                if ($kursiCount > 0) {
                    // Hitung kursi yang booked (untuk jadwal aktif mana saja)
                    $kursiIds = $gerbong->kursi()->pluck('id');
                    
                    $bookedCount = DetailPembelianTiket::whereIn('id_kursi', $kursiIds)
                        ->whereHas('pembelianTiket', function($q) {
                            $q->where('status', 'booked');
                        })
                        ->distinct('id_kursi')
                        ->count('id_kursi');
                    
                    $totalBooked += $bookedCount;
                }
            }

            // Tambahkan computed fields
            $kereta->total_gerbong = $kereta->gerbong->count();
            $kereta->total_kursi = $totalKursi;
            $kereta->kursi_terbooked = $totalBooked;
            $kereta->kursi_tersedia = $totalKursi - $totalBooked;

            // Remove relasi gerbong dari response (tidak perlu di frontend)
            unset($kereta->gerbong);
        });

        return response()->json($keretas, 200);
    }

    // show
     public function show($id)
    {
        $kereta = Kereta::with('gerbong')->findOrFail($id);
        
        //  Compute stats untuk kereta ini
        $totalKursi = 0;
        $totalBooked = 0;

        foreach ($kereta->gerbong as $gerbong) {
            $kursiCount = $gerbong->kursi()->count();
            $totalKursi += $kursiCount;
            
            if ($kursiCount > 0) {
                $kursiIds = $gerbong->kursi()->pluck('id');
                
                $bookedCount = DetailPembelianTiket::whereIn('id_kursi', $kursiIds)
                    ->whereHas('pembelianTiket', function($q) {
                        $q->where('status', 'booked');
                    })
                    ->distinct('id_kursi')
                    ->count('id_kursi');
                
                $totalBooked += $bookedCount;
            }
        }

        $kereta->total_gerbong = $kereta->gerbong->count();
        $kereta->total_kursi = $totalKursi;
        $kereta->kursi_terbooked = $totalBooked;
        $kereta->kursi_tersedia = $totalKursi - $totalBooked;

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
