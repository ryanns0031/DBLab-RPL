<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\ActivityLog;
use App\Models\SchoolClass;
use App\Models\User;
use App\Services\Audit;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    private function definition($kind)
    {
        return match ($kind) {
            'users' => [User::class, 'Kelola Pengguna', ['name' => 'Nama', 'email' => 'Email Google', 'role' => 'Peran', 'status' => 'Status', 'nis' => 'NIS', 'nip' => 'NIP', 'class_id' => 'Kelas']],
            'classes' => [SchoolClass::class, 'Kelola Kelas', ['name' => 'Nama kelas', 'level' => 'Tingkat', 'academic_year_id' => 'Tahun ajaran', 'status' => 'Status']],
            'years' => [AcademicYear::class, 'Tahun Ajaran', ['name' => 'Tahun ajaran', 'semester' => 'Semester', 'active' => 'Aktif']],default => abort(404)
        };
    }

    public function index(Request $r, string $kind)
    {
        [$model,$title,$fields] = $this->definition($kind);
        $q = $model::query();
        if ($r->filled('q')) {
            $q->where('name', 'like', '%'.$r->string('q').'%');
        }if ($kind === 'users') {
            if ($r->filled('role')) {
                $q->where('role', $r->role);
            }if ($r->filled('class_id')) {
                $q->where('class_id', $r->class_id);
            }
        } $rows = $q->latest()->paginate(15)->withQueryString();
        $edit = $r->filled('edit') ? $model::findOrFail($r->integer('edit')) : null;

        return view('admin.manage', compact('kind', 'title', 'fields', 'rows', 'edit') + ['classes' => SchoolClass::all(), 'years' => AcademicYear::all()]);
    }

    public function save(Request $r, string $kind, ?int $id = null)
    {
        [$model] = $this->definition($kind);
        $item = $id ? $model::findOrFail($id) : new $model;
        $rules = match ($kind) {
            'users' => ['name' => 'required|string|max:120', 'email' => ['required', 'email', 'max:190', Rule::unique('users')->ignore($id)], 'password' => $id ? 'nullable|string|min:8' : 'required|string|min:8', 'role' => 'required|in:admin,guru,siswa', 'status' => 'required|in:aktif,nonaktif', 'nis' => ['nullable', 'required_if:role,siswa', 'string', 'max:50', Rule::unique('users')->ignore($id)], 'nip' => 'nullable|string|max:50', 'class_id' => 'nullable|required_if:role,siswa|exists:classes,id'],
            'classes' => ['name' => ['required', 'string', 'max:80', Rule::unique('classes')->ignore($id)], 'level' => 'required|in:X,XI,XII', 'academic_year_id' => 'required|exists:academic_years,id', 'status' => 'required|in:aktif,nonaktif'],
            'years' => ['name' => ['required', 'regex:/^\d{4}\/\d{4}$/', Rule::unique('academic_years')->ignore($id)], 'semester' => 'required|in:Ganjil,Genap', 'active' => 'required|boolean']
        };
        if ($kind === 'users') {
            $r->merge(['email' => strtolower($r->input('email', ''))]);
        }$data = $r->validate($rules);
        if ($kind === 'users') {
            if (! empty($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            } else {
                unset($data['password']);
            }
        }
        if ($kind === 'users' && $item->exists && $item->role !== $data['role']) {
            return back()->withErrors(['role' => 'Peran akun yang sudah dibuat tidak dapat diubah. Buat akun terpisah untuk peran lain.']);
        }
        if ($kind === 'users' && $id === auth()->id() && ($data['role'] !== 'admin' || $data['status'] !== 'aktif')) {
            return back()->withErrors(['status' => 'Akun admin yang sedang digunakan harus tetap aktif.']);
        }
        DB::transaction(function () use ($data, $kind, $item) {
            if ($kind === 'years' && $data['active']) {
                AcademicYear::query()->update(['active' => false]);
            }
            if ($kind === 'users') {
                if ($item->exists && $item->email !== $data['email']) {
                    $item->google_id = null;
                }if ($data['role'] !== 'siswa') {
                    $data['class_id'] = null;
                    $data['nis'] = null;
                }
            }
            $item->fill($data)->save();
            if ($kind === 'users') {
                DB::table('class_students')->where('student_id', $item->id)->delete();
                if ($item->class_id) {
                    DB::table('class_students')->insert(['student_id' => $item->id, 'class_id' => $item->class_id, 'created_at' => now(), 'updated_at' => now()]);
                }
            }
            Audit::log('Data '.$kind.' disimpan: '.$item->name);
        });

        return redirect('/admin/'.$kind)->with('success', $kind === 'users' ? 'Pengguna berhasil disimpan. Login menggunakan '.$item->email.' dan password yang dibuat.' : 'Data berhasil disimpan.');
    }

    public function delete(string $kind, int $id)
    {
        [$model] = $this->definition($kind);
        $item = $model::findOrFail($id);
        if ($kind === 'users') {
            abort_if($id === auth()->id(), 422, 'Anda tidak dapat menghapus akun sendiri.');
        }if ($kind === 'classes' && $item->students()->exists()) {
            return back()->withErrors(['delete' => 'Pindahkan siswa sebelum menghapus kelas.']);
        }try {
            $item->delete();
            Audit::log('Data '.$kind.' dihapus: '.$item->name);
        } catch (QueryException $e) {
            return back()->withErrors(['delete' => 'Data masih digunakan. Nonaktifkan atau hapus relasi terlebih dahulu.']);
        }

return back()->with('success', 'Data berhasil dihapus.');
    }

    public function assignments()
    {
        return view('admin.assignments', ['teachers' => User::where('role', 'guru')->get(), 'classes' => SchoolClass::with('teachers')->get()]);
    }

    public function assign(Request $r)
    {
        $d = $r->validate(['teacher_id' => ['required', Rule::exists('users', 'id')->where('role', 'guru')], 'class_id' => 'required|exists:classes,id']);
        $c = SchoolClass::findOrFail($d['class_id']);
        $r->boolean('remove') ? $c->teachers()->detach($d['teacher_id']) : $c->teachers()->syncWithoutDetaching([$d['teacher_id']]);
        Audit::log('Guru pengampu diperbarui: '.$c->name);

        return back()->with('success', 'Guru pengampu diperbarui.');
    }

    public function logs()
    {
        return view('admin.logs', ['logs' => ActivityLog::with('user')->latest()->paginate(30)]);
    }
}
