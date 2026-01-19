{{-- resources/views/projects/partials/Edit/IES/educational_background.blade.php --}}
<div class="mb-3 card">
    <div class="card-header">
        <h4>Edit: Educational Background / Present Education (support requested)</h4>
    </div>
    <div class="card-body">
        @if($project->iesEducationBackground)
            @php
                $educationBackground = $project->iesEducationBackground;
            @endphp
        @else
            @php
                $educationBackground = new \App\Models\OldProjects\IES\ProjectIESEducationBackground();
            @endphp
        @endif

        <!-- Previous Class/Studies Information -->
        <div class="form-group">
            <label>Mention the previous class/studies for which the project support is given:</label>
            <input type="text" name="previous_class" class="form-control" value="{{ old('previous_class', $educationBackground->previous_class) }}">
        </div>

        <div class="form-group">
            <label>Amount sanctioned in the previous year:</label>
            <input type="number" step="0.01" name="amount_sanctioned" class="form-control" value="{{ old('amount_sanctioned', $educationBackground->amount_sanctioned) }}">
        </div>

        <div class="form-group">
            <label>Amount utilized:</label>
            <input type="number" step="0.01" name="amount_utilized" class="form-control" value="{{ old('amount_utilized', $educationBackground->amount_utilized) }}">
        </div>

        <div class="form-group">
            <label>The total amount of scholarship availed by the beneficiary from the government / any other agency in the previous year:</label>
            <input type="number" step="0.01" name="scholarship_previous_year" class="form-control" value="{{ old('scholarship_previous_year', $educationBackground->scholarship_previous_year) }}">
        </div>

        <div class="form-group">
            <label>Explain the academic and overall performance of the beneficiary:</label>
            <textarea name="academic_performance" class="form-control" rows="4">{{ old('academic_performance', $educationBackground->academic_performance) }}</textarea>
        </div>
    </div>

    <div class="card-header">
        <h4>Information on 2nd Phase of Studies</h4>
    </div>
    <div class="card-body">
        <!-- Present Class/Year of Study -->
        <div class="form-group">
            <label>Present class / year of study:</label>
            <input type="text" name="present_class" class="form-control" value="{{ old('present_class', $educationBackground->present_class) }}">
        </div>

        <div class="form-group">
            <label>Expected amount of Scholarship:</label>
            <input type="number" step="0.01" name="expected_scholarship" class="form-control" value="{{ old('expected_scholarship', $educationBackground->expected_scholarship) }}">
        </div>

        <div class="form-group">
            <label>Financial contribution from the family:</label>
            <input type="number" step="0.01" name="family_contribution" class="form-control" value="{{ old('family_contribution', $educationBackground->family_contribution) }}">
        </div>

        <div class="form-group">
            <label>If no support from the family, mention the reasons:</label>
            <textarea name="reason_no_support" class="form-control" rows="3">{{ old('reason_no_support', $educationBackground->reason_no_support) }}</textarea>
        </div>
    </div>
</div>

<!-- Styles -->
<style>
    .form-control {
        background-color: #202ba3;
        color: white;
    }
</style>
