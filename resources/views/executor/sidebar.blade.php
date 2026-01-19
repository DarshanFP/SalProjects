<nav class="sidebar">
    <div class="sidebar-header">
        <a href="#" class="sidebar-brand">
        SAL <span>Projects</span>
        </a>
        <div class="sidebar-toggler not-active">
        <span></span>
        <span></span>
        <span></span>
        </div>
    </div>
    <div class="sidebar-body">
        <ul class="nav">
            <li class="nav-item nav-category">Main</li>
            <li class="nav-item">
                <a href="{{ route('executor.dashboard') }}" class="nav-link">
                <i class="link-icon" data-feather="box"></i>
                <span class="link-title">Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('activities.my-activities') }}" class="nav-link">
                <i class="link-icon" data-feather="activity"></i>
                <span class="link-title">My Activities</span>
                </a>
            </li>
            <li class="nav-item nav-category">web apps</li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="collapse" href="#webAppsCollapse" role="button" aria-expanded="false" aria-controls="webAppsCollapse">
                <i class="link-icon" data-feather="mail"></i>
                <span class="link-title">Email</span>
                <i class="link-arrow" data-feather="chevron-down"></i>
                </a>
                <div class="collapse" id="webAppsCollapse">
                    <ul class="nav sub-menu">
                        <li class="nav-item">
                            <a href="#" class="nav-link">Email</a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link">Messages</a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link">Notifications</a>
                        </li>
                    </ul>
                </div>
            </li>
            {{-- Create old Projects --}}
            <li class="nav-item nav-category">Create projects</li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="collapse" href="#createProjectsCollapse" role="button" aria-expanded="false" aria-controls="createProjectsCollapse">
                <i class="link-icon" data-feather="mail"></i>
                <span class="link-title">Projects</span>
                <i class="link-arrow" data-feather="chevron-down"></i>
                </a>
                <div class="collapse" id="createProjectsCollapse">
                    <ul class="nav sub-menu">
                        <li class="nav-item">
                            <a href="{{ route('projects.create') }}" class="nav-link">Write Project</a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('projects.index') }}" class="nav-link">Pending Projects</a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('projects.approved') }}" class="nav-link">Approved Projects</a>
                        </li>
                        {{-- <li class="nav-item">
                            <a href="#" class="nav-link">Compose</a>
                        </li> --}}
                    </ul>
                </div>
            </li>
            {{-- View Reports --}}
            <li class="nav-item nav-category">View Reports</li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="collapse" href="#monthlyReportsCollapse" role="button" aria-expanded="false" aria-controls="monthlyReportsCollapse">
                <i class="link-icon" data-feather="feather"></i>
                <span class="link-title">Monthly Reports</span>
                <i class="link-arrow" data-feather="chevron-down"></i>
                </a>
                <div class="collapse" id="monthlyReportsCollapse">
                    <ul class="nav sub-menu">
                        <li class="nav-item">
                            <a href="{{ route('monthly.report.index') }}" class="nav-link">My Reports List</a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('executor.report.list') }}" class="nav-link">All Reports Overview</a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('executor.report.pending') }}" class="nav-link">Pending Reports</a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('executor.report.approved') }}" class="nav-link">Approved Reports</a>
                        </li>
                        {{-- <li class="nav-item">
                            <a href="{{ route('quarterly.developmentLivelihood.index') }}" class="nav-link">Development Livelihood</a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('quarterly.institutionalSupport.index') }}" class="nav-link">Institutional / Non-Inst Support</a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('quarterly.womenInDistress.index') }}" class="nav-link">Women in Distress</a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('quarterly.skillTraining.index') }}" class="nav-link">Skill Training</a>
                        </li> --}}
                    </ul>
                </div>
            </li>

            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="collapse" href="#quarterlyReportsCollapse" role="button" aria-expanded="false" aria-controls="quarterlyReportsCollapse">
                <i class="link-icon" data-feather="file-text"></i>
                <span class="link-title">Quarterly Reports</span>
                <i class="link-arrow" data-feather="chevron-down"></i>
                </a>
                <div class="collapse" id="quarterlyReportsCollapse">
                    <ul class="nav sub-menu">
                        <li class="nav-item">
                            <a href="{{ route('aggregated.quarterly.index') }}" class="nav-link">View Quarterly Reports</a>
                        </li>
                    </ul>
                </div>
            </li>

            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="collapse" href="#biannualReportsCollapse" role="button" aria-expanded="false" aria-controls="biannualReportsCollapse">
                <i class="link-icon" data-feather="anchor"></i>
                <span class="link-title">Biannual Reports</span>
                <i class="link-arrow" data-feather="chevron-down"></i>
                </a>
                <div class="collapse" id="biannualReportsCollapse">
                    <ul class="nav sub-menu">
                        <li class="nav-item">
                            <a href="{{ route('aggregated.half-yearly.index') }}" class="nav-link">View Half-Yearly Reports</a>
                        </li>
                    </ul>
                </div>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="collapse" href="#annualReportsCollapse" role="button" aria-expanded="false" aria-controls="annualReportsCollapse">
                <i class="link-icon" data-feather="inbox"></i>
                <span class="link-title">Annual Reports</span>
                <i class="link-arrow" data-feather="chevron-down"></i>
                </a>
                <div class="collapse" id="annualReportsCollapse">
                    <ul class="nav sub-menu">
                        <li class="nav-item">
                            <a href="{{ route('aggregated.annual.index') }}" class="nav-link">View Annual Reports</a>
                        </li>
                    </ul>
                </div>
            </li>

            {{-- Create Reports --}}
            {{-- <li class="nav-item nav-category">Create Reports</li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="collapse" href="#quarterlyReportsCollapse" role="button" aria-expanded="false" aria-controls="quarterlyReportsCollapse">
                <i class="link-icon" data-feather="feather"></i>
                <span class="link-title">Quarterly Reports</span>
                <i class="link-arrow" data-feather="chevron-down"></i>
                </a>
                <div class="collapse" id="quarterlyReportsCollapse">
                    <ul class="nav sub-menu">
                        <li class="nav-item">
                            <a href="{{ route('quarterly.developmentProject.create') }}" class="nav-link">Development Projects</a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('quarterly.developmentLivelihood.create') }}" class="nav-link">Development Livelihood</a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('quarterly.institutionalSupport.create') }}" class="nav-link">Institutional / Non-Inst Support</a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('quarterly.womenInDistress.create') }}" class="nav-link">Women in Distress</a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('quarterly.skillTraining.create') }}" class="nav-link">Skill Training</a>
                        </li>
                    </ul>
                </div>
            </li>

            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="collapse" href="#biannualCreateReportsCollapse" role="button" aria-expanded="false" aria-controls="biannualCreateReportsCollapse">
                <i class="link-icon" data-feather="anchor"></i>
                <span class="link-title">Biannual Reports</span>
                <i class="link-arrow" data-feather="chevron-down"></i>
                </a>
                <div class="collapse" id="biannualCreateReportsCollapse">
                    <ul class="nav sub-menu">
                        <li class="nav-item">
                            <a href="pages/advanced-ui/cropper.html" class="nav-link">Cropper</a>
                        </li>
                        <li class="nav-item">
                            <a href="pages/advanced-ui/owl-carousel.html" class="nav-link">Owl carousel</a>
                        </li>
                        <li class="nav-item">
                            <a href="pages/advanced-ui/sortablejs.html" class="nav-link">SortableJs</a>
                        </li>
                        <li class="nav-item">
                            <a href="pages/advanced-ui/sweet-alert.html" class="nav-link">Sweet Alert</a>
                        </li>
                    </ul>
                </div>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="collapse" href="#annualCreateReportsCollapse" role="button" aria-expanded="false" aria-controls="annualCreateReportsCollapse">
                <i class="link-icon" data-feather="inbox"></i>
                <span class="link-title">Annual Reports</span>
                <i class="link-arrow" data-feather="chevron-down"></i>
                </a>
                <div class="collapse" id="annualCreateReportsCollapse">
                    <ul class="nav sub-menu">
                        <li class="nav-item">
                            <a href="pages/forms/basic-elements.html" class="nav-link">Basic Elements</a>
                        </li>
                        <li class="nav-item">
                            <a href="pages/forms/advanced-elements.html" class="nav-link">Advanced Elements</a>
                        </li>
                        <li class="nav-item">
                            <a href="pages/forms/editors.html" class="nav-link">Editors</a>
                        </li>
                        <li class="nav-item">
                            <a href="pages/forms/wizard.html" class="nav-link">Wizard</a>
                        </li>
                    </ul>
                </div>
            </li> --}}

            {{-- Other sections --}}
            {{-- <li class="nav-item">
                <a class="nav-link" data-bs-toggle="collapse" href="#charts" role="button" aria-expanded="false" aria-controls="charts">
                <i class="link-icon" data-feather="pie-chart"></i>
                <span class="link-title">Charts</span>
                <i class="link-arrow" data-feather="chevron-down"></i>
                </a>
                <div class="collapse" id="charts">
                    <ul class="nav sub-menu">
                        <li class="nav-item">
                            <a href="pages/charts/apex.html" class="nav-link">Apex</a>
                        </li>
                        <li class="nav-item">
                            <a href="pages/charts/chartjs.html" class="nav-link">ChartJs</a>
                        </li>
                        <li class="nav-item">
                            <a href="pages/charts/flot.html" class="nav-link">Flot</a>
                        </li>
                        <li class="nav-item">
                            <a href="pages/charts/morrisjs.html" class="nav-link">Morris</a>
                        </li>
                        <li class="nav-item">
                            <a href="pages/charts/peity.html" class="nav-link">Peity</a>
                        </li>
                        <li class="nav-item">
                            <a href="pages/charts/sparkline.html" class="nav-link">Sparkline</a>
                        </li>
                    </ul>
                </div>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="collapse" href="#tables" role="button" aria-expanded="false" aria-controls="tables">
                <i class="link-icon" data-feather="layout"></i>
                <span class="link-title">Table</span>
                <i class="link-arrow" data-feather="chevron-down"></i>
                </a>
                <div class="collapse" id="tables">
                    <ul class="nav sub-menu">
                        <li class="nav-item">
                            <a href="pages/tables/basic-table.html" class="nav-link">Basic Tables</a>
                        </li>
                        <li class="nav-item">
                            <a href="pages/tables/data-table.html" class="nav-link">Data Table</a>
                        </li>
                    </ul>
                </div>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="collapse" href="#icons" role="button" aria-expanded="false" aria-controls="icons">
                <i class="link-icon" data-feather="smile"></i>
                <span class="link-title">Icons</span>
                <i class="link-arrow" data-feather="chevron-down"></i>
                </a>
                <div class="collapse" id="icons">
                    <ul class="nav sub-menu">
                        <li class="nav-item">
                            <a href="pages/icons/feather-icons.html" class="nav-link">Feather Icons</a>
                        </li>
                        <li class="nav-item">
                            <a href="pages/icons/flag-icons.html" class="nav-link">Flag Icons</a>
                        </li>
                        <li class="nav-item">
                            <a href="pages/icons/mdi-icons.html" class="nav-link">Mdi Icons</a>
                        </li>
                    </ul>
                </div>
            </li> --}}

            {{-- Project Application --}}
            <li class="nav-item nav-category">Project Application</li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="collapse" href="#individualProjectCollapse" role="button" aria-expanded="false" aria-controls="individualProjectCollapse">
                <i class="link-icon" data-feather="book"></i>
                <span class="link-title">Individual</span>
                <i class="link-arrow" data-feather="chevron-down"></i>
                </a>
                <div class="collapse" id="individualProjectCollapse">
                    <ul class="nav sub-menu">
                        <li class="nav-item">
                            <a href="pages/general/blank-page.html" class="nav-link">Health</a>
                        </li>
                        <li class="nav-item">
                            <a href="pages/general/faq.html" class="nav-link">Education</a>
                        </li>
                        <li class="nav-item">
                            <a href="pages/general/invoice.html" class="nav-link">Social</a>
                        </li>
                        {{-- <li class="nav-item">
                            <a href="pages/general/profile.html" class="nav-link">Profile</a>
                        </li>
                        <li class="nav-item">
                            <a href="pages/general/pricing.html" class="nav-link">Pricing</a>
                        </li>
                        <li class="nav-item">
                            <a href="pages/general/timeline.html" class="nav-link">Timeline</a>
                        </li> --}}
                    </ul>
                </div>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="collapse" href="#groupProjectCollapse" role="button" aria-expanded="false" aria-controls="groupProjectCollapse">
                <i class="link-icon" data-feather="unlock"></i>
                <span class="link-title">Group</span>
                <i class="link-arrow" data-feather="chevron-down"></i>
                </a>
                <div class="collapse" id="groupProjectCollapse">
                    <ul class="nav sub-menu">
                        <li class="nav-item">
                            <a href="pages/auth/login.html" class="nav-link">Health</a>
                        </li>
                        <li class="nav-item">
                            <a href="pages/auth/register.html" class="nav-link">Education</a>
                        </li>
                        <li class="nav-item">
                            <a href="pages/auth/register.html" class="nav-link">Social</a>
                        </li>
                    </ul>
                </div>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="collapse" href="#otherProjectsCollapse" role="button" aria-expanded="false" aria-controls="otherProjectsCollapse">
                <i class="link-icon" data-feather="cloud-off"></i>
                <span class="link-title">Other</span>
                <i class="link-arrow" data-feather="chevron-down"></i>
                </a>
                <div class="collapse" id="otherProjectsCollapse">
                    <ul class="nav sub-menu">
                        <li class="nav-item">
                            <a href="pages/error/404.html" class="nav-link">404</a>
                        </li>
                        <li class="nav-item">
                            <a href="pages/error/500.html" class="nav-link">500</a>
                        </li>
                    </ul>
                </div>
            </li>
            <li class="nav-item nav-category">Docs</li>
            <li class="nav-item">
                <a href="#" target="_blank" class="nav-link">
                <i class="link-icon" data-feather="hash"></i>
                <span class="link-title">Documentation</span>
                </a>
            </li>
        </ul>
    </div>
</nav>
