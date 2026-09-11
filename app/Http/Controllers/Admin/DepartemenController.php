<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Departemen;
use App\Models\Karyawan;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class DepartemenController extends Controller
{
    public function index(Request $request)
    {
        $search = $this->resolveSearch($request);
        $perPage = $this->resolvePerPage($request);
        $editId = $request->integer('edit');

        if (! $editId && old('form_type') === 'edit') {
            $editId = (int) old('edit_id');
        }

        $editDepartemen = $editId ? Departemen::query()->find($editId) : null;

        return view('admin.departemen', [
            'departemenList' => Departemen::query()
                ->withCount('karyawan')
                ->when($search !== '', function ($query) use ($search): void {
                    $query->where('nama_departemen', 'like', "%{$search}%");
                })
                ->orderBy('nama_departemen')
                ->paginate($perPage)
                ->withQueryString(),
            'editDepartemen' => $editDepartemen,
            'search' => $search,
            'perPage' => $perPage,
        ]);
    }

    public function store(Request $request)
    {
        Departemen::query()->create($this->validatedData($request));

        return $this->respondSuccess($request, route('admin.departemen'), 'Departemen berhasil ditambahkan.');
    }

    public function update(Request $request, Departemen $departemen)
    {
        $data = $this->validatedData($request, $departemen);

        $departemen->update($data);

        Karyawan::query()
            ->where('departemen_id', $departemen->id)
            ->update([
                'departemen' => $departemen->nama_departemen,
            ]);

        return $this->respondSuccess($request, route('admin.departemen'), 'Departemen berhasil diperbarui.');
    }

    public function destroy(Request $request, Departemen $departemen)
    {
        if (Karyawan::query()->where('departemen_id', $departemen->id)->exists()) {
            return $this->respondError($request, route('admin.departemen'), 'Departemen masih dipakai oleh data karyawan.', status: 409);
        }

        $departemen->delete();

        return $this->respondSuccess($request, route('admin.departemen'), 'Departemen berhasil dihapus.');
    }

    private function validatedData(Request $request, ?Departemen $departemen = null): array
    {
        $data = $request->validate([
            'nama_departemen' => ['required', 'string', 'max:100', Rule::unique('departemen', 'nama_departemen')->ignore($departemen?->id)],
        ]);

        return [
            'nama_departemen' => Str::of($data['nama_departemen'])->trim()->toString(),
        ];
    }
}
