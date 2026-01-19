<div class="mb-4 card">
    <div class="card-header">
        <h4 class="mb-0">Project Sustainability, Monitoring, and Methodologies</h4>
    </div>
    <div class="card-body">
        @if($project->sustainabilities->isNotEmpty())
            @foreach($project->sustainabilities as $sustainability)
                <!-- Sustainability Section -->
                <div class="mb-3">
                    <h5>Explain the Sustainability of the Project:</h5>
                    <textarea name="sustainability" class="form-control sustainability-textarea" rows="3">{{ $sustainability->sustainability }}</textarea>
                </div>

                <!-- Monitoring Process Section -->
                <div class="mb-3">
                    <h5>Explain the Monitoring Process of the Project:</h5>
                    <textarea name="monitoring_process" class="form-control sustainability-textarea" rows="3">{{ $sustainability->monitoring_process }}</textarea>
                </div>

                <!-- Reporting Methodology Section -->
                <div class="mb-3">
                    <h5>Explain the Methodology of Reporting:</h5>
                    <textarea name="reporting_methodology" class="form-control sustainability-textarea" rows="3">{{ $sustainability->reporting_methodology }}</textarea>
                </div>

                <!-- Evaluation Methodology Section -->
                <div class="mb-3">
                    <h5>Explain the Methodology of Evaluation:</h5>
                    <textarea name="evaluation_methodology" class="form-control sustainability-textarea" rows="3">{{ $sustainability->evaluation_methodology }}</textarea>
                </div>
            @endforeach
        @else
            <p>No sustainability information available for this project.</p>
        @endif
    </div>
</div>
