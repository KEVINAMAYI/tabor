<aside class="left-sidebar with-vertical">
    <div>
        <div class="brand-logo mb-3 mt-3 d-flex justify-content-center align-items-center" style="height: 100px;">
            <img height="120" width="120" src="{{ asset('assets/images/logos/tabor_logo.png') }}" alt="Logo"/>
        </div>

        <nav class="sidebar-nav scroll-sidebar" data-simplebar>
            <ul class="sidebar-menu" id="sidebarnav">

                <li class="nav-small-cap">
                    <iconify-icon icon="solar:menu-dots-bold" class="nav-small-cap-icon fs-4"></iconify-icon>
                    <span class="hide-menu">LMS</span>
                </li>

                <li class="sidebar-item {{ request()->routeIs('lecturer.dashboard') ? 'active' : '' }}">
                    <a class="sidebar-link" href="{{ route('lecturer.dashboard') }}">
                        <iconify-icon icon="solar:widget-add-bold-duotone"></iconify-icon>
                        <span class="hide-menu">Dashboard</span>
                    </a>
                </li>

                <li class="sidebar-item {{ request()->routeIs('lecturer.module.view') ? 'active' : '' }}">
                    <a class="sidebar-link" href="{{ route('lecturer.dashboard') }}">
                        <iconify-icon icon="solar:book-2-bold-duotone"></iconify-icon>
                        <span class="hide-menu">My Modules</span>
                    </a>
                </li>

                <li class="nav-small-cap mt-3">
                    <iconify-icon icon="solar:menu-dots-bold" class="nav-small-cap-icon fs-4"></iconify-icon>
                    <span class="hide-menu">Account</span>
                </li>

                <li class="sidebar-item">
                    <a class="sidebar-link" href="{{ route('user.settings') }}">
                        <iconify-icon icon="solar:settings-bold-duotone"></iconify-icon>
                        <span class="hide-menu">Profile</span>
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
