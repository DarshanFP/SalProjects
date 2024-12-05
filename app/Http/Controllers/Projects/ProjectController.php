<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Projects\ProjectEduRUTBasicInfoController;
use Illuminate\Http\Request;
use App\Models\OldProjects\Project;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
// Aliases for CCI Controllers with prefix 'CCI'
use App\Http\Controllers\Projects\CCI\AchievementsController as CCIAchievementsController;
use App\Http\Controllers\Projects\CCI\AgeProfileController as CCIAgeProfileController;
use App\Http\Controllers\Projects\CCI\AnnexedTargetGroupController as CCIAnnexedTargetGroupController;
use App\Http\Controllers\Projects\CCI\EconomicBackgroundController as CCIEconomicBackgroundController;
use App\Http\Controllers\Projects\CCI\PersonalSituationController as CCIPersonalSituationController;
use App\Http\Controllers\Projects\CCI\PresentSituationController as CCIPresentSituationController;
use App\Http\Controllers\Projects\CCI\RationaleController as CCIRationaleController;
use App\Http\Controllers\Projects\CCI\StatisticsController as CCIStatisticsController;
// Aliases for IGE Controllers
use App\Http\Controllers\Projects\IGE\InstitutionInfoController as IGEInstitutionInfoController;
use App\Http\Controllers\Projects\IGE\IGEBeneficiariesSupportedController as IGEBeneficiariesSupportedController;
use App\Http\Controllers\Projects\IGE\NewBeneficiariesController as IGENewBeneficiariesController;
use App\Http\Controllers\Projects\IGE\OngoingBeneficiariesController as IGEOngoingBeneficiariesController;
use App\Http\Controllers\Projects\IGE\IGEBudgetController as IGEBudgetController;
use App\Http\Controllers\Projects\IGE\DevelopmentMonitoringController as IGEDevelopmentMonitoringController;
// LDP - Livelihood Development Project controllers
use App\Http\Controllers\Projects\LDP\InterventionLogicController as LDPInterventionLogicController;
use App\Http\Controllers\Projects\LDP\NeedAnalysisController as LDPNeedAnalysisController;
use App\Http\Controllers\Projects\LDP\TargetGroupController as LDPTargetGroupController;
// RST - Residential Skill Training controllers
// RST Controllers
use App\Http\Controllers\Projects\RST\BeneficiariesAreaController as RSTBeneficiariesAreaController;
use App\Http\Controllers\Projects\RST\GeographicalAreaController as RSTGeographicalAreaController;
use App\Http\Controllers\Projects\RST\InstitutionInfoController as RSTInstitutionInfoController;
use App\Http\Controllers\Projects\RST\TargetGroupAnnexureController as RSTTargetGroupAnnexureController;
use App\Http\Controllers\Projects\RST\TargetGroupController as RSTTargetGroupController;



class ProjectController extends Controller
{
    protected $logicalFrameworkController;
    protected $sustainabilityController;
    //Edu-RUT
    protected $eduRUTBasicInfoController;
    protected $eduRUTTargetGroupController;
    protected $eduRUTAnnexedTargetGroupController;
    // CIC
    protected $cicBasicInfoController;
    // CCI
    protected $cciAchievementsController;
    protected $cciAgeProfileController;
    protected $cciAnnexedTargetGroupController;
    protected $cciEconomicBackgroundController;
    protected $cciPersonalSituationController;
    protected $cciPresentSituationController;
    protected $cciRationaleController;
    protected $cciStatisticsController;
    // Declarations for IGE controllers
    protected $igeInstitutionInfoController;
    protected $igeBeneficiariesSupportedController;
    protected $igeNewBeneficiariesController;
    protected $igeOngoingBeneficiariesController;
    protected $igeBudgetController;
    protected $igeDevelopmentMonitoringController;
    // LDP controllers
    protected $ldpInterventionLogicController;
    protected $ldpNeedAnalysisController;
    protected $ldpTargetGroupController;
    // RST controllers
    // RST controllers
    protected $rstBeneficiariesAreaController;
    protected $rstGeographicalAreaController;
    protected $rstInstitutionInfoController;
    protected $rstTargetGroupAnnexureController;
    protected $rstTargetGroupController;




    public function __construct(
        LogicalFrameworkController $logicalFrameworkController,
        SustainabilityController $sustainabilityController,
        //Edu-RUT
        ProjectEduRUTBasicInfoController $eduRUTBasicInfoController,
        EduRUTTargetGroupController $eduRUTTargetGroupController,
        EduRUTAnnexedTargetGroupController $eduRUTAnnexedTargetGroupController,
        // CIC
        CICBasicInfoController $cicBasicInfoController,
        // CCI
        CCIAchievementsController $cciAchievementsController,
        CCIAgeProfileController $cciAgeProfileController,
        CCIAnnexedTargetGroupController $cciAnnexedTargetGroupController,
        CCIEconomicBackgroundController $cciEconomicBackgroundController,
        CCIPersonalSituationController $cciPersonalSituationController,
        CCIPresentSituationController $cciPresentSituationController,
        CCIRationaleController $cciRationaleController,
        CCIStatisticsController $cciStatisticsController,
        // IGE controllers...
        IGEInstitutionInfoController $igeInstitutionInfoController,
        IGEBeneficiariesSupportedController $igeBeneficiariesSupportedController,
        IGENewBeneficiariesController $igeNewBeneficiariesController,
        IGEOngoingBeneficiariesController $igeOngoingBeneficiariesController,
        IGEBudgetController $igeBudgetController,
        IGEDevelopmentMonitoringController $igeDevelopmentMonitoringController,
        // LDP controllers
        LDPInterventionLogicController $ldpInterventionLogicController,
        LDPNeedAnalysisController $ldpNeedAnalysisController,
        LDPTargetGroupController $ldpTargetGroupController,
        // RST controllers
        RSTBeneficiariesAreaController $rstBeneficiariesAreaController,
        RSTGeographicalAreaController $rstGeographicalAreaController,
        RSTInstitutionInfoController $rstInstitutionInfoController,
        RSTTargetGroupAnnexureController $rstTargetGroupAnnexureController,
        RSTTargetGroupController $rstTargetGroupController

    ) {
        $this->logicalFrameworkController = $logicalFrameworkController;
        $this->sustainabilityController = $sustainabilityController;
        //Edu-RUT
        $this->eduRUTBasicInfoController = $eduRUTBasicInfoController;
        $this->eduRUTTargetGroupController = $eduRUTTargetGroupController;
        $this->eduRUTAnnexedTargetGroupController = $eduRUTAnnexedTargetGroupController;
        // CIC
        $this->cicBasicInfoController = $cicBasicInfoController;
        // CCI
        $this->cciAchievementsController = $cciAchievementsController;
        $this->cciAgeProfileController = $cciAgeProfileController;
        $this->cciAnnexedTargetGroupController = $cciAnnexedTargetGroupController;
        $this->cciEconomicBackgroundController = $cciEconomicBackgroundController;
        $this->cciPersonalSituationController = $cciPersonalSituationController;
        $this->cciPresentSituationController = $cciPresentSituationController;
        $this->cciRationaleController = $cciRationaleController;
        $this->cciStatisticsController = $cciStatisticsController;
        // IGE controllers...
        $this->igeInstitutionInfoController = $igeInstitutionInfoController;
        $this->igeBeneficiariesSupportedController = $igeBeneficiariesSupportedController;
        $this->igeNewBeneficiariesController = $igeNewBeneficiariesController;
        $this->igeOngoingBeneficiariesController = $igeOngoingBeneficiariesController;
        $this->igeBudgetController = $igeBudgetController;
        $this->igeDevelopmentMonitoringController = $igeDevelopmentMonitoringController;
        // LDP controllers
        $this->ldpInterventionLogicController = $ldpInterventionLogicController;
        $this->ldpNeedAnalysisController = $ldpNeedAnalysisController;
        $this->ldpTargetGroupController = $ldpTargetGroupController;
        // RST controllers
        $this->rstBeneficiariesAreaController = $rstBeneficiariesAreaController;
        $this->rstGeographicalAreaController = $rstGeographicalAreaController;
        $this->rstInstitutionInfoController = $rstInstitutionInfoController;
        $this->rstTargetGroupAnnexureController = $rstTargetGroupAnnexureController;
        $this->rstTargetGroupController = $rstTargetGroupController;


    }

    public function index()
    {
        $projects = Project::all();
        $user = Auth::user();

        // Fetch projects where the user is either the owner or the in-charge
        $projects = Project::where('user_id', $user->id)
                       ->orWhere('in_charge', $user->id)
                       ->get();

        return view('projects.Oldprojects.index', compact('projects', 'user'));
    }

    public function create()
    {
        $users = User::all();
        $user = Auth::user();
        return view('projects.Oldprojects.createProjects', compact('users', 'user'));
    }


    public function store(Request $request)
{
    DB::beginTransaction();
    try {
        Log::info('ProjectController@store - Data received from form', $request->all());

        // Store the main project details first
        $project = (new GeneralInfoController())->store($request);

        // Now pass the $project->project_id to the LogicalFrameworkController
        $keyInformation = (new KeyInformationController())->store($request, $project);
        $budget = (new BudgetController())->store($request, $project);
        $attachments = (new AttachmentController())->store($request, $project);

        // Ensure project_id is passed to LogicalFrameworkController
        $request->merge(['project_id' => $project->project_id]);
        $this->logicalFrameworkController->store($request);
        $this->sustainabilityController->store($request, $project->project_id);
        // Check for Education Rural-Urban-Tribal project type
        if ($request->project_type == 'Rural-Urban-Tribal') {
            $this->eduRUTBasicInfoController->store($request, $project->project_id);
            $this->eduRUTTargetGroupController->store($request, $project->project_id);
            $this->eduRUTAnnexedTargetGroupController->store($request);
        }
        elseif ($request->project_type == 'PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER') {
            $this->cicBasicInfoController->store($request, $project->project_id);
        }
        // Check for CCI project type
        elseif ($project->project_type === 'CHILD CARE INSTITUTION') {
            // CCI Project type logic
            $this->cciAchievementsController->store($request, $project->project_id);
            $this->cciAgeProfileController->store($request, $project->project_id);
            $this->cciAnnexedTargetGroupController->store($request, $project->project_id);
            $this->cciEconomicBackgroundController->store($request, $project->project_id);
            $this->cciPersonalSituationController->store($request, $project->project_id);
            $this->cciPresentSituationController->store($request, $project->project_id);
            $this->cciRationaleController->store($request, $project->project_id);
            $this->cciStatisticsController->store($request, $project->project_id);
        }
        //IGE project type
        elseif ($request->project_type === 'Institutional Ongoing Group Educational proposal') {
            // Call methods from the IGE controllers
            $this->igeInstitutionInfoController->store($request, $project->project_id);
            $this->igeBeneficiariesSupportedController->store($request, $project->project_id);
            $this->igeNewBeneficiariesController->store($request, $project->project_id);
            $this->igeOngoingBeneficiariesController->store($request, $project->project_id);
            $this->igeBudgetController->store($request, $project->project_id);
            $this->igeDevelopmentMonitoringController->store($request, $project->project_id);
        }
        // LDP project type
        elseif ($request->project_type == 'Livelihood Development Projects') {
            $this->ldpInterventionLogicController->store($request, $project->project_id);
            $this->ldpNeedAnalysisController->store($request, $project->project_id);
            $this->ldpTargetGroupController->store($request, $project->project_id);
        }
        // RST project type
        elseif ($request->project_type === 'Residential Skill Training Proposal 2') {  // Replace with actual type
            Log::info('Calling rstBeneficiariesAreaController@store');
            $this->rstBeneficiariesAreaController->store($request, $project->project_id);
            Log::info('Calling rstGeographicalAreaController@store');
            $this->rstGeographicalAreaController->store($request, $project->project_id);
            Log::info('Calling rstInstitutionInfoController@store');
            $this->rstInstitutionInfoController->store($request, $project->project_id);
            Log::info('Calling rstTargetGroupAnnexureController@store');
            $this->rstTargetGroupAnnexureController->store($request, $project->project_id);
            Log::info('Calling rstTargetGroupController@store');
            $this->rstTargetGroupController->store($request, $project->project_id);

            Log::info('All RST controllers called successfully');

        }


        DB::commit();

        return redirect()->route('projects.index')->with('success', 'Project created successfully.');
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('ProjectController@store - Error', ['error' => $e->getMessage()]);
        return redirect()->back()->withErrors(['error' => 'There was an error creating the project. Please try again.'])->withInput();
    }
}


    public function show($project_id)
    {
        $project = Project::where('project_id', $project_id)
            ->with('budgets', 'attachments', 'objectives', 'sustainabilities')
            ->firstOrFail();

        $user = Auth::user();

        // Initialize variables for each project type as needed
        $data = [
            'project' => $project,
            'user' => $user,
            'basicInfo' => null,
            'targetGroups' => null,
            'annexedTargetGroups' => null,
            'achievements' => null,
            'ageProfile' => null,
            'annexedTargetGroup' => null,
            'economicBackground' => null,
            'personalSituation' => null,
            'presentSituation' => null,
            'rationale' => null,
            'statistics' => null,
            'interventionLogic' => null,
            'needAnalysis' => null,
            'LDPtargetGroups' => null, // Renamed for LDP
            'IGEInstitutionInfo' => null, // Renamed for IGE
            'beneficiariesSupported' => null,
            'newBeneficiaries' => null,
            'ongoingBeneficiaries' => null,
            'budget' => null,
            'developmentMonitoring' => null,
            'RSTBeneficiariesArea' => null, // Renamed for RST
            'RSTGeographicalArea' => null, // Renamed for RST
            'RSTInstitutionInfo' => null, // Renamed for RST
            'RSTTargetGroupAnnexure' => null, // Renamed for RST
            'RSTTargetGroup' => null, // Renamed for RST
        ];

        // Handle project-specific data
        if ($project->project_type == 'Rural-Urban-Tribal') {
            $data['basicInfo'] = $this->eduRUTBasicInfoController->show($project_id);
            $data['RUTtargetGroups'] = $this->eduRUTTargetGroupController->show($project_id);
            $data['annexedTargetGroups'] = $this->eduRUTAnnexedTargetGroupController->show($project_id);
        } elseif ($project->project_type == 'PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER') {
            $data['basicInfo'] = $this->cicBasicInfoController->show($project->project_id);
        } elseif ($project->project_type === 'CHILD CARE INSTITUTION') {
            $data['achievements'] = $this->cciAchievementsController->show($project->project_id);
            $data['ageProfile'] = $this->cciAgeProfileController->show($project->project_id);
            $data['annexedTargetGroup'] = $this->cciAnnexedTargetGroupController->show($project->project_id);

            $data['economicBackground'] = $this->cciEconomicBackgroundController->show($project->project_id);
            $data['personalSituation'] = $this->cciPersonalSituationController->show($project->project_id);
            $data['presentSituation'] = $this->cciPresentSituationController->show($project->project_id);
            $data['rationale'] = $this->cciRationaleController->show($project->project_id);
            $data['statistics'] = $this->cciStatisticsController->show($project->project_id);
        } elseif ($project->project_type === 'Institutional Ongoing Group Educational proposal') {
            $data['IGEInstitutionInfo'] = $this->igeInstitutionInfoController->show($project->project_id);
            $data['beneficiariesSupported'] = $this->igeBeneficiariesSupportedController->show($project->project_id);
            $data['newBeneficiaries'] = $this->igeNewBeneficiariesController->show($project->project_id);
            $data['ongoingBeneficiaries'] = $this->igeOngoingBeneficiariesController->show($project->project_id);
            $data['IGEbudget'] = $this->igeBudgetController->show($project->project_id);
            $data['developmentMonitoring'] = $this->igeDevelopmentMonitoringController->show($project->project_id);
        } elseif ($project->project_type == 'Livelihood Development Projects') {
            $data['interventionLogic'] = $this->ldpInterventionLogicController->show($project_id);
            $data['needAnalysis'] = $this->ldpNeedAnalysisController->show($project_id);
            $data['LDPtargetGroups'] = $this->ldpTargetGroupController->show($project_id);
        } elseif ($project->project_type === 'Residential Skill Training Proposal 2') {
            $data['RSTBeneficiariesArea'] = $this->rstBeneficiariesAreaController->show($project->project_id);
            $data['RSTGeographicalArea'] = $this->rstGeographicalAreaController->show($project->project_id);
            $data['RSTInstitutionInfo'] = $this->rstInstitutionInfoController->show($project->project_id);
            $data['RSTTargetGroupAnnexure'] = $this->rstTargetGroupAnnexureController->show($project->project_id);
            $data['RSTTargetGroup'] = $this->rstTargetGroupController->show($project->project_id);
        }

        // Pass data to the view
        return view('projects.Oldprojects.show', $data);
    }



    public function edit($project_id)
    {
        Log::info('ProjectController@edit - Received project_id', ['project_id' => $project_id]);

        try {
            $project = Project::where('project_id', $project_id)
                ->with('budgets', 'attachments', 'objectives', 'sustainabilities')
                ->firstOrFail();

            Log::info('ProjectController@edit - Project type', ['project_type' => $project->project_type]);

            $user = Auth::user();
            $users = User::all();

            // Initialize variables for different project types
            $basicInfo = null;
            $targetGroups = null;
            $annexedTargetGroups = null;
            $cicBasicInfo = null;

            // Initialize variables for CCI project type
            $achievements = null;
            $ageProfile = null;
            $targetGroup = null; // Renamed to match the view's variable
            $economicBackground = null;
            $personalSituation = null;
            $presentSituation = null;
            $rationale = null;
            $statistics = null;
            // Initialize variables for IGE
            $IGEinstitutionInfo = null; //Added IGE
            $beneficiariesSupported = collect();
            $newBeneficiaries = null;
            $ongoingBeneficiaries = null;
            $budget = null;
            $developmentMonitoring = null;
            // Initialize variables for LDP
            $interventionLogic = null;
            $needAnalysis = null;
            $LDPtargetGroups = null;
            // Initialize variables for RST
            $beneficiariesArea = null;
            $geographicalArea = null;
            $RSTinstitutionInfo = null; //Added RST
            $RSTtargetGroupAnnexure = null; //Added RST
            $RSTtargetGroup = null; //Added RST


            // Handle specific project types
            if ($project->project_type == 'Rural-Urban-Tribal') {
                $basicInfo = $this->eduRUTBasicInfoController->edit($project->project_id);
                $targetGroups = $this->eduRUTTargetGroupController->edit($project->project_id);
                $annexedTargetGroups = $this->eduRUTAnnexedTargetGroupController->edit($project->project_id);
            } elseif ($project->project_type == 'PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER') {
                $cicBasicInfo = $this->cicBasicInfoController->edit($project->project_id);
            } elseif ($project->project_type === 'CHILD CARE INSTITUTION') {
                // For CCI projects, retrieve data models
                $achievements = $this->cciAchievementsController->edit($project->project_id);
                $ageProfile = $this->cciAgeProfileController->edit($project->project_id);
                $targetGroup = $this->cciAnnexedTargetGroupController->edit($project->project_id);
                $economicBackground = $this->cciEconomicBackgroundController->edit($project->project_id);
                $personalSituation = $this->cciPersonalSituationController->edit($project->project_id);
                $presentSituation = $this->cciPresentSituationController->edit($project->project_id);
                $rationale = $this->cciRationaleController->edit($project->project_id);
                $statistics = $this->cciStatisticsController->edit($project->project_id);
            } elseif ($project->project_type === 'Institutional Ongoing Group Educational proposal') {
                $IGEinstitutionInfo = $this->igeInstitutionInfoController->edit($project->project_id);
                $beneficiariesSupported = $this->igeBeneficiariesSupportedController->edit($project->project_id);
                // $newBeneficiaries = $this->igeNewBeneficiariesController->edit($project->project_id);
                $newBeneficiaries = $this->igeNewBeneficiariesController->edit($project->project_id);
                $ongoingBeneficiaries = $this->igeOngoingBeneficiariesController->edit($project->project_id);
                $budget = $this->igeBudgetController->edit($project->project_id);
                $developmentMonitoring = $this->igeDevelopmentMonitoringController->edit($project->project_id);
            }
            // LDP project type
            elseif ($project->project_type == 'Livelihood Development Projects') {
                $interventionLogic = $this->ldpInterventionLogicController->edit($project_id);
                // $needAnalysis = $this->ldpNeedAnalysisController->edit($project_id);
                $needAnalysis = $this->ldpNeedAnalysisController->edit($project_id);

                $LDPtargetGroups = $this->ldpTargetGroupController->edit($project_id);
            }
            // RST project type
            elseif ($project->project_type === 'Residential Skill Training Proposal 2') {  // Replace with actual type
                $beneficiariesArea = $this->rstBeneficiariesAreaController->edit($project->project_id);
                $geographicalArea = $this->rstGeographicalAreaController->edit($project->project_id);
                $RSTinstitutionInfo = $this->rstInstitutionInfoController->edit($project->project_id);
                $RSTtargetGroupAnnexure = $this->rstTargetGroupAnnexureController->edit($project->project_id);
                $RSTtargetGroup = $this->rstTargetGroupController->edit($project->project_id);
            }


            return view('projects.Oldprojects.edit', compact(
                'project', 'user', 'users',
                // Variables for different project types
                'basicInfo', 'targetGroups', 'annexedTargetGroups', 'cicBasicInfo',
                // CCI variables
                'achievements', 'ageProfile', 'targetGroup', 'economicBackground',
                'personalSituation', 'presentSituation', 'rationale', 'statistics',
                 // IGE variables
                'IGEinstitutionInfo', 'beneficiariesSupported', 'newBeneficiaries',
                'ongoingBeneficiaries', 'budget', 'developmentMonitoring',
                // LDP variables
                'interventionLogic', 'needAnalysis', 'LDPtargetGroups',
                // RST variables
                'beneficiariesArea', 'geographicalArea', 'RSTinstitutionInfo',
                'RSTtargetGroupAnnexure', 'RSTtargetGroup'

            ));
        } catch (\Exception $e) {
            Log::error('ProjectController@edit - Error retrieving project data', ['error' => $e->getMessage()]);
            return redirect()->back()->withErrors(['error' => 'Unable to retrieve project data.']);
        }
    }




public function update(Request $request, $project_id)
{
    Log::info('ProjectController@update - Start', ['project_id' => $project_id, 'request_data' => $request->all()]);

    DB::beginTransaction();
    try {
        $project = Project::where('project_id', $project_id)->firstOrFail();

        // Update the project details
        $project = (new GeneralInfoController())->update($request, $project->project_id);

        $keyInformation = (new KeyInformationController())->update($request, $project);
        $budget = (new BudgetController())->update($request, $project);
        $attachments = (new AttachmentController())->update($request, $project->project_id);
        $this->logicalFrameworkController->update($request, $project->project_id);
        $this->sustainabilityController->update($request, $project->project_id);

        if ($project->project_type == 'Rural-Urban-Tribal') {
            $this->eduRUTBasicInfoController->update($request, $project->project_id);
            $this->eduRUTTargetGroupController->update($request, $project->project_id);
            $this->eduRUTAnnexedTargetGroupController->update($request, $project->project_id);
        }
        elseif ($project->project_type == 'PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER') {
            $this->cicBasicInfoController->update($request, $project->project_id);
        }
        elseif ($project->project_type === 'CHILD CARE INSTITUTION') {
            // CCI Project type logic
            $this->cciAchievementsController->update($request, $project->project_id);
            $this->cciAgeProfileController->update($request, $project->project_id);
            $this->cciAnnexedTargetGroupController->update($request, $project->project_id);
            $this->cciEconomicBackgroundController->update($request, $project->project_id);
            $this->cciPersonalSituationController->update($request, $project->project_id);
            $this->cciPresentSituationController->update($request, $project->project_id);
            $this->cciRationaleController->update($request, $project->project_id);
            $this->cciStatisticsController->update($request, $project->project_id);
        }
        //IGE project type
        elseif ($project->project_type === 'Institutional Ongoing Group Educational proposal') {
            // Call the update methods from IGE controllers
            $this->igeInstitutionInfoController->update($request, $project->project_id);
            $this->igeBeneficiariesSupportedController->update($request, $project->project_id);
            $this->igeNewBeneficiariesController->update($request, $project->project_id);
            $this->igeOngoingBeneficiariesController->update($request, $project->project_id);
            $this->igeBudgetController->update($request, $project->project_id);
            $this->igeDevelopmentMonitoringController->update($request, $project->project_id);
        }
        // LDP project type
        elseif ($project->project_type == 'Livelihood Development Projects') {
            $this->ldpInterventionLogicController->update($request, $project->project_id);
            $this->ldpNeedAnalysisController->update($request, $project->project_id);
            $this->ldpTargetGroupController->update($request, $project->project_id);
        }
        // RST project type
        elseif ($project->project_type === 'Residential Skill Training Proposal 2') {  // Replace with actual type
            $this->rstBeneficiariesAreaController->update($request, $project->project_id);
            $this->rstGeographicalAreaController->update($request, $project->project_id);
            $this->rstInstitutionInfoController->update($request, $project->project_id);
            $this->rstTargetGroupAnnexureController->update($request, $project->project_id);
            $this->rstTargetGroupController->update($request, $project->project_id);
        }

        DB::commit();
        return redirect()->route('projects.index')->with('success', 'Project updated successfully.');
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('ProjectController@update - Error', ['project_id' => $project_id, 'error' => $e->getMessage()]);
        return redirect()->back()->withErrors(['error' => 'There was an error updating the project. Please try again.'])->withInput();
    }
}



    public function destroy($project_id)
    {
        DB::beginTransaction();
        try {
            $this->sustainabilityController->destroy($project_id);
            $this->logicalFrameworkController->destroy($project_id);

            $project = Project::where('project_id', $project_id)->firstOrFail();

            // Check for Education Rural-Urban-Tribal project type
            if ($project->project_type == 'Rural-Urban-Tribal') {
                $this->eduRUTBasicInfoController->destroy($project_id);
                $this->eduRUTTargetGroupController->destroy($project_id);
                $this->eduRUTAnnexedTargetGroupController->destroy($project_id);
            }
            // Check for CCI project type
            elseif ($project->project_type == 'PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER') {
                $this->cicBasicInfoController->destroy($project_id);
            }
            // Check for CCI project type
            elseif ($project->project_type === 'CHILD CARE INSTITUTION') {
                // CCI Project type logic
                $this->cciAchievementsController->destroy($project_id);
                $this->cciAgeProfileController->destroy($project_id);
                $this->cciAnnexedTargetGroupController->destroy($project_id);
                $this->cciEconomicBackgroundController->destroy($project_id);
                $this->cciPersonalSituationController->destroy($project_id);
                $this->cciPresentSituationController->destroy($project_id);
                $this->cciRationaleController->destroy($project_id);
                $this->cciStatisticsController->destroy($project_id);
            }
            //IGE project type
            elseif ($project->project_type === 'Institutional Ongoing Group Educational proposal') {
                // Call the destroy methods from IGE controllers
                $this->igeInstitutionInfoController->destroy($project_id);
                $this->igeBeneficiariesSupportedController->destroy($project_id);
                $this->igeNewBeneficiariesController->destroy($project_id);
                $this->igeOngoingBeneficiariesController->destroy($project_id);
                $this->igeBudgetController->destroy($project_id);
                $this->igeDevelopmentMonitoringController->destroy($project_id);
            }
            // LDP project type
            elseif ($project->project_type == 'Livelihood Development Projects') {
                $this->ldpInterventionLogicController->destroy($project_id);
                $this->ldpNeedAnalysisController->destroy($project_id);
                $this->ldpTargetGroupController->destroy($project_id);
            }
            // RST project type
            elseif ($project->project_type === 'Residential Skill Training Proposal 2') {  // Replace with actual type
                $this->rstBeneficiariesAreaController->destroy($project_id);
                $this->rstGeographicalAreaController->destroy($project_id);
                $this->rstInstitutionInfoController->destroy($project_id);
                $this->rstTargetGroupAnnexureController->destroy($project_id);
                $this->rstTargetGroupController->destroy($project_id);
            }


            $project->delete();

            DB::commit();
            return redirect()->route('projects.index')->with('success', 'Project deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ProjectController@destroy - Error', ['error' => $e->getMessage()]);
            return redirect()->back()->withErrors(['error' => 'There was an error deleting the project. Please try again.']);
        }
    }

}
