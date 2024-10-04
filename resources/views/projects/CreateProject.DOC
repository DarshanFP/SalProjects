
Certainly! Let's go through each partial of the Blade template and its associated JavaScript to understand their functionalities. This comprehensive explanation will help you grasp how each component works together in the form.

---

## Main Blade Template (`createProjects.blade.php`)

This is the main template that assembles the entire project application form. It extends the `executor.dashboard` layout and includes several partials that make up different sections of the form.

### Structure:

- **Form Setup**: The form uses the `POST` method to submit data to the `projects.store` route and includes `enctype="multipart/form-data"` to handle file uploads.

- **Sections Included**:
  - General Information
  - Key Information
  - Logical Framework
  - Project Sustainability, Monitoring, and Evaluation Framework
  - Budget Section
  - Attachments Section

- **Submit Button**: A button to submit the application.

- **Scripts and Styles**: Includes custom JavaScript and CSS styles.

---

## 1. General Information Partial (`partials/general_info.blade.php`)

### Functionality:

This section collects basic information about the project and the applicant.

#### Fields:

1. **Project Type**:
   - A dropdown (`select`) to choose the type of project.
   - Options are pre-defined with values like "Development Projects", "Livelihood Development Projects", etc.
   - The selected value is retained using `old('project_type')`.

2. **Project Title**:
   - A text input for the project's title.
   - Value retained with `old('project_title')`.

3. **Name of the Society / Trust**:
   - A readonly text input displaying the society's name from `$user->society_name`.

4. **President / Chair Person**:
   - A readonly text input displaying the president's name from `$user->parent->name`.

5. **Project Applicant**:
   - Readonly inputs for the applicant's name, mobile, and email, populated from `$user` data.

6. **Project In-Charge**:
   - A dropdown to select the project in-charge.
   - Options are dynamically populated from `$users` where the province matches the current user's province.
   - On selection, the in-charge's name, mobile, and email fields are updated via JavaScript.

7. **Full Address**:
   - A textarea for the full address, pre-filled with `$user->address`.

8. **Overall Project Period**:
   - A dropdown to select the overall duration of the project (1-4 years).
   - On selection, updates the "Current Phase" options via JavaScript.

9. **Current Phase**:
   - A dropdown whose options depend on the "Overall Project Period".
   - Populated via JavaScript to ensure phases do not exceed the project period.

10. **Commencement Month and Year**:
    - Dropdowns to select the starting month and year of the project.

11. **Overall Project Budget**:
    - A numeric input for the total budget of the project.

12. **Project Coordinators**:
    - Displays the details of the Project Coordinator in India and the Mission Coordinator in Luzern, Switzerland.
    - Fetches coordinators from `$users` where `role` is 'coordinator' and `province` matches 'Generalate' or 'Luzern'.

### JavaScript:

```javascript
document.addEventListener('DOMContentLoaded', function() {
    // Update the current phase options based on the selected overall project period
    document.getElementById('overall_project_period').addEventListener('change', function() {
        const projectPeriod = parseInt(this.value);
        const phaseSelect = document.getElementById('current_phase');

        // Clear previous options
        phaseSelect.innerHTML = '<option value="" disabled selected>Select Phase</option>';

        // Add new options based on the selected value
        for (let i = 1; i <= projectPeriod; i++) {
            const option = document.createElement('option');
            option.value = i;
            option.text = `Phase ${i}`;
            phaseSelect.appendChild(option);
        }
    });

    // Placeholder for future additional dynamic interactions
    // Example: You can add more event listeners here to handle other dynamic interactions
});
```

#### Explanation:

- **Dynamic Phase Options**:
  - When the "Overall Project Period" changes, the script updates the "Current Phase" dropdown.
  - It ensures the phases listed do not exceed the total project period.
  - This prevents users from selecting an invalid phase.

---

## 2. Key Information Partial (`partials/key_information.blade.php`)

### Functionality:

Collects the goal of the project.

#### Fields:

1. **Goal of the Project**:
   - A textarea where the user describes the project's goal.
   - Value retained with `old('goal')`.

---

## 3. Logical Framework Partial (`partials/logical_framework.blade.php`)

### Functionality:

This section allows users to define objectives, results, risks, activities, and time frames in a structured format.

#### Structure:

- **Objectives Container**:
  - Holds multiple objectives.
  - Each objective includes:
    - Objective description.
    - Results/Outcomes.
    - Risks.
    - Activities and Means of Verification.
    - Time Frame (included via another partial).

#### Components:

1. **Objective**:
   - A textarea for the objective description.

2. **Results/Outcomes**:
   - Users can add multiple results for each objective.
   - Each result has a description textarea.

3. **Risks**:
   - Users can add multiple risks for each objective.
   - Each risk has a description textarea.

4. **Activities and Means of Verification**:
   - A table where each row represents an activity and its means of verification.
   - Users can add or remove activities.
   - Columns include:
     - Activities (description).
     - Means of Verification.

5. **Time Frame**:
   - For each activity, users can specify in which months it will occur.
   - Included via `@include('projects.partials._timeframe', ['objectiveIndex' => 0])`.

6. **Objective Controls**:
   - Buttons to add or remove objectives.

### JavaScript:

```javascript
let objectiveCount = 1;

document.addEventListener('DOMContentLoaded', function() {
    objectiveCount = document.querySelectorAll('.objective-card').length;
});

function addObjective() {
    // Clone the objective template and reset values
    // Update the objective header and name attributes
    // Append the new objective to the container
}

function removeLastObjective() {
    // Remove the last objective if there are more than one
    // Update objective numbers
}

function addResult(button) {
    // Clone the result section
    // Update name attributes
}

function removeResult(button) {
    // Remove the result section if there are more than one
    // Update name attributes
}

function addRisk(button) {
    // Clone the risk section
    // Update name attributes
}

function removeRisk(button) {
    // Remove the risk section if there are more than one
    // Update name attributes
}

function addActivity(button) {
    // Add activity row to the activities table
    // Add corresponding time frame row
    // Update name attributes
}

function removeActivity(button) {
    // Remove activity row and corresponding time frame row
    // Update name attributes
}

function updateNameAttributes(objectiveCard, objectiveIndex) {
    // Updates the 'name' attributes of inputs and textareas based on the objective index
}

function updateObjectiveNumbers() {
    // Updates the numbering of objectives after addition or removal
}
```

#### Explanation:

- **Dynamic Objective Management**:
  - Allows users to add or remove objectives.
  - Each objective's components (results, risks, activities) are dynamically managed.
  - Name attributes are updated to maintain proper data structure when the form is submitted.

- **Adding/Removing Results and Risks**:
  - Users can add multiple results and risks for each objective.
  - Buttons trigger functions to clone or remove these sections.

- **Activities and Time Frames**:
  - Activities are linked with the time frame table.
  - When an activity is added, a corresponding row in the time frame is also added.
  - Activity descriptions in the time frame update automatically based on the activities entered.

- **Name Attributes Update**:
  - Critical to ensure form data is correctly structured.
  - The `updateNameAttributes` function updates the `name` attributes whenever objectives or their components are added or removed.

---

## 4. Sustainability Partial (`partials/sustainability.blade.php`)

### Functionality:

Collects detailed explanations about the project's sustainability, monitoring, reporting, and evaluation methodologies.

#### Fields:

1. **Sustainability**:
   - A textarea for explaining how the project will be sustainable in the long term.

2. **Monitoring Process**:
   - A textarea for describing how the project will be monitored.

3. **Reporting Methodology**:
   - A textarea for detailing the reporting methods.

4. **Evaluation Methodology**:
   - A textarea for explaining how the project will be evaluated.

### JavaScript:

- No specific JavaScript code is associated with this partial.

---

## 5. Budget Partial (`partials/budget.blade.php`)

### Functionality:

Allows users to input detailed budget information for each phase of the project.

#### Structure:

- **Phases Container**:
  - Users can add multiple phases.
  - Each phase includes:
    - Amount sanctioned in the phase.
    - Budget table with detailed breakdown.
    - Option to add budget rows.
    - Totals calculated automatically.

#### Components:

1. **Phase Card**:
   - **Amount Sanctioned**:
     - Automatically calculated based on budget entries.
   - **Budget Table**:
     - Columns:
       - Particular
       - Costs (Rate Quantity)
       - Rate Multiplier
       - Rate Duration
       - Rate Increase (next phase)
       - This Phase (calculated)
       - Next Phase (calculated)
       - Action (Remove)
     - Rows can be added or removed.
   - **Totals**:
     - Totals for each column calculated automatically.
   - **Add Row Button**:
     - Adds a new budget row to the table.

2. **Add Phase Button**:
   - Allows users to add a new phase.

3. **Total Amounts**:
   - **Total Amount Sanctioned**:
     - Sum of amounts sanctioned across all phases.
   - **Total Amount Forwarded**:
     - Total amount carried forward from previous phases.

### JavaScript:

```javascript
// Calculate the budget totals for a single budget row
function calculateBudgetRowTotals(element) {
    // Fetch values from the row
    // Calculate 'This Phase' and 'Next Phase' amounts
    // Update the totals
}

// Update all budget rows based on the selected project period
function updateAllBudgetRows() {
    // Iterate through phases and rows
    // Recalculate totals
}

// Calculate the total budget for a phase
function calculateBudgetTotals(phaseCard) {
    // Sum up columns for the phase
    // Update total fields
    // Update overall project budget
}

// Calculate the total amount sanctioned and update the overall project budget
function calculateTotalAmountSanctioned() {
    // Sum up amounts from all phases
    // Update total amount sanctioned and forwarded
}

// Add a new budget row to the phase card
function addBudgetRow(button) {
    // Add a new row to the budget table
    // Set up event listeners for inputs
    // Recalculate totals
}

// Remove a budget row from the phase card
function removeBudgetRow(button) {
    // Remove the row
    // Recalculate totals
}

// Add a new phase card
function addPhase() {
    // Create a new phase card
    // Append to phases container
    // Recalculate totals
}

// Remove a phase card
function removePhase(button) {
    // Remove the phase card
    // Recalculate totals
}
```

#### Explanation:

- **Dynamic Budget Management**:
  - Users can add or remove budget items and phases.
  - Totals are recalculated automatically whenever inputs change.

- **Automatic Calculations**:
  - **This Phase**:
    - Calculated as `rate_quantity * rate_multiplier * rate_duration`.
  - **Next Phase**:
    - Calculated similarly but includes `rate_increase`.
  - **Totals**:
    - Sum up values across all budget rows in a phase.

- **Event Listeners**:
  - Input fields have `oninput` events to trigger recalculations.
  - Ensures data stays up-to-date with user inputs.

- **Adding Phases**:
  - New phases can be added with their own budget tables.
  - Phases can also be removed if needed.

---

## 6. Attachments Partial (`partials/attachments.blade.php`)

### Functionality:

Allows users to upload a file attachment related to the project.

#### Fields:

1. **Attachment File**:
   - A file input that accepts PDF files.
   - Max file size is enforced via JavaScript.

2. **File Name**:
   - A text input for the name of the file.

3. **Brief Description**:
   - A textarea for describing the attachment.

### JavaScript:

```javascript
function checkFileSize(input) {
    const file = input.files[0];
    if (file.size > 10485760) { // 10 MB in bytes
        document.getElementById('file-size-warning').style.display = 'block';
        input.value = ''; // Reset the file input
    } else {
        document.getElementById('file-size-warning').style.display = 'none';
    }
}
```

#### Explanation:

- **File Size Validation**:
  - Checks if the selected file exceeds 10 MB.
  - If it does, displays a warning message and resets the file input.
  - Ensures that users do not upload files that are too large.

---

## 7. Scripts Partial (`partials/scripts.blade.php`)

### Functionality:

Contains additional JavaScript functions that handle various interactions in the form.

### JavaScript:

```javascript
function beforeSubmit() {
    const formData = new FormData(document.querySelector('form'));
    formData.forEach((value, key) => {
        console.log(`${key}: ${value}`);
    });
}

document.addEventListener('DOMContentLoaded', function() {
    // Update the mobile and email fields based on the selected project in-charge
    document.getElementById('in_charge').addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const name = selectedOption.getAttribute('data-name');
        const mobile = selectedOption.getAttribute('data-mobile');
        const email = selectedOption.getAttribute('data-email');

        document.getElementById('in_charge_name').value = name;
        document.getElementById('in_charge_mobile').value = mobile;
        document.getElementById('in_charge_email').value = email;
    });

    // Update the phase options based on the selected overall project period
    document.getElementById('overall_project_period').addEventListener('change', function() {
        const projectPeriod = parseInt(this.value);
        const phaseSelect = document.getElementById('current_phase');

        // Clear previous options
        phaseSelect.innerHTML = '<option value="" disabled selected>Select Phase</option>';

        // Add new options based on the selected value
        for (let i = 1; i <= projectPeriod; i++) {
            const option = document.createElement('option');
            option.value = i;
            option.text = `${i}${i === 1 ? 'st' : i === 2 ? 'nd' : i === 3 ? 'rd' : 'th'} Phase`;
            phaseSelect.appendChild(option);
        }

        // Update all budget rows based on the selected project period
        updateAllBudgetRows();
    });
});

// Additional functions for budget calculations and attachment management
// (See Budget Partial for detailed explanations)
```

#### Explanation:

- **Before Submit Function**:
  - Intended to perform actions before form submission.
  - Currently logs form data to the console.

- **Dynamic Field Updates**:
  - **Project In-Charge Details**:
    - When the in-charge is selected, updates the name, mobile, and email fields.
  - **Phase Options**:
    - Updates the "Current Phase" options when the "Overall Project Period" changes.
    - Ensures phase selection is valid.

- **Budget Updates**:
  - Calls `updateAllBudgetRows()` to recalculate budgets when the project period changes.

---

## CSS Styling

### Styles:

- **Read-Only Inputs**:
  - `.readonly-input`: Styles for inputs that are read-only.
- **Select Inputs**:
  - `.select-input`: Styles for select inputs.
- **Table Styles**:
  - Adjustments for table cell alignment and padding.

---

## Summary of Functionalities

- **Form Sections**:
  - The form is divided into logical sections for better user experience.
- **Dynamic Content**:
  - Many fields and options are dynamically generated or updated based on user input.
- **Data Consistency**:
  - JavaScript functions ensure that the data structure remains consistent, especially with dynamically added fields.
- **User Feedback**:
  - Validation and warnings (e.g., file size limit) provide immediate feedback to users.
- **Automatic Calculations**:
  - Budget calculations are automated to reduce user error and improve efficiency.
- **Data Retention**:
  - Uses `old()` helper to retain input values upon form validation errors.

---

## How the Parts Work Together

- **Blade Templates and Partials**:
  - Blade templates are used to structure the HTML and PHP logic.
  - Partials allow for modular code, making it easier to manage and maintain.

- **JavaScript Interaction**:
  - Enhances user experience by providing dynamic form elements.
  - Ensures that related fields are synchronized (e.g., updating phases, budget calculations).

- **Form Submission**:
  - When the form is submitted, all data, including dynamically added fields, are sent to the server.
  - Name attributes are carefully managed to ensure data is correctly grouped on the backend.

- **Server-Side Processing**:
  - The form action points to `projects.store`, where the server will process the input data.
  - Validation and storage of data occur on the server.

---

## Conclusion

Each partial in the Blade template serves a specific purpose, and the associated JavaScript enhances interactivity and data integrity. Understanding how these components interact helps in maintaining the application and extending its functionality in the future.

Feel free to ask if you need further clarification on any specific part!
