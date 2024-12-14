
<div class="mb-3 card">
    <div class="card-header">
        <h4>Economic Background of Parents</h4>
    </div>
    <div class="card-body">
        <div class="info-grid">
            <!-- Agricultural Labour -->
            <div class="info-label">Agricultural Labour:</div>
            <div class="info-value">{{ $economicBackground->agricultural_labour_number ?? 'N/A' }}</div>

            <!-- Marginal Farmers -->
            <div class="info-label">Marginal Farmers (less than two and half acres):</div>
            <div class="info-value">{{ $economicBackground->marginal_farmers_number ?? 'N/A' }}</div>

            <!-- Parents in Self-Employment -->
            <div class="info-label">Parents in Self-Employment:</div>
            <div class="info-value">{{ $economicBackground->self_employed_parents_number ?? 'N/A' }}</div>

            <!-- Parents Working in Informal Sector -->
            <div class="info-label">Parents Working in Informal Sector:</div>
            <div class="info-value">{{ $economicBackground->informal_sector_parents_number ?? 'N/A' }}</div>

            <!-- Any Other -->
            <div class="info-label">Any Other:</div>
            <div class="info-value">{{ $economicBackground->any_other_number ?? 'N/A' }}</div>

            <!-- General Remarks -->
            <div class="info-label">General Remarks:</div>
            <div class="info-value">{{ $economicBackground->general_remarks ?? 'No remarks provided.' }}</div>
        </div>
    </div>
</div>

