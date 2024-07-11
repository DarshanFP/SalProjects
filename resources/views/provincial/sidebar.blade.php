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
            <a href="{{ route('provincial.dashboard') }}" class="nav-link">
            <i class="link-icon" data-feather="box"></i>
            <span class="link-title">Dashboard</span>
            </a>
        </li>
        <li class="nav-item nav-category">web apps</li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="collapse" href="#" role="button" aria-expanded="false" aria-controls="emails">
            <i class="link-icon" data-feather="mail"></i>
            <span class="link-title">Email</span>
            <i class="link-arrow" data-feather="chevron-down"></i>
            </a>
            <div class="collapse" id="emails">
            <ul class="nav sub-menu">
                <li class="nav-item">
                <a href="#" class="nav-link">Inbox</a>
                </li>
                <li class="nav-item">
                <a href="#" class="nav-link">Read</a>
                </li>
                <li class="nav-item">
                <a href="#" class="nav-link">Compose</a>
                </li>
            </ul>
            </div>
        </li>
        {{-- <li class="nav-item">
            <a href="pages/apps/chat.html" class="nav-link">
            <i class="link-icon" data-feather="message-square"></i>
            <span class="link-title">Chat</span>
            </a>
        </li> --}}
        <li class="nav-item">
            <a href="#" class="nav-link">
            <i class="link-icon" data-feather="calendar"></i>
            <span class="link-title">Calendar</span>
            </a>
        </li>
        {{-- my team --}}
        <li class="nav-item nav-category">My Team</li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="collapse" href="#uiComponents" role="button" aria-expanded="false" aria-controls="uiComponents">
                    <i class="link-icon" data-feather="feather"></i>
                    <span class="link-title">Manage Team</span>
                    <i class="link-arrow" data-feather="chevron-down"></i>
                </a>
                    {{-- reports sub menu --}}
                    <div class="collapse" id="uiComponents">
                        <ul class="nav sub-menu">
                            <li class="nav-item">
                                <a href="{{ route('provincial.createExecutor') }}" class="nav-link">Add Member</a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('provincial.executors') }}" class="nav-link">View Member</a>
                            </li>
                            {{-- <li class="nav-item">
                                <a href="{{ route('quarterly.institutionalSupport.create') }}" class="nav-link">Institutional / Non-Inst Support</a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('quarterly.womenInDistress.create') }}" class="nav-link">Women in Distress</a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('quarterly.skillTraining.create') }}" class="nav-link"> Skill Training</a>
                            </li> --}}
                        </ul>
                    </div>
            </li>
        {{-- end my team --}}

        <li class="nav-item nav-category">Reports</li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="collapse" href="#uiComponents" role="button" aria-expanded="false" aria-controls="uiComponents">
                    <i class="link-icon" data-feather="feather"></i>
                    <span class="link-title">Quarterly Reports</span>
                    <i class="link-arrow" data-feather="chevron-down"></i>
                </a>
                    {{-- reports sub menu --}}
                    <div class="collapse" id="uiComponents">
                        <ul class="nav sub-menu">
                            <li class="nav-item">
                                <a href="#" class="nav-link">All Project Reports</a>
                            </li>
                            <li class="nav-item">
                                <a href="#" class="nav-link">Development Livelihood</a>
                            </li>
                            <li class="nav-item">
                                <a href="#" class="nav-link">Institutional / Non-Inst Support</a>
                            </li>
                            <li class="nav-item">
                                <a href="#" class="nav-link">Women in Distress</a>
                            </li>
                            <li class="nav-item">
                                <a href="#" class="nav-link"> Skill Training</a>
                            </li>
                        </ul>
                    </div>
            </li>

         <li class="nav-item">
            <a class="nav-link" data-bs-toggle="collapse" href="#advancedUI" role="button" aria-expanded="false" aria-controls="advancedUI">
            <i class="link-icon" data-feather="anchor"></i>
            <span class="link-title">Biannual Reports</span>
            <i class="link-arrow" data-feather="chevron-down"></i>
            </a>
            {{-- <div class="collapse" id="advancedUI">
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
            </div> --}}
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="collapse" href="#forms" role="button" aria-expanded="false" aria-controls="forms">
            <i class="link-icon" data-feather="inbox"></i>
            <span class="link-title">Annual Reports</span>
            <i class="link-arrow" data-feather="chevron-down"></i>
            </a>
            {{-- <div class="collapse" id="forms">
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
            </div> --}}
        </li>
        {{-- <li class="nav-item">
            <a class="nav-link"  data-bs-toggle="collapse" href="#charts" role="button" aria-expanded="false" aria-controls="charts">
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
        <li class="nav-item nav-category">Project Applicatipon</li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="collapse" href="#general-pages" role="button" aria-expanded="false" aria-controls="general-pages">
            <i class="link-icon" data-feather="book"></i>
            <span class="link-title">Individual</span>
            <i class="link-arrow" data-feather="chevron-down"></i>
            </a>
            <div class="collapse" id="general-pages">
            <ul class="nav sub-menu">
                <li class="nav-item">
                <a href="#" class="nav-link">Health</a>
                </li>
                <li class="nav-item">
                <a href="#" class="nav-link">Education</a>
                </li>
                <li class="nav-item">
                <a href="#" class="nav-link">Social</a>
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
            <a class="nav-link" data-bs-toggle="collapse" href="#authPages" role="button" aria-expanded="false" aria-controls="authPages">
            <i class="link-icon" data-feather="unlock"></i>
            <span class="link-title">Group</span>
            <i class="link-arrow" data-feather="chevron-down"></i>
            </a>
            <div class="collapse" id="authPages">
            <ul class="nav sub-menu">
                <li class="nav-item">
                <a href="#" class="nav-link">Health</a>
                </li>
                <li class="nav-item">
                <a href="#" class="nav-link">Education</a>
                </li>
                <li class="nav-item">
                <a href="#" class="nav-link">Social</a>
                </li>
            </ul>
            </div>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="collapse" href="#errorPages" role="button" aria-expanded="false" aria-controls="errorPages">
            <i class="link-icon" data-feather="cloud-off"></i>
            <span class="link-title">Other</span>
            <i class="link-arrow" data-feather="chevron-down"></i>
            </a>
            <div class="collapse" id="errorPages">
            <ul class="nav sub-menu">
                <li class="nav-item">
                <a href="#" class="nav-link">404</a>
                </li>
                <li class="nav-item">
                <a href="#" class="nav-link">500</a>
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
