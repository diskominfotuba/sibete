<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Services\PermohonanService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class IzinBelajarController extends Controller
{
    private $permohonan;

    public function __construct(PermohonanService $permohonanService)
    {
        $this->permohonan = $permohonanService;
    }

    public function index(Request $request)
    {
        $query = $this->permohonan->query();
        $kategori = $request->kategori ?? ['izin_belajar', 'mutasi'];
        $query->whereIn('kategori', (array) $kategori);
        $results = $query->latest()->get();

        // Map setiap item untuk ubah field lampiran jadi full URL
        $results = $results->map(function ($item) {
            $folder = 'lampiran/' . $item->kategori . '/' . date('Y');

            for ($i = 1; $i <= 4; $i++) {
                $key = "lampiran$i";
                if (!empty($item->$key)) {
                    $item->$key = \Illuminate\Support\Facades\Storage::disk('s3')->url(
                        $folder . '/' . $item->$key
                    );
                }
            }

            return $item;
        });

        return $this->success($results);
    }


    public function create(Request $request)
    {
        $izinBelajar = $this->permohonan->query()
            ->where('user_id', $request->user_id)
            ->whereIn('kategori', ['izin_belajar', 'mutasi'])
            ->latest()
            ->first();

        if ($izinBelajar && $izinBelajar->status !== 'diterima') {
            return $this->warning('Mohon maaf, Anda belum bisa mengajukan permohonan karena permohonan sebelumnya belum selesai!');
        }

        return $this->success(['message' => 'Form izin_belajar atau alih tugas']);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'kategori' => 'required|in:izin_belajar,mutasi',
            'lampiran1' => 'nullable|mimes:pdf|max:2048',
            'lampiran2' => 'nullable|mimes:pdf|max:2048',
            'lampiran3' => 'nullable|mimes:pdf|max:2048',
            'lampiran4' => 'nullable|mimes:pdf|max:2048',
            'lampiran5' => 'nullable|mimes:pdf|max:2048',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 422);
        }

        $data = $request->only(['user_id', 'kategori']);

        $existing = $this->permohonan->query()
            ->where('user_id', $request->user_id)
            ->where('kategori', $request->kategori)
            ->latest()
            ->first();

        if ($existing && $existing->status !== 'diterima') {
            return $this->warning('Mohon maaf, Anda belum bisa mengajukan permohonan karena permohonan sebelumnya belum selesai!');
        }

        $pathFile = 'lampiran/' . strtolower(str_replace(' ', '_', $request->kategori)) . '/' . date('Y');

        $lampiranList = [
            'lampiran1' => 'surat_pengantar_dari_opd.pdf',
            'lampiran2' => 'sk_pangkat_atau_jabatan_terakhir.pdf',
            'lampiran3' => 'skp_1_tahun_terakhir.pdf',
            'lampiran4' => 'daftar_hadir_3_bulan_terakhir.pdf',
            'lampiran5' => 'lampiran_tambahan_alih_tugas.pdf',
        ];

        foreach ($lampiranList as $key => $filename) {
            if ($request->hasFile($key)) {
                $data[$key] = $this->uploadLampiran($request, $key, $filename, $pathFile);
            }
        }

        DB::beginTransaction();
        try {
            $this->permohonan->store($data);
            DB::commit();
            return $this->success(null, 'Permohonan berhasil diajukan', 201);
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->error('Gagal mengajukan permohonan');
        }
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'lampiran1' => 'nullable|mimes:pdf|max:2048',
            'lampiran2' => 'nullable|mimes:pdf|max:2048',
            'lampiran3' => 'nullable|mimes:pdf|max:2048',
            'lampiran4' => 'nullable|mimes:pdf|max:2048',
            'lampiran5' => 'nullable|mimes:pdf|max:2048',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 422);
        }

        $permohonan = $this->permohonan->find($id);

        if (!$permohonan) {
            return $this->error('Permohonan tidak ditemukan', 404);
        }

        $pathFile = 'lampiran/' . strtolower(str_replace(' ', '_', $permohonan->kategori)) . '/' . date('Y');

        $data = [];
        $lampiranList = [
            'lampiran1' => 'surat_pengantar_dari_opd.pdf',
            'lampiran2' => 'sk_pangkat_atau_jabatan_terakhir.pdf',
            'lampiran3' => 'skp_1_tahun_terakhir.pdf',
            'lampiran4' => 'daftar_hadir_3_bulan_terakhir.pdf',
            'lampiran5' => 'lampiran_tambahan_alih_tugas.pdf',
        ];

        DB::beginTransaction();
        try {
            foreach ($lampiranList as $key => $filename) {
                if ($request->hasFile($key)) {
                    if ($permohonan->$key) {
                        Storage::disk('s3')->delete($pathFile . '/' . $permohonan->$key);
                    }
                    $data[$key] = $this->uploadLampiran($request, $key, uniqid() . '_' . $filename, $pathFile);
                }
            }

            $this->permohonan->update($id, array_merge($request->except('kategori'), $data));
            DB::commit();

            return $this->success([], 'Permohonan berhasil diperbarui');
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->error('Gagal memperbarui permohonan', 500);
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $this->permohonan->softDelete($id);
            DB::commit();
            return $this->success([], 'Permohonan berhasil dihapus');
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->error('Gagal menghapus permohonan', 500);
        }
    }

    private function uploadLampiran($request, $field, $filename, $path)
    {
        if ($request->hasFile($field)) {
            $uniqueName = uniqid() . '_' . $filename;
            $request->file($field)->storeAs($path, $uniqueName, 's3');
            return $uniqueName;
        }
        return null;
    }
}
