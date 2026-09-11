<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Jabatan;
use App\Models\Karyawan;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class JabatanController extends Controller
{
    public function index(Request $request)
    {
        $search = $this->resolveSearch($request);
        $perPage = $this->resolvePerPage($request);
        $editId = $request->integer('edit');

        if (! $editId && old('form_type') === 'edit') {
            $editId = (int) old('edit_id');
        }

        $editJabatan = $editId ? Jabatan::query()->find($editId) : null;

        return view('admin.jabatan', [
            'jabatanList' => Jabatan::query()
                ->withCount('karyawan')
                ->when($search !== '', function ($query) use ($search): void {
                    $query->where('nama_jabatan', 'like', "%{$search}%");
                })
                ->orderBy('nama_jabatan')
                ->paginate($perPage)
                ->withQueryString(),
            'editJabatan' => $editJabatan,
            'search' => $search,
            'perPage' => $perPage,
        ]);
    }

    public function store(Request $request)
    {
        Jabatan::query()->create($this->validatedData($request));

        return $this->respondSuccess($request, route('admin.jabatan'), 'Jabatan berhasil ditambahkan.');
    }

    public function update(Request $request, Jabatan $jabatan)
    {
        $data = $this->validatedData($request, $jabatan);

        $jabatan->update($data);

        Karyawan::query()
            ->where('jabatan_id', $jabatan->id)
            ->update([
                'jabatan' => $jabatan->nama_jabatan,
            ]);

        return $this->respondSuccess($request, route('admin.jabatan'), 'Jabatan berhasil diperbarui.');
    }

    public function destroy(Request $request, Jabatan $jabatan)
    {
        if (Karyawan::query()->where('jabatan_id', $jabatan->id)->exists()) {
            return $this->respondError($request, route('admin.jabatan'), 'Jabatan masih dipakai oleh data karyawan.', status: 409);
        }

        $jabatan->delete();

        return $this->respondSuccess($request, route('admin.jabatan'), 'Jabatan berhasil dihapus.');
    }

    private function validatedData(Request $request, ?Jabatan $jabatan = null): array
    {
        $data = $request->validate([
            'nama_jabatan' => ['required', 'string', 'max:100', Rule::unique('jabatan', 'nama_jabatan')->ignore($jabatan?->id)],
        ]);

        return [
            'nama_jabatan' => Str::of($data['nama_jabatan'])->trim()->toString(),
        ];
    }
}
