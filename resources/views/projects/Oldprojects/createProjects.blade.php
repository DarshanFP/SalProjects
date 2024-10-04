{{-- resources/views/projects/Oldprojects/createProjects.blade.php --}}
@extends('executor.dashboard')

@section('content')
<div class="page-content">
    <div class="row justify-content-center">
        <div class="col-md-12 col-xl-12">
            <form action="{{ route('projects.store') }}" method="POST" enctype="multipart/form-data"  >
                @csrf
                <div class="mb-3 card">
                    <div class="card-header">
                        <h4 class="fp-text-center1">PROJECT APPLICATION FORM</h4>
                    </div>
                    <div class="card-header">
                        <h4 class="fp-text-margin">General Information</h4>
                    </div>

                    <!-- General Information Fields -->
                    <div class="card-body">
                        @include('projects.partials.general_info')
                    </div>
                </div>

                <!-- Key Information Section -->
                @include('projects.partials.key_information')

                <!-- Logical Framework Section -->
                @include('projects.partials.logical_framework')

                <!-- Project Sustainability, Monitoring, and Evaluation Framework -->
                @include('projects.partials.sustainability')

                <!-- Budget Section -->
                @include('projects.partials.budget')

                <!-- Attachments Section -->
                @include('projects.partials.attachments')

                <button type="submit" class="btn btn-primary me-2">Submit Application</button>
            </form>
        </div>
    </div>
</div>

@include('projects.partials.scripts')

<style>
    .readonly-input {
        background-color: #0D1427;
        color: #f4f0f0;
    }

    .select-input {
        background-color: #112f6b;
        color: #f4f0f0;
    }

    .readonly-select {
        background-color: #092968;
        color: #f4f0f0;
    }

    .table th, .table td {
        vertical-align: middle;
        text-align: center;
        padding: 0;
    }

    .table th {
        white-space: normal;
    }

    .table td input {
        width: 100%;
        box-sizing: border-box;
        -moz-appearance: textfield;
        padding: 0.375rem 0.75rem;
    }

    .table td input::-webkit-outer-spin-button,
    .table td input::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    .table-container {
        overflow-x: auto;
    }

    .fp-text-center1 {
        text-align: center;
        margin-bottom: 15px;
    }

    .fp-text-margin {
        margin-bottom: 15px;
    }
</style>
@endsection
