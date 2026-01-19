{{-- <pre>{{ print_r($ILPStrengthWeakness, true) }}</pre> --}}
@php
    $strengths = is_array($ILPStrengthWeakness['strengths'] ?? null) ? $ILPStrengthWeakness['strengths'] : [];
    $weaknesses = is_array($ILPStrengthWeakness['weaknesses'] ?? null) ? $ILPStrengthWeakness['weaknesses'] : [];
@endphp


<div class="mb-4 card">
    <div class="card-header">
        <h4 class="mb-0">IV. Strengths and Weaknesses of Business Initiative</h4>
    </div>
    <div class="card-body">

        <!-- Strengths Section -->
        <div class="mb-3">
            <label for="strengths" class="form-label">Strengths:</label>
            <div id="strengths-container">
                @if(is_array($strengths) && count($strengths) > 0)
                    @foreach($strengths as $index => $strength)
                        <div class="mb-2">
                            <strong>Strength {{ $index + 1 }}:</strong>
                            <div class="mt-1 form-control" style="white-space: pre-wrap; word-wrap: break-word; overflow-wrap: break-word; line-height: 1.6;">{{ $strength }}</div>
                        </div>
                    @endforeach
                @else
                    <div class="mt-2 form-control">No strengths provided.</div>
                @endif
            </div>
        </div>

        <!-- Weaknesses Section -->
        <div class="mt-4 mb-3">
            <label for="weaknesses" class="form-label">Weaknesses:</label>
            <div id="weaknesses-container">
                @if(is_array($weaknesses) && count($weaknesses) > 0)
                    @foreach($weaknesses as $index => $weakness)
                        <div class="mb-2">
                            <strong>Weakness {{ $index + 1 }}:</strong>
                            <div class="mt-1 form-control" style="white-space: pre-wrap; word-wrap: break-word; overflow-wrap: break-word; line-height: 1.6;">{{ $weakness }}</div>
                        </div>
                    @endforeach
                @else
                    <div class="mt-2 form-control">No weaknesses provided.</div>
                @endif
            </div>
        </div>

    </div>
</div>
