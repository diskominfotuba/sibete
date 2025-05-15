<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessLampiran;
use Illuminate\Support\Facades\DB;
use App\Services\PermohonanService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;


class IzinBelajarController extends Controller
{
    private $permohonan;

    public function __construct(PermohonanService $permohonananService)
    {
        $this->permohonan = $permohonananService;
    }

    public function index(Request $request)
    {
        $query = $this->permohonan->query();

        if ($request->has('kategori')) {
            $query->where('kategori', $request->kategori);
        } else {
            $query->whereIn('kategori', ['izin_belajar', 'mutasi']);
        }

        $izinBelajar = $query->latest()->get();

        return $this->success($izinBelajar);
    }


    public function create()
    {
        $izin_belajar = $this->permohonan->Query()
            ->where('user_id', auth()->id())
            ->whereIn('kategori', ['izin_belajar', 'mutasi'])
            ->latest()->first();

        if ($izin_belajar && $izin_belajar->status !== 'diterima') {
            return $this->warning('Mohon maaf untuk saat ini Anda belum bisa mengajukan permohonan, karena ada permohonan sebelumnya yang belum selesai!');
        }

        return $this->success([
            'message' => 'Form izin_belajar atau alih tugas',
        ]);
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
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data['user_id'] = $request->user_id;
        $data['kategori'] = $request->kategori;

        $existingPermohonan = $this->permohonan->Query()
            ->where('user_id', $request->user_id)
            ->where('kategori', $request->kategori)
            ->latest()->first();

        if ($existingPermohonan && $existingPermohonan->status !== 'diterima') {
            return $this->warning('Mohon maaf untuk saat ini Anda belum bisa mengajukan permohonan, karena ada permohonan sebelumnya yang belum selesai!');
        }

        $pathFile = 'lampiran/' . strtolower(str_replace(' ', '_', $request->kategori)) . '/' . date('Y');

        if ($request->hasFile('lampiran1')) {
            $data['lampiran1'] = $this->uploadLampiran($request, 'lampiran1', uniqid() . '_surat_pengantar_dari_opd.pdf', $pathFile);
        }
        if ($request->hasFile('lampiran2')) {
            $data['lampiran2'] = $this->uploadLampiran($request, 'lampiran2', uniqid() .'_sk_pangkat_atau_jabatan_terakhir.pdf', $pathFile);
        }
        if ($request->hasFile('lampiran3')) {
            $data['lampiran3'] = $this->uploadLampiran($request, 'lampiran3', uniqid() .'_skp_1_tahun_terakhir.pdf', $pathFile);
        }
        if ($request->hasFile('lampiran4')) {
            $data['lampiran4'] = $this->uploadLampiran($request, 'lampiran4', uniqid() . '_daftar_hadir_3_bulan_terakhir.pdf', $pathFile);
        }
        if ($request->hasFile('lampiran5')) {
            $data['lampiran5'] = $this->uploadLampiran($request, 'lampiran5', '_lampiran_tambahan_alih_tugas.pdf', $pathFile);
        }

        try {
            $this->permohonan->store($data);
        } catch (\Throwable $th) {
            saveLogs($th->getMessage(), 'error');
            DB::rollBack();
            return $this->error('Gagal mengajukan permohonan');
        }

        return $this->success([], 'Permohonan berhasil diajukan', 201);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'kategori' => 'required|in:izin_belajar,mutasi',
            'lampiran1' => 'nullable|mimes:pdf|max:2048',
            'lampiran2' => 'nullable|mimes:pdf|max:2048',
            'lampiran3' => 'nullable|mimes:pdf|max:2048',
            'lampiran4' => 'nullable|mimes:pdf|max:2048',
            'lampiran5' => 'nullable|mimes:pdf|max:2048',
        ]);

        // dd($request->kategori);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $permohonan = $this->permohonan->find($id);

        if (!$permohonan) {
            return $this->error('Permohonan tidak ditemukan', 404);
        }

        // Siapkan array data
        $data = [];

        // Validasi kategori
        $pathFile = 'lampiran/' . strtolower(str_replace(' ', '_', $request->kategori)) . '/' . date('Y');

        // Delete file dulu sebelum diupdate
        if ($request->hasFile('lampiran1') && $permohonan->lampiran1) {
            Storage::disk('s3')->delete($pathFile . '/' . $permohonan->lampiran1);
            $data['lampiran1'] = $this->uploadLampiran($request, 'lampiran1', uniqid() . '_surat_pengantar_dari_opd.pdf', $pathFile);
        }
        if ($request->hasFile('lampiran2') && $permohonan->lampiran2) {
            Storage::disk('s3')->delete($pathFile . '/' . $permohonan->lampiran2);
            $data['lampiran2'] = $this->uploadLampiran($request, 'lampiran2', uniqid() . '_sk_pangkat_atau_jabatan_terakhir.pdf', $pathFile);
        }
        if ($request->hasFile('lampiran3') && $permohonan->lampiran3) {
            Storage::disk('s3')->delete($pathFile . '/' . $permohonan->lampiran3);
            $data['lampiran3'] = $this->uploadLampiran($request, 'lampiran3', uniqid() . '_skp_1_tahun_terakhir.pdf', $pathFile);
        }
        if ($request->hasFile('lampiran4') && $permohonan->lampiran4) {
            Storage::disk('s3')->delete($pathFile . '/' . $permohonan->lampiran4);
            $data['lampiran4'] = $this->uploadLampiran($request, 'lampiran4', uniqid() . '_daftar_hadir_3_bulan_terakhir.pdf', $pathFile);
        }
        if ($request->hasFile('lampiran5') && $permohonan->lampiran5) {
            Storage::disk('s3')->delete($pathFile . '/' . $permohonan->lampiran5);
            $data['lampiran5'] = $this->uploadLampiran($request, 'lampiran5', '_lampiran_tambahan_alih_tugas.pdf', $pathFile);
        }

        try {
            $this->permohonan->update($id, array_merge($request->all(), $data ?? []));
            DB::commit();
            return $this->success([], 'Permohonan berhasil diperbarui');
        } catch (\Throwable $th) {
            saveLogs($th->getMessage(), 'error');
            DB::rollBack();
            return $this->error('Gagal memperbarui permohonan', 500);
        }
    }


    public function destroy($id)
    {
        DB::beginTransaction();
        DB::commit();
        try {
            $this->permohonan->softDelete($id);
            return $this->success([
                'message' => 'Permohonan berhasil dihapus',
            ]);
        } catch (\Throwable $th) {
            saveLogs($th->getMessage(), 'error');
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
