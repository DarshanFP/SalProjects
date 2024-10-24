@extends('executor.dashboard')

@section('content')
<div class="page-content">
    <div class="row justify-content-center">
        <div class="col-md-12 col-xl-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="fp-text-center1">Edit Project</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('projects.update', $project->project_id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <!-- Project General Information -->
                        <div class="mb-3 card">
                            <div class="card-header">
                                <h4 class="fp-text-margin">General Information</h4>
                            </div>
                            <div class="card-body">
                                @include('projects.partials.Edit.general_info')
                            </div>
                        </div>

                        <!-- Key Information Section -->
                        @include('projects.partials.Edit.key_information')

                        <!-- CIC Specific Partial (After Key Information Section) -->
                        <div id="cic-section" style="display:none;">
                            @include('projects.partials.Edit.CIC.basic_info')
                        </div>

                        <!-- Edu-Rural-Urban-Tribal Specific Partials (After Key Information Section) -->
                        <div id="edu-rut-sections" style="display:none;">
                            <!-- Basic Information Partial for EduRUT -->
                            @include('projects.partials.Edit.Edu-RUT.basic_info')

                            <!-- Target Group Partial for EduRUT -->
                            @include('projects.partials.Edit.Edu-RUT.target_group')
                        </div>

                        <!-- Default Partial Sections -->
                        <div id="default-sections">
                            <!-- Logical Framework Section -->
                            @include('projects.partials.Edit.logical_framework')

                            <!-- Project Sustainability, Monitoring, and Evaluation Framework -->
                            @include('projects.partials.Edit.sustainibility')

                            <!-- Budget Section -->
                            @include('projects.partials.Edit.budget')

                            <!-- Attachments Section -->
                            @include('projects.partials.Edit.attachement')

                            <!-- Annexed Target Group Partial for EduRUT (After Attachments Section) -->
                            <div id="edu-rut-annexed-section" style="display:none;">
                                @include('projects.partials.Edit.Edu-RUT.annexed_target_group')
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary me-2">Update Project</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include the updated JavaScript code -->
@include('projects.partials.scripts-edit')

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const projectTypeDropdown = document.getElementById('project_type');
        const eduRUTSections = document.getElementById('edu-rut-sections');
        const eduRUTAnnexedSection = document.getElementById('edu-rut-annexed-section');
        const cicSection = document.getElementById('cic-section');

        function toggleSections() {
            const projectType = projectTypeDropdown.value;

            if (projectType === 'Rural-Urban-Tribal') {
                eduRUTSections.style.display = 'block';
                eduRUTAnnexedSection.style.display = 'block';
                cicSection.style.display = 'none';
            } else if (projectType === 'PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER') {
                cicSection.style.display = 'block';
                eduRUTSections.style.display = 'none';
                eduRUTAnnexedSection.style.display = 'none';
            } else {
                eduRUTSections.style.display = 'none';
                eduRUTAnnexedSection.style.display = 'none';
                cicSection.style.display = 'none';
            }
        }

        // Initial check when page loads
        toggleSections();

        // Event listener for dropdown change
        projectTypeDropdown.addEventListener('change', toggleSections);
    });
</script>

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
        padding: 0.375rem 0.75rem;
    }
    .table-container {
        overflow-x: auto;
    }
</style>
@endsection
