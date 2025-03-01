{{-- resources/views/projects/partials/Edit/ILP/attached_docs.blade.php --}}
<div class="mb-4 card">
    <div class="card-header">
        <h4 class="mb-0">Edit: Attach the following documents of the beneficiary:</h4>
    </div>
    <div class="card-body">
        <div class="row">
            <!-- LEFT COLUMN -->
            <div class="col-md-6">
                @foreach ([
                    'aadhar_doc' => 'Self-attested Aadhar',
                    'request_letter_doc' => 'Request Letter',
                ] as $name => $label)
                    <div class="mb-3">
                        <label class="form-label">{{ $label }}:</label>
                        <input type="file" name="attachments[{{ $name }}]" class="form-control" onchange="renameFile(this, '{{ $name }}')">

                        @if(!empty($attachedDocs->$name))
                            <p>Currently Attached:</p>
                            <a href="{{ asset('storage/' . str_replace('public/', '', $attachedDocs->$name)) }}" target="_blank">
                                {{ basename($attachedDocs->$name) }}
                            </a>
                            <br>
                            <a href="{{ asset('storage/' . str_replace('public/', '', $attachedDocs->$name)) }}" download class="btn btn-green">
                                Download
                            </a>
                            {{-- <button type="button" class="btn btn-danger delete-file-btn" data-field="{{ $name }}" data-url="{{ route('projects.ilp.attachments.delete', ['projectId' => $attachedDocs->project_id, 'field' => $name]) }}">
                                Delete
                            </button> --}}
                        @endif
                    </div>
                @endforeach
            </div>

            <!-- RIGHT COLUMN -->
            <div class="col-md-6">
                @foreach ([
                    'purchase_quotation_doc' => 'Purchase Quotation',
                    'other_doc' => 'Other Document',
                ] as $name => $label)
                    <div class="mb-3">
                        <label class="form-label">{{ $label }}:</label>
                        <input type="file" name="attachments[{{ $name }}]" class="form-control" onchange="renameFile(this, '{{ $name }}')">

                        @if(!empty($attachedDocs->$name))
                            <p>Currently Attached:</p>
                            <a href="{{ asset('storage/' . str_replace('public/', '', $attachedDocs->$name)) }}" target="_blank">
                                {{ basename($attachedDocs->$name) }}
                            </a>
                            <br>
                            <a href="{{ asset('storage/' . str_replace('public/', '', $attachedDocs->$name)) }}" download class="btn btn-green">
                                Download
                            </a>
                            {{-- <button type="button" class="btn btn-danger delete-file-btn" data-field="{{ $name }}" data-url="{{ route('projects.ilp.attachments.delete', ['projectId' => $attachedDocs->project_id, 'field' => $name]) }}">
                                Delete
                            </button> --}}
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

.btn-danger {
    background-color: #dc3545;
    color: white;
    border: none;
    cursor: pointer;
    margin-left: 5px;
}

.btn-danger:hover {
    background-color: #c82333;
}

/* Spacing between elements */
.mb-3 {
    margin-bottom: 20px;
}

.mb-3 p {
    margin-bottom: 5px;
}

/* File name and link alignment */
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
            return; // No file selected
        }

        const extension = file.name.split('.').pop();
        const newFileName = `${projectId}_${label}.${extension}`;

        const dataTransfer = new DataTransfer();
        const renamedFile = new File([file], newFileName, { type: file.type });
        dataTransfer.items.add(renamedFile);
        input.files = dataTransfer.files;
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.delete-file-btn').forEach(button => {
            button.addEventListener('click', function () {
                let field = this.dataset.field;
                let deleteUrl = this.dataset.url;

                if (confirm(`Are you sure you want to delete the ${field.replace('_', ' ')}?`)) {
                    fetch(deleteUrl, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Content-Type': 'application/json',
                        },
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert(`${field.replace('_', ' ')} deleted successfully.`);
                            location.reload();
                        } else {
                            alert(`Failed to delete ${field.replace('_', ' ')}.`);
                        }
                    })
                    .catch(error => console.error('Error:', error));
                }
            });
        });
    });
</script>
