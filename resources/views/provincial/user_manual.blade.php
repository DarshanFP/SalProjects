@extends('provincial.dashboard')

@section('content')
<div class="page-content">
    <div class="row justify-content-center">
        <div class="col-12 col-xl-10">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">
                        <i data-feather="book" class="me-2"></i>User Manual
                    </h4>
                </div>
                <div class="card-body">
                    <div class="user-manual-content prose">
                        {!! $content !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.user-manual-content h1 { font-size: 1.75rem; margin-top: 1.5rem; margin-bottom: 0.75rem; font-weight: 600; }
.user-manual-content h1:first-child { margin-top: 0; }
.user-manual-content h2 { font-size: 1.35rem; margin-top: 1.25rem; margin-bottom: 0.5rem; font-weight: 600; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 0.25rem; }
.user-manual-content h3 { font-size: 1.15rem; margin-top: 1rem; margin-bottom: 0.4rem; font-weight: 600; }
.user-manual-content h4 { font-size: 1.05rem; margin-top: 0.75rem; margin-bottom: 0.35rem; font-weight: 600; }
.user-manual-content p { margin-bottom: 0.75rem; line-height: 1.6; }
.user-manual-content ul, .user-manual-content ol { margin-bottom: 0.75rem; padding-left: 1.5rem; }
.user-manual-content li { margin-bottom: 0.25rem; line-height: 1.5; }
.user-manual-content strong { font-weight: 600; }
.user-manual-content hr { border: 0; border-top: 1px solid rgba(255,255,255,0.1); margin: 1.5rem 0; }
.user-manual-content table { width: 100%; border-collapse: collapse; margin-bottom: 1rem; }
.user-manual-content th, .user-manual-content td { border: 1px solid rgba(255,255,255,0.15); padding: 0.5rem 0.75rem; text-align: left; }
.user-manual-content th { font-weight: 600; background: rgba(255,255,255,0.05); }
.user-manual-content a { color: var(--bs-primary, #6571ff); text-decoration: none; }
.user-manual-content a:hover { text-decoration: underline; }
.user-manual-content code { background: rgba(255,255,255,0.08); padding: 0.2em 0.4em; border-radius: 4px; font-size: 0.9em; }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof feather !== 'undefined') feather.replace();
});
</script>
@endpush
@endsection
