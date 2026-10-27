<?php

namespace App\Http\Controllers;

use App\Models\SchoolClass;
use App\Models\User;
use App\Services\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportController extends Controller
{
    public function index()
    {
        return view('admin.import', ['rows' => session('import_rows', [])]);
    }

    public function preview(Request $r)
    {
        $r->validate(['file' => 'required|file|mimes:csv,txt,xlsx|max:2048']);
        if (strtolower($r->file('file')->getClientOriginalExtension()) === 'xlsx') {
            $zip = new \ZipArchive;
            if ($zip->open($r->file('file')->getRealPath()) !== true) {
                return back()->withErrors(['file' => 'Arsip XLSX tidak valid.']);
            }$bytes = 0;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $bytes += $zip->statIndex($i)['size'];
            }$zip->close();
            if ($bytes > 20 * 1024 * 1024) {
                return back()->withErrors(['file' => 'Isi XLSX terlalu besar. Pecah menjadi file yang lebih kecil.']);
            }
        }
        try {
            $sheet = IOFactory::load($r->file('file')->getRealPath())->getActiveSheet();
            if ($sheet->getHighestDataRow() > 501) {
                return back()->withErrors(['file' => 'Maksimal 500 siswa per import.']);
            }$all = $sheet->toArray(null, false, false, false);
        } catch (\Throwable $e) {
            return back()->withErrors(['file' => 'File tidak dapat dibaca. Gunakan CSV atau XLSX.']);
        }
        $header = array_map(fn ($x) => strtolower(trim((string) $x)), array_shift($all) ?? []);
        if (array_slice($header, 0, 4) !== ['nis', 'nama', 'email', 'kelas']) {
            return back()->withErrors(['file' => 'Header harus NIS, Nama, Email, Kelas.']);
        }
        $rows = [];
        $emails = [];
        $nis = [];
        foreach ($all as $i => $row) {
            if (! array_filter($row, fn ($x) => $x !== null && $x !== '')) {
                continue;
            }$d = ['nis' => trim((string) ($row[0] ?? '')), 'name' => trim((string) ($row[1] ?? '')), 'email' => strtolower(trim((string) ($row[2] ?? ''))), 'class_name' => trim((string) ($row[3] ?? ''))];
            $d['class_id'] = SchoolClass::where('name', $d['class_name'])->value('id');
            $v = Validator::make($d, ['nis' => 'required|string|max:50|unique:users,nis', 'name' => 'required|string|max:120', 'email' => 'required|email|max:190|unique:users,email', 'class_id' => 'required|exists:classes,id']);
            $errors = $v->errors()->all();
            if (isset($emails[$d['email']]) || isset($nis[$d['nis']])) {
                $errors[] = 'Email atau NIS duplikat di file.';
            }$emails[$d['email']] = true;
            $nis[$d['nis']] = true;
            $rows[] = $d + ['line' => $i + 2, 'errors' => $errors];
        }
        $r->session()->put('import_rows', $rows);

        return redirect('/admin/import');
    }

    public function commit(Request $r)
    {
        $rows = $r->session()->pull('import_rows', []);
        $ok = 0;
        $failed = [];
        foreach ($rows as $d) {
            if ($d['errors']) {
                $failed[] = 'Baris '.$d['line'].': '.implode(' ', $d['errors']);

                continue;
            }
            try {
                DB::transaction(function () use ($d) {
                    $u = User::create(collect($d)->only(['nis', 'name', 'email', 'class_id'])->all() + ['role' => 'siswa', 'status' => 'aktif']);
                    DB::table('class_students')->insert(['class_id' => $u->class_id, 'student_id' => $u->id, 'created_at' => now(), 'updated_at' => now()]);
                });
                $ok++;
            } catch (\Throwable $e) {
                $failed[] = 'Baris '.$d['line'].': email/NIS sudah digunakan atau kelas berubah. Silakan periksa kembali.';
            }
        }
        Audit::log('Import siswa: '.$ok.' berhasil, '.count($failed).' gagal');

        return back()->with('success', "Berhasil: $ok siswa. Gagal: ".count($failed).' siswa.')->with('import_errors',$failed);
    }
}
