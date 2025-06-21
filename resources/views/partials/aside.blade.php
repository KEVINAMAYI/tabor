<aside class="left-sidebar with-vertical">
    <!-- ---------------------------------- -->
    <!-- Start Vertical Layout Sidebar -->
    <!-- ---------------------------------- -->

    <div>

        <div class="brand-logo d-flex align-items-center">
            <a href="default-sidebar/index.html" class="text-nowrap logo-img">
                <img src="assets/images/logos/logo.svg" alt="Logo"/>
            </a>

        </div>

        <!-- ---------------------------------- -->
        <!-- Dashboard -->
        <!-- ---------------------------------- -->
        <nav class="sidebar-nav scroll-sidebar" data-simplebar>
            <ul class="sidebar-menu" id="sidebarnav">

                <li class="nav-small-cap">
                    <iconify-icon icon="solar:menu-dots-linear" class="mini-icon"></iconify-icon>
                    <span class="hide-menu">Dashboards</span>
                </li>

                <li class="sidebar-item">
                    <a class="sidebar-link" href="javascript:void(0)" id="get-url" aria-expanded="false">
                        <iconify-icon icon="solar:widget-add-line-duotone" class=""></iconify-icon>
                        <span class="hide-menu">Dashboard</span>
                    </a>
                </li>

                {{-- Students Menu --}}
                <li class="sidebar-item {{ request()->routeIs('students.*') ? 'active' : '' }}">
                    <a class="sidebar-link has-arrow {{ request()->routeIs('students.*') ? 'active' : '' }}"
                       href="javascript:void(0)"
                       aria-expanded="{{ request()->routeIs('students.*') ? 'true' : 'false' }}">
                        <iconify-icon icon="solar:shield-user-line-duotone"></iconify-icon>
                        <span class="hide-menu">Students</span>
                    </a>
                    <ul aria-expanded="{{ request()->routeIs('students.*') ? 'true' : 'false' }}"
                        class="collapse first-level {{ request()->routeIs('students.*') ? 'in' : '' }}">
                        <li class="sidebar-item">
                            <a class="sidebar-link {{ request()->routeIs('students.index') ? 'active' : '' }}"
                               href="{{ route('students.index') }}">
                                <span class="icon-small"></span>
                                <span class="hide-menu">List Students</span>
                            </a>
                        </li>
                    </ul>
                </li>

                {{-- Lecturers Menu --}}
                <li class="sidebar-item {{ request()->routeIs('lecturers.*') ? 'active' : '' }}">
                    <a class="sidebar-link has-arrow {{ request()->routeIs('lecturers.*') ? 'active' : '' }}"
                       href="javascript:void(0)"
                       aria-expanded="{{ request()->routeIs('lecturers.*') ? 'true' : 'false' }}">
                        <iconify-icon icon="solar:user-plus-rounded-line-duotone"></iconify-icon>
                        <span class="hide-menu">Lecturers</span>
                    </a>
                    <ul aria-expanded="{{ request()->routeIs('lecturers.*') ? 'true' : 'false' }}"
                        class="collapse first-level {{ request()->routeIs('lecturers.*') ? 'in' : '' }}">
                        <li class="sidebar-item">
                            <a class="sidebar-link {{ request()->routeIs('lecturers.index') ? 'active' : '' }}"
                               href="{{ route('lecturers.index') }}">
                                <span class="icon-small"></span>
                                <span class="hide-menu">List Lecturers</span>
                            </a>
                        </li>
                    </ul>
                </li>


                {{-- Courses Menu --}}
                <li class="sidebar-item {{ request()->routeIs('courses.*') ? 'active' : '' }}">
                    <a class="sidebar-link has-arrow {{ request()->routeIs('courses.*') ? 'active' : '' }}"
                       href="javascript:void(0)"
                       aria-expanded="{{ request()->routeIs('courses.*') ? 'true' : 'false' }}">
                        <iconify-icon icon="solar:clipboard-text-line-duotone"></iconify-icon>
                        <span class="hide-menu">Courses</span>
                    </a>
                    <ul aria-expanded="{{ request()->routeIs('courses.*') ? 'true' : 'false' }}"
                        class="collapse first-level {{ request()->routeIs('courses.*') ? 'in' : '' }}">
                        <li class="sidebar-item">
                            <a class="sidebar-link {{ request()->routeIs('courses.index') ? 'active' : '' }}"
                               href="{{ route('courses.index') }}">
                                <span class="icon-small"></span>
                                <span class="hide-menu">List Courses</span>
                            </a>
                        </li>
                    </ul>
                </li>

                {{-- Classes/Year Menu --}}
                <li class="sidebar-item {{ request()->routeIs('classes.*') ? 'active' : '' }}">
                    <a class="sidebar-link has-arrow {{ request()->routeIs('classes.*') ? 'active' : '' }}"
                       href="javascript:void(0)"
                       aria-expanded="{{ request()->routeIs('classes.*') ? 'true' : 'false' }}">
                        <iconify-icon icon="solar:clipboard-text-line-duotone"></iconify-icon>
                        <span class="hide-menu">Classes/Year</span>
                    </a>
                    <ul aria-expanded="{{ request()->routeIs('classes.*') ? 'true' : 'false' }}"
                        class="collapse first-level {{ request()->routeIs('classes.*') ? 'in' : '' }}">
                        <li class="sidebar-item">
                            <a class="sidebar-link {{ request()->routeIs('classes.index') ? 'active' : '' }}"
                               href="{{ route('classes.index') }}">
                                <span class="icon-small"></span>
                                <span class="hide-menu">List Classes/Year</span>
                            </a>
                        </li>
                    </ul>
                </li>


                {{-- Attendance Tracking --}}
                <li class="sidebar-item {{ request()->routeIs('attendance.*') ? 'active' : '' }}">
                    <a class="sidebar-link has-arrow {{ request()->routeIs('attendance.*') ? 'active' : '' }}"
                       href="javascript:void(0)"
                       aria-expanded="{{ request()->routeIs('attendance.*') ? 'true' : 'false' }}">
                        <iconify-icon icon="solar:repeat-one-minimalistic-bold-duotone"></iconify-icon>
                        <span class="hide-menu">Attendance Tracking</span>
                    </a>
                    <ul aria-expanded="{{ request()->routeIs('attendance.*') ? 'true' : 'false' }}"
                        class="collapse first-level {{ request()->routeIs('attendance.*') ? 'in' : '' }}">
                        <li class="sidebar-item">
                            <a class="sidebar-link {{ request()->routeIs('attendance.index') ? 'active' : '' }}"
                               href="{{ route('attendance.index') }}">
                                <span class="icon-small"></span>
                                <span class="hide-menu">View Attendance</span>
                            </a>
                        </li>
                    </ul>
                </li>


                {{-- Financials (Payments) --}}
                <li class="sidebar-item {{ request()->routeIs('payments.*') ? 'active' : '' }}">
                    <a class="sidebar-link has-arrow {{ request()->routeIs('payments.*') ? 'active' : '' }}"
                       href="javascript:void(0)"
                       aria-expanded="{{ request()->routeIs('payments.*') ? 'true' : 'false' }}">
                        <iconify-icon icon="solar:wallet-line-duotone"></iconify-icon>
                        <span class="hide-menu">Financials</span>
                    </a>
                    <ul aria-expanded="{{ request()->routeIs('payments.*') ? 'true' : 'false' }}"
                        class="collapse first-level {{ request()->routeIs('payments.*') ? 'in' : '' }}">
                        <li class="sidebar-item">
                            <a class="sidebar-link {{ request()->routeIs('payments.index') ? 'active' : '' }}"
                               href="{{ route('payments.index') }}">
                                <span class="icon-small"></span>
                                <span class="hide-menu">View Payment</span>
                            </a>
                        </li>
                    </ul>
                </li>


                {{-- Library --}}
                <li class="sidebar-item {{ request()->routeIs('library.*') ? 'active' : '' }}">
                    <a class="sidebar-link has-arrow {{ request()->routeIs('library.*') ? 'active' : '' }}"
                       href="javascript:void(0)"
                       aria-expanded="{{ request()->routeIs('library.*') ? 'true' : 'false' }}">
                        <iconify-icon icon="solar:book-line-duotone"></iconify-icon>
                        <span class="hide-menu">Library</span>
                    </a>
                    <ul aria-expanded="{{ request()->routeIs('library.*') ? 'true' : 'false' }}"
                        class="collapse first-level {{ request()->routeIs('library.*') ? 'in' : '' }}">
                        <li class="sidebar-item">
                            <a class="sidebar-link {{ request()->routeIs('library.index') ? 'active' : '' }}"
                               href="{{ route('library.index') }}">
                                <span class="icon-small"></span>
                                <span class="hide-menu">Issue Book</span>
                            </a>
                        </li>
                    </ul>
                </li>


                {{-- Exams --}}
                <li class="sidebar-item {{ request()->routeIs('exams.*') ? 'active' : '' }}">
                    <a class="sidebar-link has-arrow {{ request()->routeIs('exams.*') ? 'active' : '' }}"
                       href="javascript:void(0)"
                       aria-expanded="{{ request()->routeIs('exams.*') ? 'true' : 'false' }}">
                        <iconify-icon icon="solar:document-text-line-duotone"></iconify-icon>
                        <span class="hide-menu">Exams</span>
                    </a>
                    <ul aria-expanded="{{ request()->routeIs('exams.*') ? 'true' : 'false' }}"
                        class="collapse first-level {{ request()->routeIs('exams.*') ? 'in' : '' }}">
                        <li class="sidebar-item">
                            <a class="sidebar-link {{ request()->routeIs('exams.index') ? 'active' : '' }}"
                               href="{{ route('exams.index') }}">
                                <span class="icon-small"></span>
                                <span class="hide-menu">Exam Results</span>
                            </a>
                        </li>
                    </ul>
                </li>


                <li class="sidebar-item">
                    <a class="sidebar-link has-arrow" href="javascript:void(0)" aria-expanded="false">
                        <iconify-icon icon="solar:settings-line-duotone"></iconify-icon>
                        <span class="hide-menu">Settings</span>
                    </a>
                    <ul aria-expanded="false" class="collapse first-level">
                        <li class="sidebar-item">
                            <a class="sidebar-link" href="#">
                                <span class="icon-small"></span>
                                <span class="hide-menu">General Settings</span>
                            </a>
                        </li>
                        <li class="sidebar-item">
                            <a class="sidebar-link" href="#">
                                <span class="icon-small"></span>
                                <span class="hide-menu">User Roles</span>
                            </a>
                        </li>
                    </ul>
                </li>

                <li class="sidebar-item">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="sidebar-link w-100 text-start border-0 bg-transparent d-flex align-items-center gap-2 px-3 py-2">
                            <iconify-icon icon="solar:logout-2-line-duotone"></iconify-icon>
                            <span class="hide-menu">Logout</span>
                        </button>
                    </form>
                </li>


            </ul>
        </nav>

    </div>
</aside>
