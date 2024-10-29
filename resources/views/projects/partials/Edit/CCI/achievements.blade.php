<div class="mb-3 card">
    <div class="card-header">
        <h4>Edit: Achievements of the Children In Various Fields (Last year)</h4>
    </div>
    <div class="card-body">
        <!-- Academic Achievements -->
        <div class="mb-3">
            <h5>Academic achievements</h5>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Academic achievements</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="academic-achievements-rows">
                        @if(!empty($achievements->academic_achievements))
                            @foreach($achievements->academic_achievements as $index => $achievement)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td><input type="text" name="academic_achievements[{{ $index }}]" value="{{ $achievement }}" class="form-control" style="background-color: #202ba3;"></td>
                                    <td><button type="button" class="btn btn-danger" onclick="removeAchievementRow(this, 'academic')">Remove</button></td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td>1</td>
                                <td><input type="text" name="academic_achievements[0]" class="form-control" style="background-color: #202ba3;"></td>
                                <td><button type="button" class="btn btn-danger" onclick="removeAchievementRow(this, 'academic')">Remove</button></td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
            <button type="button" class="mt-3 btn btn-primary" onclick="addAchievementRow('academic')">Add More Academic Achievement</button>
        </div>

        <!-- Sport Achievements -->
        <div class="mb-3">
            <h5>Sport achievements</h5>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Sport achievements</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="sport-achievements-rows">
                        @if(!empty($achievements->sport_achievements))
                            @foreach($achievements->sport_achievements as $index => $achievement)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td><input type="text" name="sport_achievements[{{ $index }}]" value="{{ $achievement }}" class="form-control" style="background-color: #202ba3;"></td>
                                    <td><button type="button" class="btn btn-danger" onclick="removeAchievementRow(this, 'sport')">Remove</button></td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td>1</td>
                                <td><input type="text" name="sport_achievements[0]" class="form-control" style="background-color: #202ba3;"></td>
                                <td><button type="button" class="btn btn-danger" onclick="removeAchievementRow(this, 'sport')">Remove</button></td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
            <button type="button" class="mt-3 btn btn-primary" onclick="addAchievementRow('sport')">Add More Sport Achievement</button>
        </div>

        <!-- Other Achievements -->
        <div class="mb-3">
            <h5>Other achievements</h5>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Other achievements</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="other-achievements-rows">
                        @if(!empty($achievements->other_achievements))
                            @foreach($achievements->other_achievements as $index => $achievement)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td><input type="text" name="other_achievements[{{ $index }}]" value="{{ $achievement }}" class="form-control" style="background-color: #202ba3;"></td>
                                    <td><button type="button" class="btn btn-danger" onclick="removeAchievementRow(this, 'other')">Remove</button></td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td>1</td>
                                <td><input type="text" name="other_achievements[0]" class="form-control" style="background-color: #202ba3;"></td>
                                <td><button type="button" class="btn btn-danger" onclick="removeAchievementRow(this, 'other')">Remove</button></td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
            <button type="button" class="mt-3 btn btn-primary" onclick="addAchievementRow('other')">Add More Other Achievement</button>
        </div>
    </div>
</div>

<script>
    (function() {
        function addAchievementRow(type) {
            const container = document.getElementById(type + '-achievements-rows');
            const rowCount = container.children.length;
            const newRow = `
                <tr>
                    <td>${rowCount + 1}</td>
                    <td><input type="text" name="${type}_achievements[${rowCount}]" class="form-control" style="background-color: #202ba3;"></td>
                    <td><button type="button" class="btn btn-danger" onclick="removeAchievementRow(this, '${type}')">Remove</button></td>
                </tr>
            `;
            container.insertAdjacentHTML('beforeend', newRow);
        }

        function removeAchievementRow(button, type) {
            const row = button.closest('tr');
            row.remove();
            updateAchievementRowNumbers(type);
        }

        function updateAchievementRowNumbers(type) {
            const rows = document.querySelectorAll(`#${type}-achievements-rows tr`);
            rows.forEach((row, index) => {
                row.children[0].textContent = index + 1;
                row.querySelector(`input[name^="${type}_achievements"]`).setAttribute('name', `${type}_achievements[${index}]`);
            });
        }

        window.addAchievementRow = addAchievementRow;
        window.removeAchievementRow = removeAchievementRow;
    })();
</script>
