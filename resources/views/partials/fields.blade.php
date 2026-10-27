@foreach($fields as $key=>$label)
@php
$defaultValue = match($key) {
    'role' => request('role', 'siswa'),
    'status' => 'aktif',
    'sort_order', 'week' => 1,
    'points' => 100,
    'deadline_at' => $edit?->deadline_at?->format('Y-m-d\\TH:i') ?? ($edit?->deadline ? $edit->deadline->copy()->endOfDay()->format('Y-m-d\\TH:i') : ''),
    'allow_late_submissions' => (int) ($edit?->allow_late_submissions ?? false),
    default => $edit?->$key ?? '',
};
$value=old($key,$defaultValue);
$options=match($key){'role'=>['siswa'=>'Siswa','guru'=>'Guru','admin'=>'Admin'],'status'=>['aktif'=>'Aktif','nonaktif'=>'Nonaktif'],'semester'=>['Ganjil'=>'Ganjil','Genap'=>'Genap'],'active'=>['1'=>'Aktif','0'=>'Nonaktif'],'allow_late_submissions'=>['0'=>'Tolak setelah deadline','1'=>'Terima dan tandai terlambat'],'level'=>['X'=>'X','XI'=>'XI','XII'=>'XII'],'type'=>['pretest'=>'Pre-test','posttest'=>'Post-test'],'difficulty'=>['Mudah'=>'Mudah','Menengah'=>'Menengah','Sulit'=>'Sulit'],'class_id'=>($classes??collect())->pluck('name','id')->all(),'academic_year_id'=>($years??collect())->pluck('name','id')->all(),'material_id'=>($materials??collect())->pluck('title','id')->all(),'correct_answer'=>['A'=>'A','B'=>'B','C'=>'C','D'=>'D'],default=>null};
@endphp
<div class="mb-3"><label for="f-{{ $key }}" class="form-label">{{ $label }}</label>
@if($options!==null)<select name="{{ $key }}" id="f-{{ $key }}" class="form-select"><option value="">Pilih {{ strtolower($label) }}</option>@foreach($options as $v=>$text)<option value="{{ $v }}" @selected((string)$value===(string)$v)>{{ $text }}</option>@endforeach</select>
@elseif(in_array($key,['content','description','expected_query','steps','question']))<textarea class="form-control {{ $key==='expected_query'?'font-monospace':'' }}" id="f-{{ $key }}" name="{{ $key }}" rows="{{ $key==='content'?7:4 }}">{{ $key==='steps' && $edit?old('steps',$edit->steps->pluck('title')->implode("\n")):$value }}</textarea>
@else<input class="form-control" id="f-{{ $key }}" name="{{ $key }}" type="{{ match($key){'email'=>'email','file'=>'file','deadline'=>'date','deadline_at'=>'datetime-local','week','sort_order','points','duration_minutes'=>'number','youtube_url'=>'url',default=>'text'} }}" @if($key!=='file') value="{{ $value }}" @else accept=".pdf,.docx" @endif @if($key==='duration_minutes') min="1" max="480" placeholder="Kosongkan untuk tanpa batas waktu" @endif @if($key==='youtube_url') placeholder="https://www.youtube.com/watch?v=..." maxlength="2048" @endif>@endif
@if($key==='youtube_url')<small class="form-text text-secondary">Tempel tautan video pembelajaran yang ingin ditampilkan kepada siswa. YouTube watch, youtu.be, embed, dan Shorts didukung.</small>@endif
</div>@if(($kind??null)==='users' && $key==='email')<div class="mb-3"><label for="f-password" class="form-label">Password</label><input class="form-control" id="f-password" name="password" type="password" minlength="8" autocomplete="new-password" @required(!$edit)><small class="form-text text-secondary">@if($edit)Kosongkan jika tidak ingin mengganti password.@elseMinimal 8 karakter; digunakan bersama email untuk login.@endif</small></div>@endif @endforeach
