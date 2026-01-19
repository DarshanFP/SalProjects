<div class="mb-3 card">
    <div class="card-header">
        <h4>Edit: Present situation of the inmates</h4>
    </div>
    <div class="card-body">
        <div class="mb-3">
            <label for="internal_challenges" class="form-label">(1) Internal challenges faced from inmates</label>
            <textarea name="internal_challenges" class="form-control sustainability-textarea" rows="3" placeholder="Describe internal challenges">{{ $presentSituation->internal_challenges ?? '' }}</textarea>
        </div>

        <div class="mb-3">
            <label for="external_challenges" class="form-label">(2) External challenges / Present difficulties</label>
            <textarea name="external_challenges" class="form-control sustainability-textarea" rows="3" placeholder="Describe external challenges">{{ $presentSituation->external_challenges ?? '' }}</textarea>
        </div>
    </div>

    <div class="card-header">
        <h4>Edit: Area of focus for the current year</h4>
    </div>
    <div class="card-body">
        <div class="mb-3">
            <label for="area_of_focus" class="form-label">
                What are the main areas you want to focus on for the growth/development of the institution and children? Explain why you want to focus on these areas and what you intend to achieve?
            </label>
            <textarea name="area_of_focus" class="form-control sustainability-textarea" rows="4" placeholder="Specify areas of focus">{{ $presentSituation->area_of_focus ?? '' }}</textarea>
        </div>
    </div>
</div>
