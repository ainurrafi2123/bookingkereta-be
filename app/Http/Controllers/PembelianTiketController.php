<?php

namespace App\Http\Controllers;

use App\Models\PembelianTiket;
use App\Models\DetailPembelianTiket;
use App\Models\JadwalKereta;
use App\Models\Kursi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PembelianTiketController extends Controller
{
    /**
     * GET /api/pembelian-tiket
     */
    public function index(Request $request)
    {
        $query = PembelianTiket::with([
            'penumpang.user',
            'jadwalKereta.kereta',
            'detailPembelian.kursi'
        ]);
        
        // Filter by status (optional)
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        // Filter by penumpang (optional)
        if ($request->has('id_penumpang')) {
            $query->where('id_penumpang', $request->id_penumpang);
        }
        
        // Filter by jadwal (optional)
        if ($request->has('id_jadwal_kereta')) {
            $query->where('id_jadwal_kereta', $request->id_jadwal_kereta);
        }
        
        // Pagination
        $perPage = $request->get('per_page', 15);
        $pembelian = $query->latest()->paginate($perPage);
        
        return response()->json([
            'message' => 'Data pembelian tiket',
            'data' => $pembelian
        ]);
    }

    /**
     * POST /api/pembelian-tiket
     */
    public function store(Request $request)
    {
        // Validasi input
        $validated = $request->validate([
            'id_penumpang' => 'required|exists:penumpang,id',
            'id_jadwal_kereta' => 'required|exists:jadwal_kereta,id',
            'penumpang' => 'required|array|min:1|max:10',
            'penumpang.*.nik' => [
                'required', 
                'string',
                'size:16',
                'regex:/^[1-9]\d{15}$/'
            ],
            'penumpang.*.nama' => [
                'required',
                'string',
                'min:3',
                'max:255',
            ],
            'penumpang.*.kategori' => 'required|in:anak,dewasa,lansia',
            'penumpang.*.id_kursi' => [
                'required',
                'exists:kursi,id',
                'distinct'
            ],
        ], [
            'penumpang.*.nik.required' => 'NIK wajib diisi untuk semua penumpang',
            'penumpang.*.nik.size' => 'NIK harus 16 digit',
            'penumpang.*.nik.regex' => 'Format NIK tidak valid (harus angka dan tidak diawali 0)',
            'penumpang.*.nama.required' => 'Nama penumpang wajib diisi',
            'penumpang.*.nama.min' => 'Nama minimal 3 karakter',
            'penumpang.*.kategori.required' => 'Kategori penumpang wajib dipilih',
            'penumpang.*.kategori.in' => 'Kategori harus: anak, dewasa, atau lansia',
            'penumpang.*.id_kursi.required' => 'Kursi harus dipilih',
            'penumpang.*.id_kursi.distinct' => 'Tidak boleh memilih kursi yang sama',
            'penumpang.min' => 'Minimal 1 penumpang',
            'penumpang.max' => 'Maksimal 10 penumpang per transaksi',
        ]);

        DB::beginTransaction();
        try {
            // 1. Get jadwal & validasi
            $jadwal = JadwalKereta::with('kereta.gerbong')
                ->findOrFail($validated['id_jadwal_kereta']);
            
            // Cek jadwal masih aktif
            if ($jadwal->status !== 'active') {
                return response()->json([
                    'message' => 'Jadwal tidak aktif'
                ], 400);
            }
            
            // Cek jadwal belum kedaluwarsa
            if (now()->greaterThan($jadwal->tanggal_berangkat)) {
                return response()->json([
                    'message' => 'Jadwal sudah lewat'
                ], 400);
            }

            // 2. Collect kursi IDs
            $kursiIds = collect($validated['penumpang'])->pluck('id_kursi');
            
            // 4. Cek double booking (kursi sudah dipesan orang lain)
            $existingBooking = DetailPembelianTiket::whereIn('id_kursi', $kursiIds)
                ->whereHas('pembelianTiket', function($q) use ($validated) {
                    $q->where('id_jadwal_kereta', $validated['id_jadwal_kereta'])
                      ->where('status', 'booked');
                })
                ->exists();
            
            if ($existingBooking) {
                return response()->json([
                    'message' => 'Beberapa kursi sudah dipesan penumpang lain'
                ], 409);
            }

            // 3. Validasi kursi tersedia & di gerbong yang tepat
            $gerbongIds = $jadwal->kereta->gerbong->pluck('id');
            
            $validKursi = Kursi::whereIn('id', $kursiIds)
                ->whereIn('id_gerbong', $gerbongIds)
                ->where('status', 'available')
                ->pluck('id');
            
            if ($validKursi->count() !== $kursiIds->count()) {
                return response()->json([
                    'message' => 'Beberapa kursi tidak tersedia atau tidak valid untuk jadwal ini'
                ], 400);
            }

            // 5. Hitung total harga
            $totalHarga = 0;
            $detailData = [];
            
            foreach ($validated['penumpang'] as $penumpang) {
                // Get harga sesuai kategori
                $harga = match($penumpang['kategori']) {
                    'dewasa' => $jadwal->harga_dewasa,
                    'anak' => $jadwal->harga_anak,
                    'lansia' => $jadwal->harga_lansia,
                };
                
                $totalHarga += $harga;
                
                $detailData[] = [
                    'id_kursi' => $penumpang['id_kursi'],
                    'nik' => $penumpang['nik'],
                    'nama_penumpang' => $penumpang['nama'],
                    'kategori' => $penumpang['kategori'],
                    'harga' => $harga,
                ];
            }

            // 6. Generate kode tiket
            $kodeTiket = $this->generateKodeTiket();

            // 7. Create pembelian tiket
            $pembelian = PembelianTiket::create([
                'kode_tiket' => $kodeTiket,
                'tanggal_pembelian' => now(),
                'id_penumpang' => $validated['id_penumpang'],
                'id_jadwal_kereta' => $validated['id_jadwal_kereta'],
                'total_harga' => $totalHarga,
                'status' => 'booked',
            ]);

            // 8. Create detail pembelian untuk setiap penumpang
            foreach ($detailData as $detail) {
                $pembelian->detailPembelian()->create($detail);
            }

            // 9. Update status kursi jadi 'booked'
            Kursi::whereIn('id', $kursiIds)->update([
                'status' => 'booked'
            ]);

            // 9b. Update kuota jadwal
            $jumlahKursi = count($kursiIds);

            $jadwal->decrement('kursi_tersedia', $jumlahKursi);
            $jadwal->increment('kursi_terjual', $jumlahKursi);

            // 10. Commit transaction
            DB::commit();

            // 11. Load relasi untuk response
            $pembelian->load([
                'penumpang.user',
                'jadwalKereta.kereta',
                'detailPembelian.kursi'
            ]);

            return response()->json([
                'message' => 'Booking tiket berhasil',
                'data' => [
                    'kode_tiket' => $pembelian->kode_tiket,
                    'tanggal_pembelian' => $pembelian->tanggal_pembelian->format('d-m-Y H:i'),
                    'status' => $pembelian->status,
                    'total_harga' => number_format($pembelian->total_harga, 0, ',', '.'),
                    'jumlah_penumpang' => $pembelian->detailPembelian->count(),
                    'jadwal' => [
                        'kereta' => $pembelian->jadwalKereta->kereta->nama_kereta,
                        'kelas' => $pembelian->jadwalKereta->kereta->kelas_kereta,
                        'rute' => $pembelian->jadwalKereta->asal_keberangkatan . ' - ' . $pembelian->jadwalKereta->tujuan_keberangkatan,
                        'tanggal_berangkat' => $pembelian->jadwalKereta->tanggal_berangkat->format('d-m-Y H:i'),
                    ],
                    'penumpang' => $pembelian->detailPembelian->map(function($detail) {
                        return [
                            'nama' => $detail->nama_penumpang,
                            'nik' => $detail->nik,
                            'kategori' => $detail->kategori,
                            'kursi' => $detail->kursi->no_kursi,
                            'harga' => number_format($detail->harga, 0, ',', '.'),
                        ];
                    })
                ]
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Gagal membuat booking',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
    * GET /api/v1/pembelian-tiket/{id}/receipt
    */
    public function receipt(Request $request, $id)
    {
        $pembelian = PembelianTiket::with([
            'penumpang.user',
            'jadwalKereta.kereta',
            'detailPembelian.kursi.gerbong'
        ])->find($id);

        if (!$pembelian) {
            return response()->json([
                'success' => false,
                'message' => 'Pembelian tiket tidak ditemukan'
            ], 404);
        }

        // Cek kepemilikan tiket (user hanya bisa lihat receipt sendiri, kecuali petugas)
        $user = $request->user();
        
        if ($user->role !== 'petugas' && $pembelian->id_penumpang !== $user->penumpang?->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke tiket ini'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Receipt pembelian tiket',
            'data' => $this->formatReceiptData($pembelian),
        ]);
    }

    /**
     * api/v1/pembelian-tiket/kode/{kode_tiket}/receipt
     */
    public function receiptByKode(Request $request, $kode_tiket)
    {
        $pembelian = PembelianTiket::with([
            'penumpang.user',
            'jadwalKereta.kereta',
            'detailPembelian.kursi.gerbong'
        ])->where('kode_tiket', $kode_tiket)->first();

        if (!$pembelian) {
            return response()->json([
                'success' => false,
                'message' => 'Tiket dengan kode ' . $kode_tiket . ' tidak ditemukan'
            ], 404);
        }

        // Cek kepemilikan tiket (user hanya bisa lihat receipt sendiri, kecuali petugas)
        $user = $request->user();
        
        if ($user->role !== 'petugas' && $pembelian->id_penumpang !== $user->penumpang?->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke tiket ini'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Receipt pembelian tiket',
            'data' => $this->formatReceiptData($pembelian),
        ]);
    }

    private function formatReceiptData(PembelianTiket $pembelian): array
    {
        return [
            // Header Info
            'perusahaan' => [
                'nama' => 'PT SENRO INDONESIA (PERSERO)',
                'npwp' => 'NPWP 01.000.016.4-083.000',
            ],

            // Detail Pembayaran
            'detail_pembayaran' => [
                'tanggal_pembayaran' => $pembelian->tanggal_pembelian->format('d M Y, H:i'),
                'metode_pembayaran' => $pembelian->metode_pembayaran ?? 'ATM',
                'kode_pemesanan' => $pembelian->kode_tiket,
            ],

            // Rincian Tiket
            'rincian' => [
                'kereta' => $pembelian->jadwalKereta->kereta->nama_kereta
                    . ' (' . $pembelian->jadwalKereta->kode_jadwal . ')',
                'kelas' => strtoupper($pembelian->jadwalKereta->kereta->kelas_kereta),
                'kode_pemesanan' => $pembelian->kode_tiket,
                'penumpang' => $pembelian->detailPembelian->map(function ($detail) {
                    return [
                        'nama' => strtoupper($detail->nama_penumpang),
                        'harga' => 'Rp. ' . number_format($detail->harga, 0, ',', '.'),
                    ];
                }),
            ],

            // Total Pembayaran
            'total_pembayaran' => 'Rp. ' . number_format($pembelian->total_harga, 0, ',', '.'),
            'ppn_info' => 'Tidak termasuk PPN.',
            'ppn_disclaimer' =>
                'PPN dibebaskan berdasarkan pasal 16B Undang Undang Harmonisasi Peraturan Perpajakan.',

            // Barcode / Kode Pemesanan
            'kode_pemesanan_display' => $pembelian->kode_tiket,

            // Pemesanan Info
            'pemesanan' => [
                'nama' => strtoupper($pembelian->penumpang->user->username ?? 'N/A'),
                'no_telepon' => $pembelian->penumpang->user->no_telepon ?? '0',
                'email' => $pembelian->penumpang->user->email ?? 'N/A',
                'tanggal_pesan' => $pembelian->tanggal_pembelian->format('d M Y, H:i:s'),
                'pemesanan_melalui' => 'KAI Access',
            ],

            // Detail Perjalanan
            'detail_pemesanan' => [
                [
                    'kereta' => strtoupper($pembelian->jadwalKereta->kereta->nama_kereta),
                    'nomor_ka' => $pembelian->jadwalKereta->kode_jadwal,
                    'keberangkatan' =>
                        strtoupper($pembelian->jadwalKereta->asal_keberangkatan)
                        . ' | ' . $pembelian->jadwalKereta->tanggal_berangkat->format('d M Y, H:i'),
                    'tujuan' =>
                        strtoupper($pembelian->jadwalKereta->tujuan_keberangkatan)
                        . ' | ' . $pembelian->jadwalKereta->tanggal_kedatangan->format('d M Y, H:i'),
                ]
            ],

            // Detail Penumpang
            'detail_penumpang' => $pembelian->detailPembelian->map(function ($detail) {
                return [
                    'penumpang' => strtoupper($detail->nama_penumpang),
                    'kursi' =>
                        $detail->kursi->gerbong->nama_gerbong
                        . ' ' . $detail->kursi->no_kursi,
                    'kelas' => strtoupper($detail->kategori),
                    'no_identitas' => $detail->nik,
                ];
            }),

            // Status
            'status' => $pembelian->status,
        ];
    }

    /**
     * GET /api/pembelian-tiket/{id}
     */
    public function show($id)
    {
        $pembelian = PembelianTiket::with([
            'penumpang.user',
            'jadwalKereta.kereta',
            'detailPembelian.kursi'
        ])->find($id);

        if (!$pembelian) {
            return response()->json([
                'message' => 'Pembelian tiket tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'message' => 'Detail pembelian tiket',
            'data' => [
                'id' => $pembelian->id,
                'kode_tiket' => $pembelian->kode_tiket,
                'tanggal_pembelian' => $pembelian->tanggal_pembelian->format('d-m-Y H:i'),
                'status' => $pembelian->status,
                'total_harga' => number_format($pembelian->total_harga, 0, ',', '.'),
                'pemesan' => [
                    'nama' => $pembelian->penumpang->user->username ?? 'N/A',
                    'email' => $pembelian->penumpang->user->email ?? 'N/A',
                ],
                'jadwal' => [
                    'kode' => $pembelian->jadwalKereta->kode_jadwal,
                    'kereta' => $pembelian->jadwalKereta->kereta->nama_kereta,
                    'kelas' => $pembelian->jadwalKereta->kereta->kelas_kereta,
                    'asal' => $pembelian->jadwalKereta->asal_keberangkatan,
                    'tujuan' => $pembelian->jadwalKereta->tujuan_keberangkatan,
                    'tanggal_berangkat' => $pembelian->jadwalKereta->tanggal_berangkat->format('d-m-Y H:i'),
                    'tanggal_tiba' => $pembelian->jadwalKereta->tanggal_kedatangan->format('d-m-Y H:i'),
                ],
                'penumpang' => $pembelian->detailPembelian->map(function($detail) {
                    return [
                        'nama' => $detail->nama_penumpang,
                        'nik' => $detail->nik,
                        'kategori' => ucfirst($detail->kategori),
                        'kursi' => $detail->kursi->no_kursi,
                        'gerbong' => $detail->kursi->gerbong->nama_gerbong ?? 'N/A',
                        'harga' => 'Rp ' . number_format($detail->harga, 0, ',', '.'),
                    ];
                }),
                'jumlah_penumpang' => $pembelian->detailPembelian->count(),
            ]
        ]);
    }

    /**
     * Display pembelian by kode_tiket
     * GET /api/pembelian-tiket/kode/{kode_tiket}
     */
    public function showByKode($kode_tiket)
    {
        $pembelian = PembelianTiket::with([
            'penumpang.user',
            'jadwalKereta.kereta',
            'detailPembelian.kursi.gerbong'
        ])->where('kode_tiket', $kode_tiket)->first();

        if (!$pembelian) {
            return response()->json([
                'message' => 'Tiket tidak ditemukan'
            ], 404);
        }

        return $this->show($pembelian->id);
    }

    /**
     * Cancel pembelian tiket
     * PUT /api/pembelian-tiket/{id}/cancel
     */
    public function cancel($id)
    {
        DB::beginTransaction();
        try {
            $pembelian = PembelianTiket::with(['detailPembelian', 'jadwalKereta'])
                ->findOrFail($id);

            // Validasi status
            if ($pembelian->status === 'cancelled') {
                return response()->json([
                    'message' => 'Tiket sudah dibatalkan sebelumnya'
                ], 400);
            }

            // Validasi: Cek apakah masih bisa cancel
            // Misal: minimal 24 jam sebelum keberangkatan
            $jamSebelumBerangkat = now()->diffInHours($pembelian->jadwalKereta->tanggal_berangkat, false);
            
            if ($jamSebelumBerangkat < 24) {
                return response()->json([
                    'message' => 'Tidak bisa membatalkan tiket kurang dari 24 jam sebelum keberangkatan',
                    'jam_tersisa' => round($jamSebelumBerangkat, 1)
                ], 400);
            }

            // Update status pembelian
            $pembelian->update([
                'status' => 'cancelled'
            ]);

            // Kembalikan status kursi jadi 'available'
            $kursiIds = $pembelian->detailPembelian->pluck('id_kursi');
            Kursi::whereIn('id', $kursiIds)->update([
                'status' => 'available'
            ]);

            $jumlahKursi = $pembelian->detailPembelian->count();
            $jadwal = $pembelian->jadwalKereta;
            $jadwal->increment('kursi_tersedia', $jumlahKursi);
            $jadwal->decrement('kursi_terjual', $jumlahKursi);

            DB::commit();

            return response()->json([
                'message' => 'Tiket berhasil dibatalkan',
                'data' => [
                    'kode_tiket' => $pembelian->kode_tiket,
                    'status' => $pembelian->status,
                    'kursi_dikembalikan' => $kursiIds->count()
                ]
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Pembelian tiket tidak ditemukan'
            ], 404);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Gagal membatalkan tiket',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available seats for jadwal
     * GET /api/pembelian-tiket/jadwal/{id_jadwal}/kursi-tersedia
     */
    public function getAvailableSeats($id_jadwal)
    {
        try {
            $jadwal = JadwalKereta::with(['kereta.gerbong.kursi' => function($query) {
                $query->where('status', 'available')
                      ->orderBy('baris')
                      ->orderBy('kolom');
            }])->findOrFail($id_jadwal);

            // Group kursi by gerbong
            $kursiByGerbong = [];
            
            foreach ($jadwal->kereta->gerbong as $gerbong) {
                $kursiByGerbong[] = [
                    'id_gerbong' => $gerbong->id,
                    'nama_gerbong' => $gerbong->nama_gerbong,
                    'kelas' => $gerbong->kelas_gerbong,
                    'kuota_total' => $gerbong->kuota,
                    'kursi_tersedia' => $gerbong->kursi->count(),
                    'kursi' => $gerbong->kursi->map(function($kursi) {
                        return [
                            'id' => $kursi->id,
                            'no_kursi' => $kursi->no_kursi,
                            'baris' => $kursi->baris,
                            'kolom' => $kursi->kolom,
                            'status' => $kursi->status,
                        ];
                    })
                ];
            }

            return response()->json([
                'message' => 'Kursi tersedia',
                'jadwal' => [
                    'kode_jadwal' => $jadwal->kode_jadwal,
                    'kereta' => $jadwal->kereta->nama_kereta,
                    'rute' => $jadwal->asal_keberangkatan . ' - ' . $jadwal->tujuan_keberangkatan,
                ],
                'gerbong' => $kursiByGerbong,
                'total_kursi_tersedia' => collect($kursiByGerbong)->sum('kursi_tersedia')
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Jadwal tidak ditemukan'
            ], 404);
        }
    }

    /**
     * Generate kode tiket unik
     */
    private function generateKodeTiket()
    {
        $prefix = 'TKT';
        $yearMonth = date('Ym'); // 202501
        
        // Get last ticket number untuk bulan ini
        $lastTicket = PembelianTiket::where('kode_tiket', 'like', "$prefix-$yearMonth-%")
            ->orderBy('id', 'desc')
            ->first();
        
        if ($lastTicket) {
            // Extract number dari kode terakhir
            $lastNumber = (int) substr($lastTicket->kode_tiket, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        
        // Format: TKT-202501-0001
        return sprintf('%s-%s-%04d', $prefix, $yearMonth, $newNumber);
    }

    /**
     * Get statistics
     * GET /api/pembelian-tiket/statistics
     */
    public function statistics()
    {
        $totalBooking = PembelianTiket::count();
        $totalBooked = PembelianTiket::where('status', 'booked')->count();
        $totalCancelled = PembelianTiket::where('status', 'cancelled')->count();
        $totalRevenue = PembelianTiket::where('status', 'booked')->sum('total_harga');
        
        return response()->json([
            'message' => 'Statistik pembelian tiket',
            'data' => [
                'total_transaksi' => $totalBooking,
                'booking_aktif' => $totalBooked,
                'booking_dibatalkan' => $totalCancelled,
                'total_pendapatan' => 'Rp ' . number_format($totalRevenue, 0, ',', '.'),
            ]
        ]);
    }
}