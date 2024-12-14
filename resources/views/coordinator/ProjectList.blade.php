@extends('coordinator.dashboard')

@section('content')
<div class="page-content">
    <div class="row justify-content-center">
        <div class="col-md-12 col-xl-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="fp-text-center1">ALL PROJECTS (Coordinator View)</h4>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('coordinator.projects.list') }}" id="filterForm">
                        <div class="mb-3 row">
                            <div class="col-md-3">
                                <select name="province" id="provinceSelect" class="form-control">
                                    <option value="">Filter by Province</option>
                                    @foreach($provinces as $province)
                                        <option value="{{ $province }}" {{ request('province') == $province ? 'selected' : '' }}>
                                            {{ $province }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3">
                                <select name="project_type" class="form-control">
                                    <option value="">Filter by Project Type</option>
                                    @foreach($projectTypes as $type)
                                        <option value="{{ $type }}" {{ request('project_type') == $type ? 'selected' : '' }}>
                                            {{ $type }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3">
                                <select name="user_id" id="executorSelect" class="form-control">
                                    <option value="">Filter by Executor</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                            {{ $user->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3">
                                <button type="submit" class="btn btn-primary">Filter</button>
                            </div>
                        </div>
                    </form>

                    <style>
                        table.my-projects-table {
                            table-layout: fixed;
                            width: 100%;
                        }

                        table.my-projects-table th,
                        table.my-projects-table td {
                            vertical-align: middle;
                            text-align: center;
                            padding: 8px;
                            white-space: normal !important;
                            word-break: break-word;
                            overflow-wrap: break-word;
                        }

                        /* Adjusting specific columns */
                        table.my-projects-table th.project-title,
                        table.my-projects-table td.project-title,
                        table.my-projects-table th.project-type,
                        table.my-projects-table td.project-type {
                            text-align: left;
                            width: 20%; /* Adjust as needed */
                        }

                        table.my-projects-table th.amount-sanctioned,
                        table.my-projects-table td.amount-sanctioned,
                        table.my-projects-table th.amount-forwarded,
                        table.my-projects-table td.amount-forwarded {
                            width: 10%; /* Adjust width as needed */
                            text-align: center;
                        }
                    </style>

                    <div class="table-responsive">
                        <table class="table table-bordered my-projects-table">
                            <thead>
                                <tr>
                                    <th>Project ID</th>
                                    <th>Executor</th>
                                    <th class="project-title">Project Title</th>
                                    <th class="project-type">Project Type</th>
                                    <th class="amount-sanctioned">Amount Sanctioned</th>
                                    <th class="amount-forwarded">Amount Forwarded</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($projects as $project)
                                    <tr>
                                        <td>{{ $project->project_id }}</td>
                                        <td>{{ $project->user->name }}</td>
                                        <td class="project-title">{{ $project->project_title }}</td>
                                        <td class="project-type">{{ $project->project_type }}</td>
                                        <td class="amount-sanctioned">{{ number_format($project->amount_sanctioned, 2) }}</td>
                                        <td class="amount-forwarded">{{ number_format($project->amount_forwarded, 2) }}</td>
                                        <td>
                                            {{ \App\Models\OldProjects\Project::$statusLabels[$project->status] ?? $project->status }}
                                        </td>
                                        <td>
                                            <a href="{{ route('coordinator.projects.show', $project->project_id) }}" class="btn btn-primary btn-sm">View Project</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center">No projects found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Include jQuery if not already included --}}
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
    $(document).ready(function(){
        $('#provinceSelect').on('change', function(){
            var selectedProvince = $(this).val();
            $.ajax({
                url: "{{ route('coordinator.executors.byProvince') }}",
                type: "GET",
                data: { province: selectedProvince },
                success: function(data) {
                    var executorSelect = $('#executorSelect');
                    executorSelect.empty();
                    executorSelect.append('<option value="">Filter by Executor</option>');
                    $.each(data, function(index, executor) {
                        executorSelect.append('<option value="'+ executor.id +'">'+ executor.name +'</option>');
                    });
                }
            });
        });
    });
</script>

@endsection
