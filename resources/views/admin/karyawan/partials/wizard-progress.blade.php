@php
    $stepLabels = $stepLabels ?? [];
    $activeStep = $activeStep ?? 0;
@endphp

<div class="employee-wizard-progress employee-wizard-progress--header" role="list" aria-label="Tahapan form karyawan">
    @foreach ($stepLabels as $index => $label)
        <div class="employee-wizard-progress-item{{ $index === $activeStep ? ' is-active' : '' }}{{ $index < $activeStep ? ' is-complete' : '' }}" data-wizard-progress-item="{{ $index }}" role="listitem">
            <span class="employee-wizard-progress-index">{{ $index + 1 }}</span>
            <span class="employee-wizard-progress-label">{{ $label }}</span>
        </div>
    @endforeach
</div>
