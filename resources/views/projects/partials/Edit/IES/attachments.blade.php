{{-- resources/views/projects/partials/Edit/IES/attachments.blade.php --}}
<div class="mb-4 card">
    <div class="card-header">
        <h4 class="mb-0">Edit: Attach the following documents of the beneficiary:</h4>
    </div>
    <div class="card-body">
        <div class="row">
            <!-- LEFT COLUMN -->
            <div class="col-md-6">
                @foreach ([
                    'aadhar_card' => 'Self-attested Aadhar',
                    'fee_quotation' => 'Fee Quotation from Institution',
                    'scholarship_proof' => 'Proof of Scholarship',
                    'medical_confirmation' => 'Medical Confirmation'
                ] as $name => $label)
                    <div class="mb-3">
                        <label class="form-label">{{ $label }}:</label>
                        <input type="file" name="{{ $name }}" class="form-control">
                        @if(!empty($IESAttachments->$name))
                            <p>Currently Attached:</p>
                            <a href="{{ asset('storage/' . str_replace('public/', '', $IESAttachments->$name)) }}" target="_blank">
                                {{ basename($IESAttachments->$name) }}
                            </a>
                            <br>
                            <a href="{{ asset('storage/' . str_replace('public/', '', $IESAttachments->$name)) }}" download class="btn btn-green">
                                Download
                            </a>
                        @endif
                    </div>
                @endforeach
            </div>

            <!-- RIGHT COLUMN -->
            <div class="col-md-6">
                @foreach ([
                    'caste_certificate' => 'Caste Certificate',
                    'self_declaration' => 'Self Declaration',
                    'death_certificate' => 'Death Certificate',
                    'request_letter' => 'Request Letter'
                ] as $name => $label)
                    <div class="mb-3">
                        <label class="form-label">{{ $label }}:</label>
                        <input type="file" name="{{ $name }}" class="form-control">
                        @if(!empty($IESAttachments->$name))
                            <p>Currently Attached:</p>
                            <a href="{{ asset('storage/' . str_replace('public/', '', $IESAttachments->$name)) }}" target="_blank">
                                {{ basename($IESAttachments->$name) }}
                            </a>
                            <br>
                            <a href="{{ asset('storage/' . str_replace('public/', '', $IESAttachments->$name)) }}" download class="btn btn-green">
                                Download
                            </a>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<!-- Styles -->
<style>
/* Style for the input field */
.form-control {
    background-color: #202ba3;
    color: white;
}

/* Button Styles */
.btn {
    display: inline-block;
    padding: 5px 10px; /* Reduced padding for a smaller button */
    font-size: 12px; /* Smaller font size */
    font-weight: bold;
    text-align: center;
    text-decoration: none;
    border-radius: 4px; /* Slightly rounded corners */
}

.btn-green {
    background-color: #28a745; /* Bootstrap green color */
    color: white;
    border: none;
    cursor: pointer;
}

.btn-green:hover {
    background-color: #218838;
}

/* Spacing between elements */
.mb-3 {
    margin-bottom: 20px; /* Adds breathable space between "Choose File" field and "Currently Attached" */
}

.mb-3 p {
    margin-bottom: 5px; /* Adjust spacing between the text and download button */
}

/* File name and link alignment */
p a {
    display: block; /* Make each link appear on a new line */
    margin-bottom: 5px; /* Adds space between "View" and "Download" links */
}
</style>

<!-- JavaScript for renaming files -->
<script>
    function renameFile(input, label) {
        const projectIdInput = document.querySelector('input[name="project_id"]');
        if (!projectIdInput) {
            console.warn("No <input name='project_id'> found in the parent form.");
            return;
        }

        const projectId = projectIdInput.value;
        const file = input.files[0];

        if (!file) {
            return; // No file selected
        }

        const extension = file.name.split('.').pop();
        const newFileName = `${projectId}_${label}.${extension}`;

        const dataTransfer = new DataTransfer();
        const renamedFile = new File([file], newFileName, { type: file.type });
        dataTransfer.items.add(renamedFile);
        input.files = dataTransfer.files;
    }
</script>
