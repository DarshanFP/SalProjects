<div class="mb-4 card">
    <div class="card-header">
        <h4 class="mb-0">Edit: Attach the following documents of the beneficiary:</h4>
    </div>
    <div class="card-body">
        <div class="row">
            <!-- LEFT COLUMN -->
            <div class="col-md-6">
                @foreach ($project->iahDocuments as $doc)
                    @foreach ([
                        'aadhar_copy' => 'Self-attested Aadhar',
                        'request_letter' => 'Request Letter',
                    ] as $name => $label)
                        <div class="mb-3">
                            <label class="form-label">{{ $label }}:</label>
                            <input type="file" name="attachments[{{ $doc->id }}][{{ $name }}]" class="form-control" onchange="renameFile(this, '{{ $name }}')">

                            @if(!empty($doc->$name))
                                <p>Currently Attached:</p>
                                <a href="{{ Storage::url($doc->$name) }}" target="_blank">
                                    {{ basename($doc->$name) }}
                                </a>
                                <br>
                                <a href="{{ Storage::url($doc->$name) }}" download class="btn btn-green">
                                    Download
                                </a>
                            @endif
                        </div>
                    @endforeach
                @endforeach
            </div>

            <!-- RIGHT COLUMN -->
            <div class="col-md-6">
                @foreach ($project->iahDocuments as $doc)
                    @foreach ([
                        'medical_reports' => 'Medical Reports',
                        'other_docs' => 'Other Supporting Documents',
                    ] as $name => $label)
                        <div class="mb-3">
                            <label class="form-label">{{ $label }}:</label>
                            <input type="file" name="attachments[{{ $doc->id }}][{{ $name }}]" class="form-control" onchange="renameFile(this, '{{ $name }}')">

                            @if(!empty($doc->$name))
                                <p>Currently Attached:</p>
                                <a href="{{ Storage::url($doc->$name) }}" target="_blank">
                                    {{ basename($doc->$name) }}
                                </a>
                                <br>
                                <a href="{{ Storage::url($doc->$name) }}" download class="btn btn-green">
                                    Download
                                </a>
                            @endif
                        </div>
                    @endforeach
                @endforeach
            </div>
        </div>

        <!-- If no documents exist, show upload form -->
        @if($project->iahDocuments->isEmpty())
            <div class="row">
                <div class="col-md-6">
                    @foreach ([
                        'aadhar_copy' => 'Self-attested Aadhar',
                        'request_letter' => 'Request Letter',
                    ] as $name => $label)
                        <div class="mb-3">
                            <label class="form-label">{{ $label }}:</label>
                            <input type="file" name="attachments[new][{{ $name }}]" class="form-control">
                        </div>
                    @endforeach
                </div>
                <div class="col-md-6">
                    @foreach ([
                        'medical_reports' => 'Medical Reports',
                        'other_docs' => 'Other Supporting Documents',
                    ] as $name => $label)
                        <div class="mb-3">
                            <label class="form-label">{{ $label }}:</label>
                            <input type="file" name="attachments[new][{{ $name }}]" class="form-control">
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>

<!-- Styles -->
<style>
.form-control {
    background-color: #202ba3;
    color: white;
}
.btn {
    display: inline-block;
    padding: 5px 10px;
    font-size: 12px;
    font-weight: bold;
    text-align: center;
    text-decoration: none;
    border-radius: 4px;
}
.btn-green {
    background-color: #28a745;
    color: white;
    border: none;
    cursor: pointer;
}
.btn-green:hover {
    background-color: #218838;
}
.mb-3 {
    margin-bottom: 20px;
}
.mb-3 p {
    margin-bottom: 5px;
}
p a {
    display: block;
    margin-bottom: 5px;
}
</style>

<!-- JavaScript -->
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
            return;
        }

        const extension = file.name.split('.').pop();
        const newFileName = `${projectId}_${label}.${extension}`;

        const dataTransfer = new DataTransfer();
        const renamedFile = new File([file], newFileName, { type: file.type });
        dataTransfer.items.add(renamedFile);
        input.files = dataTransfer.files;
    }
</script>
