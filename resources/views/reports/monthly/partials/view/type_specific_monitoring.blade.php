{{-- Provincial type-specific monitoring: LDP, IGE, RST, CIC (Phase 4); Individual, Development/CCI/Rural-Urban-Tribal/NEXT PHASE, Beneficiary (Phase 5). --}}
@php
    $checks = $typeSpecificChecks ?? [];
    $showStatuses = ['submitted_to_provincial', 'forwarded_to_coordinator'];
    $showByStatus = in_array($report->status ?? '', $showStatuses);
    $hasLdp = isset($checks['ldp']) && count($checks['ldp']['alerts'] ?? []) > 0;
    $hasIge = isset($checks['ige']) && count($checks['ige']['alerts'] ?? []) > 0;
    $hasRst = isset($checks['rst']) && count($checks['rst']['alerts'] ?? []) > 0;
    $hasCic = isset($checks['cic']) && count($checks['cic']['alerts'] ?? []) > 0;
    $hasIndividual = isset($checks['individual']) && count($checks['individual']['alerts'] ?? []) > 0;
    $hasDevelopment = isset($checks['development']) && count($checks['development']['alerts'] ?? []) > 0;
    $hasBeneficiary = isset($checks['beneficiary']) && count($checks['beneficiary']['alerts'] ?? []) > 0;
    $hasAny = $hasLdp || $hasIge || $hasRst || $hasCic || $hasIndividual || $hasDevelopment || $hasBeneficiary;
    $showByRole = in_array(auth()->user()->role ?? '', ['provincial', 'coordinator']);
@endphp
@if($showByStatus && $showByRole && $hasAny)
<div class="mb-3 card border-warning">
    <div class="card-header bg-warning bg-opacity-25">
        <h4 class="fp-text-margin mb-0">Project-Type — Monitoring</h4>
    </div>
    <div class="card-body">
        @if($hasLdp)
        <div class="mb-4">
            <h5 class="text-secondary">LDP (Annexure)</h5>
            <ul class="mb-0">
                @foreach($checks['ldp']['alerts'] ?? [] as $msg)
                    <li>{{ $msg }}</li>
                @endforeach
            </ul>
            @if(!empty($checks['ldp']['meta']['count']))
                <small class="text-muted">Annexure entries: {{ $checks['ldp']['meta']['count'] }}</small>
            @endif
        </div>
        @endif

        @if($hasIge)
        <div class="mb-4">
            <h5 class="text-secondary">IGE (Age profile)</h5>
            <ul class="mb-0">
                @foreach($checks['ige']['alerts'] ?? [] as $msg)
                    <li>{{ $msg }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        @if($hasRst)
        <div class="mb-4">
            <h5 class="text-secondary">RST (Trainees)</h5>
            <ul class="mb-0">
                @foreach($checks['rst']['alerts'] ?? [] as $msg)
                    <li>{{ $msg }}</li>
                @endforeach
            </ul>
            @if(isset($checks['rst']['meta']['total']) || isset($checks['rst']['meta']['sum_categories']))
                <small class="text-muted">
                    Total: {{ $checks['rst']['meta']['total'] ?? '—' }};
                    Sum of categories: {{ $checks['rst']['meta']['sum_categories'] ?? '—' }}
                </small>
            @endif
        </div>
        @endif

        @if($hasCic)
        <div class="mb-4">
            <h5 class="text-secondary">CIC (Inmates)</h5>
            <ul class="mb-0">
                @foreach($checks['cic']['alerts'] ?? [] as $msg)
                    <li>{{ $msg }}</li>
                @endforeach
            </ul>
            @if(isset($checks['cic']['meta']['grand_total']))
                <small class="text-muted">Grand Total: {{ $checks['cic']['meta']['grand_total'] }}</small>
            @endif
        </div>
        @endif

        @if($hasIndividual)
        <div class="mb-4">
            <h5 class="text-secondary">Individual (ILP, IAH, IES, IIES)</h5>
            <ul class="mb-0">
                @foreach($checks['individual']['alerts'] ?? [] as $msg)
                    <li>{{ $msg }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        @if($hasDevelopment)
        <div class="mb-4">
            <h5 class="text-secondary">Development / CCI / Rural-Urban-Tribal / NEXT PHASE</h5>
            <ul class="mb-0">
                @foreach($checks['development']['alerts'] ?? [] as $msg)
                    <li>{{ $msg }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        @if($hasBeneficiary)
        <div>
            <h5 class="text-secondary">Beneficiary consistency</h5>
            <ul class="mb-0">
                @foreach($checks['beneficiary']['alerts'] ?? [] as $msg)
                    <li>{{ $msg }}</li>
                @endforeach
            </ul>
            @if(isset($checks['beneficiary']['meta']['type_specific_total']))
                <small class="text-muted">Type-specific total: {{ $checks['beneficiary']['meta']['type_specific_total'] }}</small>
            @endif
        </div>
        @endif
    </div>
</div>
@endif
