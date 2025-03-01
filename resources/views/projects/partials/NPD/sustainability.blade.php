<div class="mb-4 card">
    <div class="card-header">
        <h4 class="mb-0">Project Sustainability, Monitoring and Methodologies - Next Phase</h4>
    </div>
    <div class="card-body">

        <!-- Sustainability Section -->
        <div class="mb-3">
            <h5>Explain the Sustainability of the Project:</h5>
            <textarea name="sustainability" class="form-control" rows="3" placeholder="Explain the sustainability of the project" style="background-color: #202ba3;">
                {{ old('sustainability', $predecessorSustainability['sustainability'] ?? '') }}
            </textarea>
        </div>

        <!-- Monitoring Process Section -->
        <div class="mb-3">
            <h5>Explain the Monitoring Process of the Project:</h5>
            <textarea name="monitoring_process" class="form-control" rows="3" placeholder="Explain the monitoring process of the project" style="background-color: #202ba3;">
                {{ old('monitoring_process', $predecessorSustainability['monitoring_process'] ?? '') }}
            </textarea>
        </div>

        <!-- Reporting Methodology Section -->
        <div class="mb-3">
            <h5>Explain the Methodology of Reporting:</h5>
            <textarea name="reporting_methodology" class="form-control" rows="3" placeholder="Explain the methodology of reporting" style="background-color: #202ba3;">
                {{ old('reporting_methodology', $predecessorSustainability['reporting_methodology'] ?? '') }}
            </textarea>
        </div>

        <!-- Evaluation Methodology Section -->
        <div class="mb-3">
            <h5>Explain the Methodology of Evaluation:</h5>
            <textarea name="evaluation_methodology" class="form-control" rows="3" placeholder="Explain the methodology of evaluation" style="background-color: #202ba3;">
                {{ old('evaluation_methodology', $predecessorSustainability['evaluation_methodology'] ?? '') }}
            </textarea>
        </div>

    </div>
</div>
