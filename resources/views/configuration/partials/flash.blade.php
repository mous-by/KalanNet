@if(session('success'))
    <div class="alert alert-success border-0 shadow-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger border-0 shadow-sm">{{ session('error') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger border-0 shadow-sm">{{ $errors->first() }}</div>
@endif
