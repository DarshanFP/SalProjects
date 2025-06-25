<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Details PDF</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #000;
            margin: 0;
            padding: 0;
            background: #fff;
        }

        .container {
            width: 100%;
            padding: 20px;
            box-sizing: border-box;
        }

        h1, h2, h3, h4, h5, h6 {
            text-align: center;
            margin-bottom: 20px;
        }

        .card {
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-bottom: 20px;
            page-break-inside: avoid;
        }

        .card-header {
            background-color: #f5f5f5;
            padding: 10px;
            border-bottom: 1px solid #ddd;
            font-size: 1.2em;
            font-weight: bold;
        }

        .card-body {
            padding: 10px;
        }

        .table, .signature-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            table-layout: fixed;
        }

        .table th, .table td, .signature-table th, .signature-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
            vertical-align: top;
            word-wrap: break-word;
        }

        .signature-table td {
            padding: 8px;
            vertical-align: top;
            text-align: left;
            width: 50%;
        }

        .signature-table tr td:nth-child(2) {
            width: 60%;
        }

        .row {
            display: flex;
            justify-content: space-between;
        }

        .column {
            flex: 0 0 48%;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            grid-gap: 20px;
        }

        .info-label {
            font-weight: bold;
            margin-right: 10px;
        }

        .info-value {
            word-wrap: break-word;
            padding-left: 10px;
        }

        @page {
            margin: 20mm;
            margin-top: 1.6in;
            margin-bottom: 1.6in;
            margin-left: 0.6in;
            margin-right: 0.6in;
            header: page-header;
            footer: page-footer;
        }

        .page-header {
            text-align: center;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
            margin-bottom: 10px;
            font-size: 0.8em;
            width: 100%;
        }

        .page-number:before {
            content: counter(page);
        }

        .objective-card {
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 20px;
            page-break-inside: avoid;
        }

        .result-section {
            border: 1px solid #ddd;
            padding: 10px;
            margin-bottom: 10px;
        }

        .custom-checkbox {
            width: 16px;
            height: 16px;
        }
    </style>
</head>
<body>

    <div class="page-header">
        Project ID: {{ $project->project_id }}  - Downloaded by: {{ auth()->user()->name }}
    </div>

    <div class="container">
        <h1>Project Details</h1>

        <!-- General Information Section -->
        <div class="card">
            <div class="card-header">
                General Information
            </div>
            <div class="card-body">
                @include('projects.partials.show.general_info')
            </div>
        </div>

        <!-- Key Information Section -->
        <div class="card">
            <div class="card-header">
                Key Information
            </div>
            <div class="card-body">
                @include('projects.partials.show.key_information')
            </div>
        </div>

        <!-- RST Beneficiaries Area for Development Projects -->
        @if ($project->project_type === 'Development Projects')
            <div class="card">
                <div class="card-header">
                    Beneficiaries Area
                </div>
                <div class="card-body">
                    @include('projects.partials.show.RST.beneficiaries_area')
                </div>
            </div>
        @endif

        <!-- CCI Specific Partials -->
        @if ($project->project_type === 'CHILD CARE INSTITUTION')
            <div class="card">
                <div class="card-header">
                    Rationale
                </div>
                <div class="card-body">
                    @include('projects.partials.show.CCI.rationale')
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    Statistics
                </div>
                <div class="card-body">
                    @include('projects.partials.show.CCI.statistics')
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    Annexed Target Group
                </div>
                <div class="card-body">
                    @include('projects.partials.show.CCI.annexed_target_group')
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    Age Profile
                </div>
                <div class="card-body">
                    @include('projects.partials.show.CCI.age_profile')
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    Personal Situation
                </div>
                <div class="card-body">
                    @include('projects.partials.show.CCI.personal_situation')
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    Economic Background
                </div>
                <div class="card-body">
                    @include('projects.partials.show.CCI.economic_background')
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    Achievements
                </div>
                <div class="card-body">
                    @include('projects.partials.show.CCI.achievements')
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    Present Situation
                </div>
                <div class="card-body">
                    @include('projects.partials.show.CCI.present_situation')
                </div>
            </div>
        @endif

        <!-- Residential Skill Training Specific Partials -->
        @if ($project->project_type === 'Residential Skill Training Proposal 2')
            <div class="card">
                <div class="card-header">
                    Beneficiaries Area
                </div>
                <div class="card-body">
                    @include('projects.partials.show.RST.beneficiaries_area')
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    Institution Information
                </div>
                <div class="card-body">
                    @include('projects.partials.show.RST.institution_info')
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    Target Group
                </div>
                <div class="card-body">
                    @include('projects.partials.show.RST.target_group')
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    Target Group Annexure
                </div>
                <div class="card-body">
                    @include('projects.partials.show.RST.target_group_annexure')
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    Geographical Area
                </div>
                <div class="card-body">
                    @include('projects.partials.show.RST.geographical_area')
                </div>
            </div>
        @endif

        <!-- Edu-Rural-Urban-Tribal Specific Partials -->
        @if ($project->project_type === 'Rural-Urban-Tribal')
            <div class="card">
                <div class="card-header">
                    Basic Information
                </div>
                <div class="card-body">
                    @include('projects.partials.show.Edu-RUT.basic_info')
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    Target Group
                </div>
                <div class="card-body">
                    @include('projects.partials.show.Edu-RUT.target_group')
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    Annexed Target Group
                </div>
                <div class="card-body">
                    @include('projects.partials.show.Edu-RUT.annexed_target_group')
                </div>
            </div>
        @endif

        <!-- Individual - Ongoing Educational Support Partials -->
        @if ($project->project_type === 'Individual - Ongoing Educational support')
            <div class="card">
                <div class="card-header">
                    Personal Information
                </div>
                <div class="card-body">
                    @include('projects.partials.show.IES.personal_info')
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    Family Working Members
                </div>
                <div class="card-body">
                    @include('projects.partials.show.IES.family_working_members')
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    Immediate Family Details
                </div>
                <div class="card-body">
                    @include('projects.partials.show.IES.immediate_family_details')
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    Educational Background
                </div>
                <div class="card-body">
                    @include('projects.partials.show.IES.educational_background')
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    Estimated Expenses
                </div>
                <div class="card-body">
                    @include('projects.partials.show.IES.estimated_expenses')
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    Attachments
                </div>
                <div class="card-body">
                    @include('projects.partials.show.IES.attachments')
                </div>
            </div>
        @endif

        <!-- Individual - Initial Educational Support Partials -->
        @if ($project->project_type === 'Individual - Initial - Educational support')
            <div class="card">
                <div class="card-header">
                    Personal Information
                </div>
                <div class="card-body">
                    @include('projects.partials.show.IIES.personal_info')
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    Family Working Members
                </div>
                <div class="card-body">
                    @include('projects.partials.show.IIES.family_working_members')
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    Immediate Family Details
                </div>
                <div class="card-body">
                    @include('projects.partials.show.IIES.immediate_family_details')
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    Scope of Financial Support
                </div>
                <div class="card-body">
                    @include('projects.partials.show.IIES.scope_financial_support')
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    Estimated Expenses
                </div>
                <div class="card-body">
                    @include('projects.partials.show.IIES.estimated_expenses')
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    Attachments
                </div>
                <div class="card-body">
                    @include('projects.partials.show.IIES.attachments')
                </div>
            </div>
        @endif

        <!-- Individual - Livelihood Application Partials -->
        @if ($project->project_type === 'Individual - Livelihood Application')
            <div class="card">
                <div class="card-header">
                    Personal Information
                </div>
                <div class="card-body">
                    @include('projects.partials.show.ILP.personal_info')
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    Revenue Goals
                </div>
                <div class="card-body">
                    @include('projects.partials.show.ILP.revenue_goals')
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    Strength and Weakness Analysis
                </div>
                <div class="card-body">
                    @include('projects.partials.show.ILP.strength_weakness')
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    Risk Analysis
                </div>
                <div class="card-body">
                    @include('projects.partials.show.ILP.risk_analysis')
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    Attached Documents
                </div>
                <div class="card-body">
                    @include('projects.partials.show.ILP.attached_docs')
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    Budget
                </div>
                <div class="card-body">
                    @include('projects.partials.show.ILP.budget')
                </div>
            </div>
        @endif

        <!-- Individual - Access to Health Specific Partials -->
        @if ($project->project_type === 'Individual - Access to Health')
            <div class="card">
                <div class="card-header">
                    Personal Information
                </div>
                <div class="card-body">
                    @include('projects.partials.show.IAH.personal_info')
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    Health Conditions
                </div>
                <div class="card-body">
                    @include('projects.partials.show.IAH.health_conditions')
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    Earning Members
                </div>
                <div class="card-body">
                    @include('projects.partials.show.IAH.earning_members')
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    Support Details
                </div>
                <div class="card-body">
                    @include('projects.partials.show.IAH.support_details')
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    Budget Details
                </div>
                <div class="card-body">
                    @include('projects.partials.show.IAH.budget_details')
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    Documents
                </div>
                <div class="card-body">
                    @include('projects.partials.show.IAH.documents')
                </div>
            </div>
        @endif

        <!-- Institutional Ongoing Group Educational Specific Partials -->
        @if ($project->project_type === 'Institutional Ongoing Group Educational proposal')
            <div class="card">
                <div class="card-header">
                    Institution Information
                </div>
                <div class="card-body">
                    @include('projects.partials.show.IGE.institution_info')
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    Beneficiaries Supported
                </div>
                <div class="card-body">
                    @include('projects.partials.show.IGE.beneficiaries_supported')
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    Ongoing Beneficiaries
                </div>
                <div class="card-body">
                    @include('projects.partials.show.IGE.ongoing_beneficiaries')
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    New Beneficiaries
                </div>
                <div class="card-body">
                    @include('projects.partials.show.IGE.new_beneficiaries')
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    Budget
                </div>
                <div class="card-body">
                    @include('projects.partials.show.IGE.budget')
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    Development Monitoring
                </div>
                <div class="card-body">
                    @include('projects.partials.show.IGE.development_monitoring')
                </div>
            </div>
        @endif

        <!-- Livelihood Development Project Specific Partials -->
        @if ($project->project_type === 'Livelihood Development Projects')
            <div class="card">
                <div class="card-header">
                    Need Analysis
                </div>
                <div class="card-body">
                    @include('projects.partials.show.LDP.need_analysis')
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    Intervention Logic
                </div>
                <div class="card-body">
                    @include('projects.partials.show.LDP.intervention_logic')
                </div>
            </div>
        @endif

        <!-- CIC Specific Partial -->
        @if ($project->project_type === 'PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER')
            <div class="card">
                <div class="card-header">
                    Basic Information
                </div>
                <div class="card-body">
                    @include('projects.partials.show.CIC.basic_info')
                </div>
            </div>
        @endif

        <!-- Default Partial Sections (These should be included for all project types except certain individual types) -->
        @if (!in_array($project->project_type, ['Individual - Ongoing Educational support', 'Individual - Livelihood Application', 'Individual - Access to Health', 'Individual - Initial - Educational support']))
            <div class="card">
                <div class="card-header">
                    Logical Framework
                </div>
                <div class="card-body">
                    @include('projects.partials.show.logical_framework')
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    Project Sustainability, Monitoring and Methodologies
                </div>
                <div class="card-body">
                    @include('projects.partials.show.sustainability')
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    Budget
                </div>
                <div class="card-body">
                    @include('projects.partials.show.budget')
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    Attachments
                </div>
                <div class="card-body">
                    @include('projects.partials.show.attachments')
                </div>
            </div>
        @endif
    </div>

    <!-- Signature and Approval Sections with page break control -->
    <div class="container" style="page-break-before: always;">
        <h2>Signatures</h2>
        <table class="signature-table">
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

        <h3>APPROVAL - To be filled by the Project Coordinator:</h3>
        <table class="signature-table">
            <tbody>
                <tr>
                    <td>Amount approved</td>
                    <td></td>
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

</body>
</html>
