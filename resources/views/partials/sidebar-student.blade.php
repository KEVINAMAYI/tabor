<aside class="left-sidebar with-vertical">
    <!-- ---------------------------------- -->
    <!-- Start Vertical Layout Sidebar -->
    <!-- ---------------------------------- -->

    <div>

        <div class="brand-logo mb-3 mt-3 d-flex justify-content-center align-items-center" style="height: 100px;">
            <img height="120" width="120" src="assets/images/logos/tabor_logo.png" alt="Logo"/>
        </div>

        <!-- ---------------------------------- -->
        <!-- Dashboard -->
        <!-- ---------------------------------- -->
        <nav class="sidebar-nav scroll-sidebar" data-simplebar>
            <ul class="sidebar-menu" id="sidebarnav">

                <li class="sidebar-item {{ request()->routeIs('student.dashboard') ? 'active' : '' }}">
                    <a class="sidebar-link" href="{{ route('student.dashboard') }}">
                        <iconify-icon icon="solar:widget-add-bold-duotone"></iconify-icon>
                        <span class="hide-menu">Dashboard</span>
                    </a>
                </li>

                <li class="nav-small-cap mt-3">
                    <iconify-icon icon="solar:menu-dots-bold" class="nav-small-cap-icon fs-4"></iconify-icon>
                    <span class="hide-menu">Learning</span>
                </li>

                <li class="sidebar-item {{ request()->routeIs('student.modules.*') || request()->routeIs('student.module.*') ? 'active' : '' }}">
                    <a class="sidebar-link" href="{{ route('student.modules.index') }}">
                        <iconify-icon icon="solar:book-2-bold-duotone"></iconify-icon>
                        <span class="hide-menu">My Modules</span>
                    </a>
                </li>

                <li class="sidebar-item {{ request()->routeIs('student.assignments.*') ? 'active' : '' }}">
                    <a class="sidebar-link" href="{{ route('student.assignments.index') }}">
                        <iconify-icon icon="solar:file-text-bold-duotone"></iconify-icon>
                        <span class="hide-menu">Assignments</span>
                    </a>
                </li>

                <li class="sidebar-item {{ request()->routeIs('student.grades.*') ? 'active' : '' }}">
                    <a class="sidebar-link" href="{{ route('student.grades.index') }}">
                        <iconify-icon icon="solar:diploma-bold-duotone"></iconify-icon>
                        <span class="hide-menu">My Grades</span>
                    </a>
                </li>

                <li class="sidebar-item {{ request()->routeIs('student.attendance.*') ? 'active' : '' }}">
                    <a class="sidebar-link" href="{{ route('student.attendance.index') }}">
                        <iconify-icon icon="solar:clipboard-check-bold-duotone"></iconify-icon>
                        <span class="hide-menu">Attendance</span>
                    </a>
                </li>

                <li class="sidebar-item {{ request()->routeIs('student.timetable.*') ? 'active' : '' }}">
                    <a class="sidebar-link" href="{{ route('student.timetable.index') }}">
                        <iconify-icon icon="solar:calendar-bold-duotone"></iconify-icon>
                        <span class="hide-menu">Timetable</span>
                    </a>
                </li>

                <li class="sidebar-item {{ request()->routeIs('student.announcements.*') ? 'active' : '' }}">
                    <a class="sidebar-link" href="{{ route('student.announcements.index') }}">
                        <iconify-icon icon="solar:bell-bold-duotone"></iconify-icon>
                        <span class="hide-menu">Announcements</span>
                    </a>
                </li>

                <li class="nav-small-cap mt-3">
                    <iconify-icon icon="solar:menu-dots-bold" class="nav-small-cap-icon fs-4"></iconify-icon>
                    <span class="hide-menu">Finance</span>
                </li>

                <li class="sidebar-item {{ request()->routeIs('student.enrollments.*') ? 'active' : '' }}">
                    <a class="sidebar-link" href="{{ route('student.enrollments.index') }}">
                        <iconify-icon icon="solar:document-add-bold-duotone"></iconify-icon>
                        <span class="hide-menu">My Enrollment</span>
                    </a>
                </li>

                <li class="sidebar-item {{ request()->routeIs('student.payments.*') ? 'active' : '' }}">
                    <a class="sidebar-link" href="{{ route('student.payments.index') }}">
                        <iconify-icon icon="solar:wallet-bold-duotone"></iconify-icon>
                        <span class="hide-menu">My Payments</span>
                    </a>
                </li>

                <li class="nav-small-cap mt-3">
                    <iconify-icon icon="solar:menu-dots-bold" class="nav-small-cap-icon fs-4"></iconify-icon>
                    <span class="hide-menu">Account</span>
                </li>

                <li class="sidebar-item">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                                class="sidebar-link w-100 text-start border-0 bg-transparent d-flex align-items-center gap-2 px-3 py-2">
                            <iconify-icon icon="solar:logout-2-line-duotone"></iconify-icon>
                            <span class="hide-menu">Logout</span>
                        </button>
                    </form>
                </li>

            </ul>
        </nav>

    </div>
</aside>
