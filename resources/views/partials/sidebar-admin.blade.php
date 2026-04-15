<aside class="left-sidebar with-vertical">
    <!-- ---------------------------------- -->
    <!-- Start Vertical Layout Sidebar -->
    <!-- ---------------------------------- -->

    <div>

        <div class="brand-logo mb-4 mt-3 d-flex justify-content-center align-items-center" style="height: 100px;">
            <a href="/" target="_blank" rel="noopener noreferrer">
                <img height="120" width="120" src="assets/images/logos/tabor_logo.png" alt="Logo"/>
            </a>
        </div>


        <!-- ---------------------------------- -->
        <!-- Dashboard -->
        <!-- ---------------------------------- -->
        <nav class="sidebar-nav scroll-sidebar" data-simplebar>
            <ul class="sidebar-menu" id="sidebarnav">

                <li class="sidebar-item">
                    <a class="sidebar-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"
                       href="{{ route('admin.dashboard') }}" aria-expanded="false">
                        <iconify-icon icon="solar:widget-add-line-duotone"></iconify-icon>
                        <span class="hide-menu">Dashboard</span>
                    </a>
                </li>

                @can('view-students')
                    <li class="sidebar-item">
                        <a class="sidebar-link {{ request()->routeIs('students.*') ? 'active' : '' }}"
                           href="#studentsMenu"
                           data-bs-toggle="collapse"
                           aria-expanded="{{ request()->routeIs('students.*') ? 'true' : 'false' }}"
                           aria-controls="studentsMenu">
                            <iconify-icon icon="ph:student-light"></iconify-icon>
                            <span class="hide-menu">Students</span>
                        </a>
                        <ul class="collapse first-level {{ request()->routeIs('students.*') ? 'show' : '' }}"
                            id="studentsMenu">
                            <li class="sidebar-item">
                                <a class="sidebar-link {{ request()->routeIs('students.index') ? 'active' : '' }}"
                                   href="{{ route('students.index') }}">
                                    <span class="icon-small"></span>
                                    <span class="hide-menu">All Students</span>
                                </a>
                            </li>
                            <li class="sidebar-item">
                                <a class="sidebar-link {{ request()->routeIs('students.pending') ? 'active' : '' }}"
                                   href="{{ route('students.pending') }}">
                                    <span class="icon-small"></span>
                                    <span class="hide-menu">Pending Enrollments</span>
                                </a>
                            </li>
                            <li class="sidebar-item">
                                <a class="sidebar-link {{ request()->routeIs('students.enrollments') ? 'active' : '' }}"
                                   href="{{ route('students.enrollments') }}">
                                    <span class="icon-small"></span>
                                    <span class="hide-menu">Active Enrollments</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                @endcan

                @can('view-lecturers')
                    <li class="sidebar-item">
                        <a class="sidebar-link {{ request()->routeIs('lecturers.index') ? 'active' : '' }}"
                           href="{{ route('lecturers.index') }}"
                           aria-expanded="false">
                            <iconify-icon icon="solar:user-plus-rounded-line-duotone"></iconify-icon>
                            <span class="hide-menu">Lecturers</span>
                        </a>
                    </li>
                @endcan

                @can('view-courses')
                    <li class="sidebar-item">
                        <a class="sidebar-link {{ request()->routeIs('courses.index') ? 'active' : '' }}"
                           href="{{ route('courses.index') }}"
                           aria-expanded="false">
                            <iconify-icon icon="solar:clipboard-text-line-duotone"></iconify-icon>
                            <span class="hide-menu">Courses</span>
                        </a>
                    </li>
                @endcan

                @can('view-intakes')
                    <li class="sidebar-item">
                        <a class="sidebar-link {{ request()->routeIs('intakes.index') ? 'active' : '' }}"
                           href="#"
                           aria-expanded="false">
                            <iconify-icon icon="solar:download-line-duotone" width="24" height="24"></iconify-icon>
                            <span class="hide-menu">Academic Calendar</span>
                        </a>
                    </li>
                @endcan

                @can('view-class-groups')
                    <li class="sidebar-item">
                        <a class="sidebar-link {{ request()->routeIs('class_groups.index') ? 'active' : '' }}"
                           href="{{ route('class_groups.index') }}"
                           aria-expanded="false">
                            <iconify-icon icon="solar:clipboard-text-line-duotone"></iconify-icon>
                            <span class="hide-menu">Class/Year Groups</span>
                        </a>
                    </li>
                @endcan

                {{--                @can('view-attendance')--}}
                {{--                    <li class="sidebar-item">--}}
                {{--                        <a class="sidebar-link {{ request()->routeIs('attendance.index') ? 'active' : '' }}"--}}
                {{--                           href="{{ route('attendance.index') }}"--}}
                {{--                           aria-expanded="false">--}}
                {{--                            <iconify-icon icon="solar:repeat-one-minimalistic-bold-duotone"></iconify-icon>--}}
                {{--                            <span class="hide-menu">Attendance Tracking</span>--}}
                {{--                        </a>--}}
                {{--                    </li>--}}
                {{--                @endcan--}}

                @can('view-payments')
                    <li class="sidebar-item">
                        <a class="sidebar-link {{ request()->routeIs('payments.index') ? 'active' : '' }}"
                           href="{{ route('payments.index') }}"
                           aria-expanded="false">
                            <iconify-icon icon="solar:wallet-line-duotone"></iconify-icon>
                            <span class="hide-menu">Finance</span>
                        </a>
                    </li>
                @endcan

                @can('manage-reports')
                    <li class="sidebar-item">
                        <a class="sidebar-link {{ request()->routeIs('reports.index') ? 'active' : '' }}"
                           href="{{ route('reports.index') }}"
                           aria-expanded="false">
                            <iconify-icon icon="solar:book-line-duotone"></iconify-icon>
                            <span class="hide-menu">Reports</span>
                        </a>
                    </li>
                @endcan

                <li class="sidebar-item">
                    <a class="sidebar-link {{ request()->routeIs('roles.*') ? 'active' : '' }}"
                       href="#settingsMenu"
                       data-bs-toggle="collapse"
                       aria-expanded="{{ request()->routeIs('roles.*') ? 'true' : 'false' }}"
                       aria-controls="settingsMenu">
                        <iconify-icon icon="tabler:shield"></iconify-icon>
                        <span class="hide-menu">Access & Security</span>
                    </a>
                    <ul class="collapse first-level {{ request()->routeIs('roles.*') ? 'show' : '' }}"
                        id="settingsMenu">
                        @can('view-users')
                            <li class="sidebar-item">
                                <a class="sidebar-link {{ request()->routeIs('roles.users') ? 'active' : '' }}"
                                   href="{{ route('roles.users') }}">
                                    <span class="icon-small"></span>
                                    <span class="hide-menu">User Management</span>
                                </a>
                            </li>
                        @endcan
                        @can('view-roles')
                            <li class="sidebar-item">
                                <a class="sidebar-link {{ request()->routeIs('roles.index') ? 'active' : '' }}"
                                   href="{{ route('roles.index') }}">
                                    <span class="icon-small"></span>
                                    <span class="hide-menu">Roles & Permissions</span>
                                </a>
                            </li>
                        @endcan

                    </ul>
                </li>

                <li class="sidebar-item">
                    <a class="sidebar-link {{ request()->routeIs('team.index') ? 'active' : '' }}"
                       href="{{ route('team.index') }}"
                       aria-expanded="false">
                        <iconify-icon icon="mdi:account-group-outline"
                                      class="fs-5 me-2"></iconify-icon>
                        <span class="hide-menu">Team</span>
                    </a>
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

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const dropdownToggles = document.querySelectorAll('.sidebar-link[data-bs-toggle="collapse"]');

            dropdownToggles.forEach(toggle => {
                toggle.addEventListener('click', function (event) {
                    document.querySelectorAll('.sidebar-link').forEach(link => {
                        link.classList.remove('active');
                    });
                    this.classList.add('active');
                });
            });
        });
    </script>
@endpush
