<?php

namespace App\Http\Controllers;

use App\Models\JadwalKereta;
use App\Models\Kereta;
use App\Models\Kursi;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class JadwalKeretaController extends Controller
{
    /**
     * GET all jadwal
     */
    public function index(Request $request)
    {
        $this->autoUpdateSchedules();

        $query = JadwalKereta::with('kereta');

        //  Filter by kereta (untuk dropdown jadwal di halaman gerbong)
        if ($request->has('id_kereta')) {
            $query->where('id_kereta', $request->id_kereta);
        }

        //  Filter by status (optional)
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Only filter by time if requested or for specific scenarios
        // For dashboard management, we often want to see past schedules (completed)
        if ($request->has('only_upcoming') && $request->only_upcoming == 'true') {
            $query->where('tanggal_berangkat', '>=', now());
        }

        // Order by tanggal
        $query->orderBy('tanggal_berangkat', 'asc');

        $jadwals = $query->get();

        return response()->json($jadwals);
    }

    /**
     * GET single jadwal
     */
    public function show($id)
    {
        $this->autoUpdateSchedules();

        $jadwal = JadwalKereta::with('kereta')->findOrFail($id);

        return response()->json($jadwal);
    }

    /**
     * CREATE jadwal
     * asal & tujuan ditentukan di awal (immutable)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_kereta'            => 'required|exists:kereta,id',
            'asal_keberangkatan'   => 'required|string|max:100',
            'tujuan_keberangkatan' => 'required|string|max:100',
            'tanggal_berangkat'    => 'required|date',
            'tanggal_kedatangan'   => 'required|date|after:tanggal_berangkat',
            'harga_dewasa'         => 'required|numeric|min:0',
            'harga_anak'           => 'required|numeric|min:0',
            'harga_lansia'         => 'required|numeric|min:0',
        ]);

        // hitung kursi dari kursi available
        $totalKursi = Kursi::whereHas('gerbong', function ($q) use ($validated) {
            $q->where('id_kereta', $validated['id_kereta']);
        })->count();

        // generate kode jadwal
        $kodeJadwal = strtoupper(
            Str::substr($validated['asal_keberangkatan'], 0, 3) . '-' .
            Str::substr($validated['tujuan_keberangkatan'], 0, 3) . '-' .
            now()->format('Ymd') . '-' .
            str_pad(JadwalKereta::count() + 1, 3, '0', STR_PAD_LEFT)
        );

        $jadwal = JadwalKereta::create([
            'id_kereta'        => $validated['id_kereta'],
            'kode_jadwal'      => $kodeJadwal,
            'asal_keberangkatan'   => $validated['asal_keberangkatan'],
            'tujuan_keberangkatan' => $validated['tujuan_keberangkatan'],
            'tanggal_berangkat'    => $validated['tanggal_berangkat'],
            'tanggal_kedatangan'   => $validated['tanggal_kedatangan'],
            'harga_dewasa'     => $validated['harga_dewasa'],
            'harga_anak'       => $validated['harga_anak'],
            'harga_lansia'     => $validated['harga_lansia'],
            'status'           => 'active',
            'kuota_total'      => $totalKursi,
            'kursi_tersedia'   => $totalKursi,
            'kursi_terjual'    => 0,
        ]);

        return response()->json($jadwal, 201);
    }

    /**
     * UPDATE jadwal
     * asal & tujuan TIDAK BOLEH diubah
     */
    public function update(Request $request, $id)
    {
        $jadwal = JadwalKereta::findOrFail($id);

        $validated = $request->validate([
            'tanggal_berangkat' => 'sometimes|date',
            'tanggal_kedatangan'=> 'sometimes|date|after:tanggal_berangkat',
            'harga_dewasa'      => 'sometimes|numeric|min:0',
            'harga_anak'        => 'sometimes|numeric|min:0',
            'harga_lansia'      => 'sometimes|numeric|min:0',
            'status'            => 'sometimes|in:active,completed,cancelled,maintenance',
        ]);

        $jadwal->update($validated);

        return response()->json($jadwal);
    }

    /**
     * DELETE jadwal
     */
    public function destroy($id)
    {
        $jadwal = JadwalKereta::findOrFail($id);
        $jadwal->delete();

        return response()->json([
            'message' => 'Jadwal kereta berhasil dihapus'
        ]);
    }
    /**
     * Auto update status active -> completed jika waktu sudah lewat
     */
    private function autoUpdateSchedules()
    {
        JadwalKereta::where('status', 'active')
            ->where('tanggal_kedatangan', '<', now())
            ->update(['status' => 'completed']);
    }
}
