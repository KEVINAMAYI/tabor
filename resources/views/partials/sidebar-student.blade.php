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
                        <iconify-icon icon="solar:widget-add-line-duotone"></iconify-icon>
                        <span class="hide-menu">Dashboard</span>
                    </a>
                </li>

                <li class="sidebar-item {{ request()->routeIs('student.enrollments.*') ? 'active' : '' }}">
                    <a class="sidebar-link {{ request()->routeIs('student.enrollments.*') ? 'active' : '' }}"
                       href="{{ route('student.enrollments.index') }}">
                        <iconify-icon icon="mdi:school"></iconify-icon>
                        <span class="hide-menu">My Enrollments</span>
                    </a>
                </li>

                <li class="sidebar-item {{ request()->routeIs('student.assignments.*') ? 'active' : '' }}">
                    <a class="sidebar-link {{ request()->routeIs('student.assignments.*') ? 'active' : '' }}"
                       href="{{ route('student.assignments.index') }}">
                        <iconify-icon icon="mdi:file-document-edit"></iconify-icon>
                        <span class="hide-menu">My Assignments</span>
                    </a>
                </li>


                <li class="sidebar-item {{ request()->routeIs('student.payments.*') ? 'active' : '' }}">
                    <a class="sidebar-link {{ request()->routeIs('student.payments.index') ? 'active' : '' }}"
                       href="{{ route('student.payments.index') }}">
                        <iconify-icon icon="solar:wallet-line-duotone"></iconify-icon>
                        <span class="hide-menu">My Payments</span>
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
