{{-- resources/views/projects/partials/Show/sustainability.blade.php --}}
<div class="mb-4 card">
    <div class="card-header">
        <h4 class="mb-0">Project Sustainability, Monitoring, and Methodologies</h4>
    </div>
    <div class="card-body">
        @forelse($project->sustainabilities as $sustainability)
            <div class="info-grid">
                <!-- Resilience Section -->
                <div class="info-label"><strong>Sustainability of the Project:</strong></div>
                <div class="info-value">{{ $sustainability->sustainability ?? 'N/A' }}</div>

                <!-- Monitoring Process Section -->
                <div class="info-label"><strong>Monitoring Process of the Project:</strong></div>
                <div class="info-value">{{ $sustainability->monitoring_process ?? 'N/A' }}</div>

                <!-- Reporting Methodology Section -->
                <div class="info-label"><strong>Methodology of Reporting:</strong></div>
                <div class="info-value">{{ $sustainability->reporting_methodology ?? 'N/A' }}</div>

                <!-- Evaluation Methodology Section -->
                <div class="info-label"><strong>Methodology of Evaluation:</strong></div>
                <div class="info-value">{{ $sustainability->evaluation_methodology ?? 'N/A' }}</div>
            </div>
        @empty
            <p>No sustainability information is available for this project.</p>
        @endforelse
    </div>
</div>
