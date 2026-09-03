<aside class="left-sidebar with-vertical">
    <!-- ---------------------------------- -->
    <!-- Start Vertical Layout Sidebar -->
    <!-- ---------------------------------- -->

    <div>

        <div class="brand-logo mb-4 mt-3 d-flex justify-content-center align-items-center" style="height: 100px;">
            <a href="/" target="_blank" rel="noopener noreferrer">
                <img height="120" width="120" src="assets/images/logos/tabor_logo.png" alt="Logo" />
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
                        <a class="sidebar-link {{ request()->routeIs('students.*') ? 'active' : '' }}" href="#studentsMenu"
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
                                <a class="sidebar-link {{ request()->routeIs('students.course-applications') ? 'active' : '' }}"
                                    href="{{ route('students.course-applications') }}">
                                    <span class="icon-small"></span>
                                    <span class="hide-menu">Course Applications</span>
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
                            href="{{ route('lecturers.index') }}" aria-expanded="false">
                            <iconify-icon icon="solar:user-plus-rounded-line-duotone"></iconify-icon>
                            <span class="hide-menu">Lecturers</span>
                        </a>
                    </li>
                @endcan

                @can('view-courses')
                    <li class="sidebar-item">
                        <a class="sidebar-link {{ request()->routeIs('courses.index') ? 'active' : '' }}"
                            href="{{ route('courses.index') }}" aria-expanded="false">
                            <iconify-icon icon="solar:clipboard-text-line-duotone"></iconify-icon>
                            <span class="hide-menu">Courses</span>
                        </a>
                    </li>
                @endcan

                @can('manage-settings')
                    <li class="sidebar-item">
                        <a class="sidebar-link {{ request()->routeIs('settings.*') ? 'active' : '' }}" href="#settingsMenu"
                            data-bs-toggle="collapse"
                            aria-expanded="{{ request()->routeIs('settings.*') ? 'true' : 'false' }}"
                            aria-controls="settingsMenu">
                            <i class="ti ti-settings" height="24" width="24"></i>
                            <span class="hide-menu">Settings</span>
                        </a>
                        <ul class="collapse first-level {{ request()->routeIs('settings.*') ? 'show' : '' }}"
                            id="settingsMenu">
                            <li class="sidebar-item">
                                <a wire:navigate
                                    class="sidebar-link {{ request()->routeIs('settings.academic-calendar') ? 'active' : '' }}"
                                    href="{{ route('settings.academic-calendar') }}">
                                    <i class="ti ti-calendar-event"></i>
                                    <span class="hide-menu">Academic Calendar</span>
                                </a>
                            </li>

                            <li class="sidebar-item">
                                <a wire:navigate
                                    class="sidebar-link {{ request()->routeIs('settings.fee-definitions') ? 'active' : '' }}"
                                    href="{{ route('settings.fee-definitions') }}">
                                    <i class="ti ti-file-invoice"></i>
                                    <span class="hide-menu">Fee Definitions</span>
                                </a>
                            </li>

                            <li class="sidebar-item">
                                <a wire:navigate
                                    class="sidebar-link {{ request()->routeIs('settings.course-fee-plans') ? 'active' : '' }}"
                                    href="{{ route('settings.course-fee-plans') }}">
                                    <i class="ti ti-report-money"></i>
                                    <span class="hide-menu">Course Fee Plans</span>
                                </a>
                            </li>

                            <li class="sidebar-item">
                                <a wire:navigate
                                    class="sidebar-link {{ request()->routeIs('settings.student-fee-items') ? 'active' : '' }}"
                                    href="{{ route('settings.student-fee-items') }}">
                                    <i class="ti ti-file-invoice"></i>
                                    <span class="hide-menu">Student Fee Items</span>
                                </a>
                            </li>

                        </ul>
                    </li>
                @endcan

                @can('view-class-groups')
                    <li class="sidebar-item">
                        <a class="sidebar-link {{ request()->routeIs('class_groups.index') ? 'active' : '' }}"
                            href="{{ route('class_groups.index') }}" aria-expanded="false">
                            <iconify-icon icon="solar:clipboard-text-line-duotone"></iconify-icon>
                            <span class="hide-menu">Class/Year Groups</span>
                        </a>
                    </li>
                @endcan

                {{-- LMS Section --}}
                <li class="sidebar-item">
                    <a class="sidebar-link {{ request()->routeIs('admin.lms.*') || request()->routeIs('exams.index') || request()->routeIs('attendance.index') ? 'active' : '' }}"
                       href="#lmsMenu" data-bs-toggle="collapse"
                       aria-expanded="{{ request()->routeIs('admin.lms.*') || request()->routeIs('exams.index') || request()->routeIs('attendance.index') ? 'true' : 'false' }}"
                       aria-controls="lmsMenu">
                        <iconify-icon icon="solar:book-2-bold-duotone"></iconify-icon>
                        <span class="hide-menu">LMS</span>
                    </a>
                    <ul class="collapse first-level {{ request()->routeIs('admin.lms.*') || request()->routeIs('exams.index') || request()->routeIs('attendance.index') ? 'show' : '' }}"
                        id="lmsMenu">
                        <li class="sidebar-item">
                            <a class="sidebar-link {{ request()->routeIs('admin.lms.modules') ? 'active' : '' }}"
                               href="{{ route('admin.lms.modules') }}">
                                <span class="icon-small"></span>
                                <span class="hide-menu">Module Management</span>
                            </a>
                        </li>
                        <li class="sidebar-item">
                            <a class="sidebar-link {{ request()->routeIs('exams.index') ? 'active' : '' }}"
                               href="{{ route('exams.index') }}">
                                <span class="icon-small"></span>
                                <span class="hide-menu">Assessments & Exams</span>
                            </a>
                        </li>
                        <li class="sidebar-item">
                            <a class="sidebar-link {{ request()->routeIs('attendance.index') ? 'active' : '' }}"
                               href="{{ route('attendance.index') }}">
                                <span class="icon-small"></span>
                                <span class="hide-menu">Attendance</span>
                            </a>
                        </li>
                    </ul>
                </li>

                @can('view-payments')
                    <li class="sidebar-item">
                        <a class="sidebar-link {{ request()->routeIs('payments.index') ? 'active' : '' }}"
                            href="{{ route('payments.index') }}" aria-expanded="false">
                            <iconify-icon icon="solar:wallet-line-duotone"></iconify-icon>
                            <span class="hide-menu">Finance</span>
                        </a>
                    </li>
                @endcan

                @canany(['view-chart-of-accounts', 'view-journal-entries', 'view-trial-balance', 'manage-accounting-periods', 'view-vote-heads', 'view-petty-cash', 'view-budgets', 'view-suppliers', 'view-purchase-requisitions', 'view-purchase-orders', 'view-supplier-invoices'])
                    <li class="sidebar-item">
                        <a class="sidebar-link {{ request()->routeIs('accounting.*') ? 'active' : '' }}"
                            href="#accountingMenu" data-bs-toggle="collapse"
                            aria-expanded="{{ request()->routeIs('accounting.*') ? 'true' : 'false' }}"
                            aria-controls="accountingMenu">
                            <iconify-icon icon="solar:calculator-line-duotone"></iconify-icon>
                            <span class="hide-menu">Accounting</span>
                        </a>
                        <ul class="collapse first-level {{ request()->routeIs('accounting.*') ? 'show' : '' }}"
                            id="accountingMenu">
                            @can('view-chart-of-accounts')
                                <li class="sidebar-item">
                                    <a wire:navigate
                                        class="sidebar-link {{ request()->routeIs('accounting.chart-of-accounts') ? 'active' : '' }}"
                                        href="{{ route('accounting.chart-of-accounts') }}">
                                        <i class="ti ti-list-details"></i>
                                        <span class="hide-menu">Chart of Accounts</span>
                                    </a>
                                </li>
                            @endcan
                            @canany(['view-chart-of-accounts', 'manage-accounting-periods'])
                                <li class="sidebar-item">
                                    <a wire:navigate
                                        class="sidebar-link {{ request()->routeIs('accounting.financial-years') ? 'active' : '' }}"
                                        href="{{ route('accounting.financial-years') }}">
                                        <i class="ti ti-calendar-time"></i>
                                        <span class="hide-menu">Financial Years & Periods</span>
                                    </a>
                                </li>
                            @endcanany
                            @can('view-journal-entries')
                                <li class="sidebar-item">
                                    <a wire:navigate
                                        class="sidebar-link {{ request()->routeIs('accounting.journal-entries.*') ? 'active' : '' }}"
                                        href="{{ route('accounting.journal-entries.index') }}">
                                        <i class="ti ti-notebook"></i>
                                        <span class="hide-menu">Journal Entries</span>
                                    </a>
                                </li>
                            @endcan
                            @can('view-trial-balance')
                                <li class="sidebar-item">
                                    <a wire:navigate
                                        class="sidebar-link {{ request()->routeIs('accounting.trial-balance') ? 'active' : '' }}"
                                        href="{{ route('accounting.trial-balance') }}">
                                        <i class="ti ti-scale"></i>
                                        <span class="hide-menu">Trial Balance</span>
                                    </a>
                                </li>
                            @endcan
                            @can('view-vote-heads')
                                <li class="sidebar-item">
                                    <a wire:navigate
                                        class="sidebar-link {{ request()->routeIs('accounting.petty-cash.vote-heads') ? 'active' : '' }}"
                                        href="{{ route('accounting.petty-cash.vote-heads') }}">
                                        <i class="ti ti-category-2"></i>
                                        <span class="hide-menu">Vote Heads</span>
                                    </a>
                                </li>
                            @endcan
                            @can('view-petty-cash')
                                <li class="sidebar-item">
                                    <a wire:navigate
                                        class="sidebar-link {{ request()->routeIs('accounting.petty-cash.custodians') ? 'active' : '' }}"
                                        href="{{ route('accounting.petty-cash.custodians') }}">
                                        <i class="ti ti-cash"></i>
                                        <span class="hide-menu">Petty Cash Custodians</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a wire:navigate
                                        class="sidebar-link {{ request()->routeIs('accounting.petty-cash.expenses') ? 'active' : '' }}"
                                        href="{{ route('accounting.petty-cash.expenses') }}">
                                        <i class="ti ti-receipt"></i>
                                        <span class="hide-menu">Petty Cash Expenses</span>
                                    </a>
                                </li>
                            @endcan
                            @can('view-budgets')
                                <li class="sidebar-item">
                                    <a wire:navigate
                                        class="sidebar-link {{ request()->routeIs('accounting.budget.manage') ? 'active' : '' }}"
                                        href="{{ route('accounting.budget.manage') }}">
                                        <i class="ti ti-report-money"></i>
                                        <span class="hide-menu">Manage Budgets</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a wire:navigate
                                        class="sidebar-link {{ request()->routeIs('accounting.budget.report') ? 'active' : '' }}"
                                        href="{{ route('accounting.budget.report') }}">
                                        <i class="ti ti-chart-bar"></i>
                                        <span class="hide-menu">Budget vs Actual</span>
                                    </a>
                                </li>
                            @endcan
                            @can('view-suppliers')
                                <li class="sidebar-item">
                                    <a wire:navigate
                                        class="sidebar-link {{ request()->routeIs('accounting.procurement.suppliers') ? 'active' : '' }}"
                                        href="{{ route('accounting.procurement.suppliers') }}">
                                        <i class="ti ti-truck-delivery"></i>
                                        <span class="hide-menu">Suppliers</span>
                                    </a>
                                </li>
                            @endcan
                            @can('view-purchase-requisitions')
                                <li class="sidebar-item">
                                    <a wire:navigate
                                        class="sidebar-link {{ request()->routeIs('accounting.procurement.requisitions') ? 'active' : '' }}"
                                        href="{{ route('accounting.procurement.requisitions') }}">
                                        <i class="ti ti-clipboard-list"></i>
                                        <span class="hide-menu">Purchase Requisitions</span>
                                    </a>
                                </li>
                            @endcan
                            @can('view-purchase-orders')
                                <li class="sidebar-item">
                                    <a wire:navigate
                                        class="sidebar-link {{ request()->routeIs('accounting.procurement.purchase-orders') ? 'active' : '' }}"
                                        href="{{ route('accounting.procurement.purchase-orders') }}">
                                        <i class="ti ti-shopping-cart"></i>
                                        <span class="hide-menu">Purchase Orders</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a wire:navigate
                                        class="sidebar-link {{ request()->routeIs('accounting.procurement.goods-received') ? 'active' : '' }}"
                                        href="{{ route('accounting.procurement.goods-received') }}">
                                        <i class="ti ti-package"></i>
                                        <span class="hide-menu">Goods Received</span>
                                    </a>
                                </li>
                            @endcan
                            @can('view-supplier-invoices')
                                <li class="sidebar-item">
                                    <a wire:navigate
                                        class="sidebar-link {{ request()->routeIs('accounting.procurement.supplier-invoices') ? 'active' : '' }}"
                                        href="{{ route('accounting.procurement.supplier-invoices') }}">
                                        <i class="ti ti-file-invoice"></i>
                                        <span class="hide-menu">Supplier Invoices</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a wire:navigate
                                        class="sidebar-link {{ request()->routeIs('accounting.procurement.supplier-payments') ? 'active' : '' }}"
                                        href="{{ route('accounting.procurement.supplier-payments') }}">
                                        <i class="ti ti-cash-banknote"></i>
                                        <span class="hide-menu">Supplier Payments</span>
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                @endcanany

                @can('view-reports')
                    <li class="sidebar-item">
                        <a class="sidebar-link {{ request()->routeIs('reports.index') ? 'active' : '' }}"
                            href="{{ route('reports.index') }}" aria-expanded="false">
                            <iconify-icon icon="solar:book-line-duotone"></iconify-icon>
                            <span class="hide-menu">Reports</span>
                        </a>
                    </li>
                @endcan

                <li class="sidebar-item">
                    <a class="sidebar-link {{ request()->routeIs('roles.*') ? 'active' : '' }}" href="#settingsMenu"
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
                    <a class="sidebar-link {{ request()->routeIs('admin.blog.*') ? 'active' : '' }}"
                        href="#blogsMenu" data-bs-toggle="collapse"
                        aria-expanded="{{ request()->routeIs('blogs.*') ? 'true' : 'false' }}"
                        aria-controls="blogsMenu">
                        <iconify-icon icon="ph:student-light"></iconify-icon>
                        <span class="hide-menu">Blogs</span>
                    </a>
                    <ul class="collapse first-level {{ request()->routeIs('admin.blog.*') ? 'show' : '' }}"
                        id="blogsMenu">
                        <li class="sidebar-item">
                            <a class="sidebar-link {{ request()->routeIs('admin.blog.posts') ? 'active' : '' }}"
                                href="{{ route('admin.blog.posts') }}">
                                <span class="icon-small"></span>
                                <span class="hide-menu">All Blogs</span>
                            </a>
                        </li>
                        <li class="sidebar-item">
                            <a class="sidebar-link {{ request()->routeIs('admin.blog.categories') ? 'active' : '' }}"
                                href="{{ route('admin.blog.categories') }}">
                                <span class="icon-small"></span>
                                <span class="hide-menu">Blog Categories</span>
                            </a>
                        </li>
                    </ul>
                </li>

                <li class="sidebar-item">
                    <a class="sidebar-link {{ request()->routeIs('team.index') ? 'active' : '' }}"
                        href="{{ route('team.index') }}" aria-expanded="false">
                        <iconify-icon icon="mdi:account-group-outline" class="fs-5 me-2"></iconify-icon>
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
        document.addEventListener('DOMContentLoaded', function() {
            const dropdownToggles = document.querySelectorAll('.sidebar-link[data-bs-toggle="collapse"]');

            dropdownToggles.forEach(toggle => {
                toggle.addEventListener('click', function(event) {
                    document.querySelectorAll('.sidebar-link').forEach(link => {
                        link.classList.remove('active');
                    });
                    this.classList.add('active');
                });
            });
        });
    </script>
@endpush
