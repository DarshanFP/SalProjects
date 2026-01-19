<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Details PDF</title>
    <style>
        /* Force black text color for all elements in PDF */
        body, div, p, h1, h2, h3, h4, h5, h6, span, td, th, label, input, select, button, a, li, ul, ol {
            color: black !important;
        }
        /* Structural, print-friendly styles for PDF, matching web view structure but no dark theme */
        textarea {
            color: black !important;
            background: #fff !important;
        }
        
        /* Preserve line breaks for all textarea content in PDF */
        .info-value,
        .card-body .info-value,
        .info-grid .info-value,
        .form-control:not(input):not(select):not(textarea),
        textarea,
        div.form-control {
            white-space: pre-wrap !important;
            word-wrap: break-word !important;
            overflow-wrap: break-word !important;
            line-height: 1.6 !important;
        }
        
        /* Preserve line breaks in table cells displaying textarea content */
        .table td,
        .table-bordered td {
            white-space: pre-wrap !important;
            word-wrap: break-word !important;
            overflow-wrap: break-word !important;
        }
        .container {
            width: 100%;
            margin: 0 auto;
            padding: 0 16px;
        }
        .mb-3 { margin-bottom: 1rem !important; }
        .mb-4 { margin-bottom: 1.5rem !important; }
        .card {
            border: 1px solid #ccc;
            border-radius: 6px;
            background: #fff;
            box-shadow: none;
            margin-bottom: 1.5rem;
        }
        .card-header {
            background: #f8f9fa;
            border-bottom: 1px solid #ccc;
            padding: 0.75rem 1.25rem;
            font-weight: bold;
            font-size: 1.1rem;
        }
        .card-body {
            padding: 1.25rem;
        }
        .form-label {
            font-weight: 600;
            margin-bottom: 0.5rem;
            display: block;
        }
        .form-control {
            width: 100%;
            padding: 0.5rem 0.75rem;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 1rem;
            background: #fff;
            color: #222;
            margin-bottom: 0.5rem;
        }
        .btn {
            display: inline-block;
            font-weight: 400;
            text-align: center;
            vertical-align: middle;
            user-select: none;
            border: 1px solid transparent;
            padding: 0.375rem 0.75rem;
            font-size: 1rem;
            border-radius: 4px;
            text-decoration: none;
            margin-right: 0.5rem;
        }
        .btn-primary {
            color: #fff;
            background-color: #007bff;
            border-color: #007bff;
        }
        .btn-danger {
            color: #fff;
            background-color: #dc3545;
            border-color: #dc3545;
        }
        .btn-secondary {
            color: #fff;
            background-color: #6c757d;
            border-color: #6c757d;
        }
        .table, .table-bordered, .general-info-table, #general-info-table,
        .table th, .table td, .table-bordered th, .table-bordered td,
        .general-info-table th, .general-info-table td, #general-info-table th, #general-info-table td {
            border: 1px solid black !important;
            border-collapse: collapse;
            padding: 8px 12px;
            text-align: left;
            vertical-align: top;
            font-size: 1rem;
        }
        .label {
            font-weight: bold;
            width: 35%;
        }
        .value {
            width: 100%;
        }

        /* Ensure tick marks render in PDF */
        body, .checkmark-icon {
            font-family: DejaVu Sans, Arial, Helvetica, sans-serif !important;
        }
        .checkmark-icon {
            font-size: 22px !important;
            font-weight: 900 !important;
            color: #000 !important;
            line-height: 1;
        }

        /* Override web dark theme cards for PDF: keep clean white cells */
        /* Force white background for budget summary cards in PDF using high specificity */
        .pdf-document .budget-summary-grid { 
            gap: 8px; 
        }
        .pdf-document .budget-summary-card,
        .pdf-document div.budget-summary-card,
        .pdf-document .card-body .budget-summary-card,
        .pdf-document .mb-3 .budget-summary-card,
        body.pdf-document .budget-summary-card {
            background-color: #ffffff !important;
            background: #ffffff !important;
            color: #000000 !important;
            border: 1px solid #000000 !important;
            border-radius: 4px;
            padding: 10px 12px;
        }
        .pdf-document .budget-summary-label,
        .pdf-document .budget-summary-label span,
        .pdf-document div.budget-summary-label,
        .pdf-document .budget-summary-value,
        .pdf-document div.budget-summary-value,
        body.pdf-document .budget-summary-label,
        body.pdf-document .budget-summary-value {
            color: #000000 !important;
        }
        /* Override any inline styles from budget partial */
        .pdf-document .card-body .budget-summary-card * {
            color: #000000 !important;
        }

        /* PDF-specific signature table styles (from history, with unique class names) */
        .pdf-signature-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            table-layout: fixed;
        }
        .pdf-signature-table th, .pdf-signature-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
            vertical-align: top;
            word-wrap: break-word;
        }
        .pdf-main-signature-table {
            table-layout: fixed;
            height: auto;
        }
        .pdf-main-signature-table td {
            padding: 0 8px;
            vertical-align: middle;
            text-align: left;
            width: 50%;
            min-height: 180px;
            height: 60px;
        }
        .pdf-main-signature-table td:first-child {
            height: 180px;
        }
        .pdf-main-signature-table td:first-child br {
            margin-bottom: 20px;
        }
        .pdf-main-signature-table tbody tr:nth-child(1) td,
        .pdf-main-signature-table tbody tr:nth-child(2) td,
        .pdf-main-signature-table tbody tr:nth-child(4) td {
            padding: 0 2px;
        }
        .pdf-main-signature-table tbody tr:nth-child(3) td {
            padding: 72px 8px;
        }
    </style>
</head>
<body class="pdf-document">

    <div class="page-header">
        Project ID: {{ $project->project_id }}
    </div>
    <div class="card-header">
        <h4>{{ $project->project_title }}</h4>
    </div>

    <div class="container">
        <!-- General Information Section -->
        <h1 class="mb-4">Project Details</h1>
        @include('projects.partials.Show.general_info')

        <!-- Key Information Section -->
        <div id="key-info-section" class="mb-3 card">
            <div class="card-header">
                <h4>Key Information</h4>
            </div>
            <div class="card-body">
                @include('projects.partials.Show.key_information')
            </div>
        </div>

        <!-- RST Beneficiaries Area for Development Projects -->
        @if ($project->project_type === 'Development Projects')
            <div class="mb-3 card">
                <div class="card-header">
                    <h4>Beneficiaries Area</h4>
                </div>
                <div class="card-body">
                    @include('projects.partials.Show.RST.beneficiaries_area')
                </div>
            </div>
        @endif

        <!-- CCI Specific Partials -->
        @if ($project->project_type === 'CHILD CARE INSTITUTION')
            <div class="mb-3 card">
                <div class="card-header">
                    <h4>Rationale</h4>
                </div>
                <div class="card-body">
                    @include('projects.partials.Show.CCI.rationale')
                </div>
            </div>

            <div class="mb-3 card">
                <div class="card-header">
                    <h4>Statistics</h4>
                </div>
                <div class="card-body">
                    @include('projects.partials.Show.CCI.statistics')
                </div>
            </div>

            <div class="mb-3 card">
                <div class="card-header">
                    <h4>Annexed Target Group</h4>
                </div>
                <div class="card-body">
                    @include('projects.partials.Show.CCI.annexed_target_group')
                </div>
            </div>

            <div class="mb-3 card">
                <div class="card-header">
                    <h4>Age Profile</h4>
                </div>
                <div class="card-body">
                    @include('projects.partials.Show.CCI.age_profile')
                </div>
            </div>

            <div class="mb-3 card">
                <div class="card-header">
                    <h4>Personal Situation</h4>
                </div>
                <div class="card-body">
                    @include('projects.partials.Show.CCI.personal_situation')
                </div>
            </div>

            <div class="mb-3 card">
                <div class="card-header">
                    <h4>Economic Background</h4>
                </div>
                <div class="card-body">
                    @include('projects.partials.Show.CCI.economic_background')
                </div>
            </div>

            <div class="mb-3 card">
                <div class="card-header">
                    <h4>Achievements</h4>
                </div>
                <div class="card-body">
                    @include('projects.partials.Show.CCI.achievements')
                </div>
            </div>

            <div class="mb-3 card">
                <div class="card-header">
                    <h4>Present Situation</h4>
                </div>
                <div class="card-body">
                    @include('projects.partials.Show.CCI.present_situation')
                </div>
            </div>
        @endif

        <!-- Residential Skill Training Specific Partials -->
        @if ($project->project_type === 'Residential Skill Training Proposal 2')
            <div class="mb-3 card">
                <div class="card-header">
                    <h4>Beneficiaries Area</h4>
                </div>
                <div class="card-body">
                    @include('projects.partials.Show.RST.beneficiaries_area')
                </div>
            </div>

            <div class="mb-3 card">
                <div class="card-header">
                    <h4>Institution Information</h4>
                </div>
                <div class="card-body">
                    @include('projects.partials.Show.RST.institution_info')
                </div>
            </div>

            <div class="mb-3 card">
                <div class="card-header">
                    <h4>Target Group</h4>
                </div>
                <div class="card-body">
                    @include('projects.partials.Show.RST.target_group')
                </div>
            </div>

            <div class="mb-3 card">
                <div class="card-header">
                    <h4>Target Group Annexure</h4>
                </div>
                <div class="card-body">
                    @include('projects.partials.Show.RST.target_group_annexure')
                </div>
            </div>

            <div class="mb-3 card">
                <div class="card-header">
                    <h4>Geographical Area</h4>
                </div>
                <div class="card-body">
                    @include('projects.partials.Show.RST.geographical_area')
                </div>
            </div>
        @endif

        <!-- Edu-Rural-Urban-Tribal Specific Partials -->
        @if ($project->project_type === 'Rural-Urban-Tribal')
            <div class="mb-3 card">
                <div class="card-header">
                    <h4>Basic Information</h4>
                </div>
                <div class="card-body">
                    @include('projects.partials.Show.Edu-RUT.basic_info')
                </div>
            </div>

            <div class="mb-3 card">
                <div class="card-header">
                    <h4>Target Group</h4>
                </div>
                <div class="card-body">
                    @include('projects.partials.Show.Edu-RUT.target_group')
                </div>
            </div>

            <div class="mb-3 card">
                <div class="card-header">
                    <h4>Annexed Target Group</h4>
                </div>
                <div class="card-body">
                    @include('projects.partials.Show.Edu-RUT.annexed_target_group')
                </div>
            </div>
        @endif

        <!-- Individual - Ongoing Educational Support Partials -->
        @if ($project->project_type === 'Individual - Ongoing Educational support')
            <div class="mb-3 card">
                <div class="card-header">
                    <h4>Personal Information</h4>
                </div>
                <div class="card-body">
                    @include('projects.partials.Show.IES.personal_info')
                </div>
            </div>

            <div class="mb-3 card">
                <div class="card-header">
                    <h4>Family Working Members</h4>
                </div>
                <div class="card-body">
                    @include('projects.partials.Show.IES.family_working_members')
                </div>
            </div>

            <div class="mb-3 card">
                <div class="card-header">
                    <h4>Immediate Family Details</h4>
                </div>
                <div class="card-body">
                    @include('projects.partials.Show.IES.immediate_family_details')
                </div>
            </div>

            <div class="mb-3 card">
                <div class="card-header">
                    <h4>Educational Background</h4>
                </div>
                <div class="card-body">
                    @include('projects.partials.Show.IES.educational_background')
                </div>
            </div>

            <div class="mb-3 card">
                <div class="card-header">
                    <h4>Estimated Expenses</h4>
                </div>
                <div class="card-body">
                    @include('projects.partials.Show.IES.estimated_expenses')
                </div>
            </div>

            <div class="mb-3 card">
                <div class="card-header">
                    <h4>Attachments</h4>
                </div>
                <div class="card-body">
                    @include('projects.partials.Show.IES.attachments')
                </div>
            </div>
        @endif

        <!-- Individual - Initial Educational Support Partials -->
        @if ($project->project_type === 'Individual - Initial - Educational support')
            <div class="mb-3 card">
                <div class="card-header">
                    <h4>Personal Information</h4>
                </div>
                <div class="card-body">
                    @include('projects.partials.Show.IIES.personal_info')
                </div>
            </div>

            <div class="mb-3 card">
                <div class="card-header">
                    <h4>Family Working Members</h4>
                </div>
                <div class="card-body">
                    @include('projects.partials.Show.IIES.family_working_members')
                </div>
            </div>

            <div class="mb-3 card">
                <div class="card-header">
                    <h4>Immediate Family Details</h4>
                </div>
                <div class="card-body">
                    @include('projects.partials.Show.IIES.immediate_family_details')
                </div>
            </div>

            <div class="mb-3 card">
                <div class="card-header">
                    <h4>Scope of Financial Support</h4>
                </div>
                <div class="card-body">
                    @include('projects.partials.Show.IIES.scope_financial_support')
                </div>
            </div>

            <div class="mb-3 card">
                <div class="card-header">
                    <h4>Estimated Expenses</h4>
                </div>
                <div class="card-body">
                    @include('projects.partials.Show.IIES.estimated_expenses')
                </div>
            </div>

            <div class="mb-3 card">
                <div class="card-header">
                    <h4>Attachments</h4>
                </div>
                <div class="card-body">
                    @include('projects.partials.Show.IIES.attachments')
                </div>
            </div>
        @endif

        <!-- Individual - Livelihood Application Partials -->
        @if ($project->project_type === 'Individual - Livelihood Application')
            <div class="mb-3 card">
                <div class="card-header">
                    <h4>Personal Information</h4>
                </div>
                <div class="card-body">
                    @include('projects.partials.Show.ILP.personal_info')
                </div>
            </div>

            <div class="mb-3 card">
                <div class="card-header">
                    <h4>Revenue Goals</h4>
                </div>
                <div class="card-body">
                    @include('projects.partials.Show.ILP.revenue_goals')
                </div>
            </div>

            <div class="mb-3 card">
                <div class="card-header">
                    <h4>Strength and Weakness Analysis</h4>
                </div>
                <div class="card-body">
                    @include('projects.partials.Show.ILP.strength_weakness')
                </div>
            </div>

            <div class="mb-3 card">
                <div class="card-header">
                    <h4>Risk Analysis</h4>
                </div>
                <div class="card-body">
                    @include('projects.partials.Show.ILP.risk_analysis')
                </div>
            </div>

            <div class="mb-3 card">
                <div class="card-header">
                    <h4>Attached Documents</h4>
                </div>
                <div class="card-body">
                    @include('projects.partials.Show.ILP.attached_docs')
                </div>
            </div>

            <div class="mb-3 card">
                <div class="card-header">
                    <h4>Budget</h4>
                </div>
                <div class="card-body">
                    @include('projects.partials.Show.ILP.budget')
                </div>
            </div>
        @endif

        <!-- Individual - Access to Health Specific Partials -->
        @if ($project->project_type === 'Individual - Access to Health')
            <div class="mb-3 card">
                <div class="card-header">
                    <h4>Personal Information</h4>
                </div>
                <div class="card-body">
                    @include('projects.partials.Show.IAH.personal_info')
                </div>
            </div>

            <div class="mb-3 card">
                <div class="card-header">
                    <h4>Health Conditions</h4>
                </div>
                <div class="card-body">
                    @include('projects.partials.Show.IAH.health_conditions')
                </div>
            </div>

            <div class="mb-3 card">
                <div class="card-header">
                    <h4>Earning Members</h4>
                </div>
                <div class="card-body">
                    @include('projects.partials.Show.IAH.earning_members')
                </div>
            </div>

            <div class="mb-3 card">
                <div class="card-header">
                    <h4>Support Details</h4>
                </div>
                <div class="card-body">
                    @include('projects.partials.Show.IAH.support_details')
                </div>
            </div>

            <div class="mb-3 card">
                <div class="card-header">
                    <h4>Budget Details</h4>
                </div>
                <div class="card-body">
                    @include('projects.partials.Show.IAH.budget_details')
                </div>
            </div>

            <div class="mb-3 card">
                <div class="card-header">
                    <h4>Documents</h4>
                </div>
                <div class="card-body">
                    @include('projects.partials.Show.IAH.documents')
                </div>
            </div>
        @endif

        <!-- Institutional Ongoing Group Educational Specific Partials -->
        @if ($project->project_type === 'Institutional Ongoing Group Educational proposal')
            <div class="mb-3 card">
                <div class="card-header">
                    <h4>Institution Information</h4>
                </div>
                <div class="card-body">
                    @include('projects.partials.Show.IGE.institution_info')
                </div>
            </div>

            <div class="mb-3 card">
                <div class="card-header">
                    <h4>Beneficiaries Supported</h4>
                </div>
                <div class="card-body">
                    @include('projects.partials.Show.IGE.beneficiaries_supported')
                </div>
            </div>

            <div class="mb-3 card">
                <div class="card-header">
                    <h4>Ongoing Beneficiaries</h4>
                </div>
                <div class="card-body">
                    @include('projects.partials.Show.IGE.ongoing_beneficiaries')
                </div>
            </div>

            <div class="mb-3 card">
                <div class="card-header">
                    <h4>New Beneficiaries</h4>
                </div>
                <div class="card-body">
                    @include('projects.partials.Show.IGE.new_beneficiaries')
                </div>
            </div>

            <div class="mb-3 card">
                <div class="card-header">
                    <h4>Budget</h4>
                </div>
                <div class="card-body">
                    @include('projects.partials.Show.IGE.budget')
                </div>
            </div>

            <div class="mb-3 card">
                <div class="card-header">
                    <h4>Development Monitoring</h4>
                </div>
                <div class="card-body">
                    @include('projects.partials.Show.IGE.development_monitoring')
                </div>
            </div>
        @endif

        <!-- Livelihood Development Project Specific Partials -->
        @if ($project->project_type === 'Livelihood Development Projects')
            <div class="mb-3 card">
                <div class="card-header">
                    <h4>Need Analysis</h4>
                </div>
                <div class="card-body">
                    @include('projects.partials.Show.LDP.need_analysis')
                </div>
            </div>

            <div class="mb-3 card">
                <div class="card-header">
                    <h4>Intervention Logic</h4>
                </div>
                <div class="card-body">
                    @include('projects.partials.Show.LDP.intervention_logic')
                </div>
            </div>
        @endif

        <!-- CIC Specific Partial -->
        @if ($project->project_type === 'PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER')
            <div class="mb-3 card">
                <div class="card-header">
                    <h4>Basic Information</h4>
                </div>
                <div class="card-body">
                    @include('projects.partials.Show.CIC.basic_info')
                </div>
            </div>
        @endif

        <!-- Default Partial Sections (These should be included for all project types except certain individual types) -->
        @if (!in_array($project->project_type, ['Individual - Ongoing Educational support', 'Individual - Livelihood Application', 'Individual - Access to Health', 'Individual - Initial - Educational support']))
            <div class="mb-3 card">
                <div class="card-header">
                    <h4>Logical Framework</h4>
                </div>
                <div class="card-body">
                    @include('projects.partials.Show.logical_framework')
                </div>
            </div>

            <div class="mb-3 card">
                <div class="card-header">
                    <h4>Project Sustainability, Monitoring and Methodologies</h4>
                </div>
                <div class="card-body">
                    @include('projects.partials.Show.sustainability')
                </div>
            </div>

            <div class="mb-3 card">
                <div class="card-header">
                    <h4>Budget</h4>
                </div>
                <div class="card-body">
                    @include('projects.partials.Show.budget')
                </div>
            </div>

            <div class="mb-3 card">
                <div class="card-header">
                    <h4>Attachments</h4>
                </div>
                <div class="card-body">
                    @include('projects.partials.Show.attachments')
                </div>
            </div>
        @endif
    </div>

    <!-- Signature and Approval Sections with page break control -->
    <div class="container" style="page-break-before: always;">
        <h2 class="mb-4">Signatures</h2>
        <table class="pdf-signature-table pdf-main-signature-table">
            <thead>
                <tr>
                    <th>Person</th>
                    <th>Signature</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Project Executor<br>{{ $projectRoles['executor'] ?? 'N/A' }}</td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <td>Project Incharge<br>{{ $projectRoles['incharge'] ?? 'N/A' }}</td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <td>President of the Society / Chair Person of the Trust<br>{{ $projectRoles['president'] ?? 'N/A' }}</td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <td>Project Sanctioned / Authorised by<br>{{ $projectRoles['authorizedBy'] }}</td>
                    <td></td>
                    <td></td>
                </tr>
            </tbody>
        </table>

        <h3 class="mb-4">APPROVAL - To be filled by the Project Coordinator:</h3>
        <table class="pdf-signature-table">
            <tbody>
                <tr>
                    <td>Amount approved (Sanctioned)</td>
                    <td>{{ format_indian_currency($project->amount_sanctioned ?? max(0, ($project->overall_project_budget ?? 0) - (($project->amount_forwarded ?? 0) + ($project->local_contribution ?? 0))), 2) }}</td>
                </tr>
                <tr>
                    <td>Contributions considered</td>
                    <td>Forwarded: {{ format_indian_currency($project->amount_forwarded ?? 0, 2) }} | Local: {{ format_indian_currency($project->local_contribution ?? 0, 2) }}</td>
                </tr>
                <tr>
                    <td>Remarks if any</td>
                    <td></td>
                </tr>
                <tr>
                    <td>Project Coordinator</td>
                    <td>{{ $projectRoles['coordinator'] ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td>Signature</td>
                    <td></td>
                </tr>
                <tr>
                    <td>Date</td>
                    <td></td>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="page-header">
        Downloaded by: {{ auth()->user()->name }}
    </div>

    <!-- Final override for budget summary cards - must come after all partials -->
    <style>
        /* Final override: Force white background for budget cards in PDF with maximum specificity */
        body.pdf-document .budget-summary-card,
        .pdf-document .budget-summary-card {
            background-color: #ffffff !important;
            background: #ffffff !important;
            color: #000000 !important;
            border: 1px solid #000000 !important;
        }
        body.pdf-document .budget-summary-card *,
        .pdf-document .budget-summary-card *,
        body.pdf-document .budget-summary-label,
        body.pdf-document .budget-summary-label span,
        body.pdf-document .budget-summary-value,
        .pdf-document .budget-summary-label,
        .pdf-document .budget-summary-value {
            color: #000000 !important;
        }
    </style>

</body>
</html>
