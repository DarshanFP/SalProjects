<div class="mb-4 card">
    <div class="card-header">
        <h4 class="mb-0">Project Sustainability, Monitoring and Methodologies</h4>
    </div>
    <div class="card-body">
        @forelse($project->sustainabilities as $sustainability)
            <!-- Resilience Section -->
            <div class="mb-3">
                <h5>Explain the Sustainability of the Project:</h5>
                <p>{{ $sustainability->sustainability ?? 'N/A' }}</p>
            </div>

            <!-- Monitoring Process Section -->
            <div class="mb-3">
                <h5>Explain the Monitoring Process of the Project:</h5>
                <p>{{ $sustainability->monitoring_process ?? 'N/A' }}</p>
            </div>

            <!-- Reporting Methodology Section -->
            <div class="mb-3">
                <h5>Explain the Methodology of Reporting:</h5>
                <p>{{ $sustainability->reporting_methodology ?? 'N/A' }}</p>
            </div>

            <!-- Evaluation Methodology Section -->
            <div class="mb-3">
                <h5>Explain the Methodology of Evaluation:</h5>
                <p>{{ $sustainability->evaluation_methodology ?? 'N/A' }}</p>
            </div>
        @empty
            <p>No sustainability information available for this project.</p>
        @endforelse
    </div>
</div>
