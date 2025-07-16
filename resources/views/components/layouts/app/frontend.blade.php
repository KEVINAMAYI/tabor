<!DOCTYPE html>
<html lang="en" dir="ltr" data-bs-theme="light" data-color-theme="Blue_Theme" data-layout="vertical">

<head>
    @include('partials.head')
    @stack('styles')
</head>

<body>

<!-- ------------------------------------- -->
<div class="topbar-image bg-dark py-8 rounded-0 mb-0 alert alert-dismissible fade show" role="alert">
    <div class="d-flex justify-content-center gap-sm-3 gap-2 align-items-center text-center flex-md-nowrap flex-wrap">
        <span class="badge bg-white bg-opacity-10 fs-2 fw-bolder px-2">New</span>
        <p class="mb-0 text-white fw-bold">Frontend Pages Included!</p>
    </div>
    <button type="button" class="btn-close btn-close-white p-3 fs-2" data-bs-dismiss="alert" aria-label="Close"></button>
</div>

<!-- ------------------------------------- -->
<!-- Top Bar End -->
<!-- ------------------------------------- -->

<!-- -------------------------------------------- -->
<!-- Header start -->
<!-- -------------------------------------------- -->
<header class="header-fp p-0 w-100 bg-light-gray">
    <nav class="navbar navbar-expand-lg py-10">
        <div class="container-fluid d-flex justify-content-between">
            <a href="{{ route('front-end.home') }}" class="text-nowrap logo-img">
                <img height="120" width="120"  src="../assets/images/logos/tabor_logo_transparent.png" alt="Logo" />
            </a>
            <button class="navbar-toggler border-0 p-0 shadow-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasRight" aria-controls="offcanvasRight">
                <i class="ti ti-menu-2 fs-8"></i>
            </button>
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-nav mx-auto mb-2 gap-xl-7 gap-8 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link fs-4 fw-bold text-dark link-primary {{ request()->routeIs('front-end.home') ? 'active' : '' }}"
                           href="{{ route('front-end.home') }}">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fs-4 fw-bold text-dark link-primary {{ request()->routeIs('front-end.about') ? 'active' : '' }}"
                           href="{{ route('front-end.about') }}">About Us</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fs-4 fw-bold text-dark link-primary {{ request()->routeIs('front-end.courses') ? 'active' : '' }}"
                           href="{{ route('front-end.courses') }}">Courses</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fs-4 fw-bold text-dark link-primary {{ request()->routeIs('front-end.course-application') ? 'active' : '' }}"
                           href="{{ route('front-end.course-application') }}">Apply</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fs-4 fw-bold text-dark link-primary {{ request()->routeIs('front-end.news') ? 'active' : '' }}"
                           href="{{ route('front-end.news') }}">News</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fs-4 fw-bold text-dark link-primary {{ request()->routeIs('front-end.contact') ? 'active' : '' }}"
                           href="{{ route('front-end.contact') }}">Contact</a>
                    </li>
                </ul>
                <a href="{{ route('login') }}" class="btn btn-dark btn-sm py-2 px-9">Log In</a>
                <a href="{{ route('front-end.course-application') }}" class="btn mx-2 btn-outline-primary btn-sm py-2 px-9">Enroll Now</a>
            </div>
        </div>
    </nav>
</header>
<!-- -------------------------------------------- -->
<!-- Header End -->
<!-- -------------------------------------------- -->

<!-- ------------------------------------- -->
<!-- Responsive Header Start -->
<!-- ------------------------------------- -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasRight" aria-labelledby="offcanvasRightLabel">
    <div class="offcanvas-header">
        <a href="../main/frontend-landingpage.html" class="text-nowrap logo-img">
            <img src="../assets/images/logos/logo.svg" alt="Logo" />
        </a>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <ul class="list-unstyled ps-0">
            <li class="mb-1">
                <a href="{{ route('front-end.home') }}"
                   class="px-0 fs-4 d-block w-100 py-2 text-dark link-primary {{ request()->routeIs('front-end.home') ? 'active' : '' }}">
                    Home
                </a>
            </li>
            <li class="mb-1">
                <a href="{{ route('front-end.about') }}"
                   class="px-0 fs-4 d-block text-dark link-primary w-100 py-2 {{ request()->routeIs('front-end.about') ? 'active' : '' }}">
                    About Us
                </a>
            </li>

            <li class="mb-1">
                <a href="{{ route('front-end.courses') }}"
                   class="px-0 fs-4 d-block w-100 py-2 text-dark link-primary {{ request()->routeIs('front-end.courses') ? 'active' : '' }}">
                    Courses
                </a>
            </li>

            <li class="mb-1">
                <a href="{{ route('front-end.news') }}"
                   class="px-0 fs-4 d-flex align-items-center justify-content-start gap-2 w-100 py-2 text-dark link-primary {{ request()->routeIs('front-end.news') ? 'active' : '' }}">
                    News
                    <span class="badge text-primary bg-primary-subtle fs-2 fw-bolder hstack">New</span>
                </a>
            </li>

            <li class="mb-1">
                <a href="{{ route('front-end.course-application') }}"
                   class="px-0 fs-4 d-block w-100 py-2 text-dark link-primary {{ request()->routeIs('front-end.course-application') ? 'active' : '' }}">
                    Apply
                </a>
            </li>

            <li class="mb-1">
                <a href="{{ route('front-end.contact') }}"
                   class="px-0 fs-4 d-block w-100 py-2 text-dark link-primary {{ request()->routeIs('front-end.contact') ? 'active' : '' }}">
                    Contact
                </a>
            </li>

            <li class="mt-3">
                <a href="{{ route('login') }}" class="btn btn-primary w-100">Log In</a>
            </li>
        </ul>
    </div>
</div>
<!-- ------------------------------------- -->
<!-- Responsive Header End -->
<!-- ------------------------------------- -->

{{ $slot }}

<!-- ------------------------------------- -->
<!-- Footer Start -->
<!-- ------------------------------------- -->
<footer class="bg-dark">
    <div class="container-fluid">
        <div class="row py-7 py-md-14 py-lg-11">
            <div class="col-md-3 col-6 mb-7 mb-md-0">
                <img src="../assets/images/logos/white-logo.svg" alt="white logo">

                <ul class="d-flex flex-column gap-9 mt-7 mb-0">
                    <li>
                        <a href="../main/app-kanban.html" class="fs-4 text-light link-primary">Kanban</a>
                    </li>
                    <li>
                        <a href="../main/app-invoice.html" class="fs-4 text-light link-primary">Invoice
                            List</a>
                    </li>
                    <li>
                        <a href="../main/eco-shop.html" class="fs-4 text-light link-primary">eCommerce</a>
                    </li>
                    <li>
                        <a href="../main/app-chat.html" class="fs-4 text-light link-primary">Chat</a>
                    </li>
                    <li>
                        <a href="../main/app-calendar.html" class="fs-4 text-light link-primary">Calendar</a>
                    </li>
                    <li>
                        <a href="../main/blog-posts.html" class="fs-4 text-light link-primary">Blog</a>
                    </li>
                </ul>
            </div>
            <div class="col-md-3 col-6 mb-7 mb-md-0">
                <h3 class="fs-4 text-white fw-semibold mb-7">Forms</h3>
                <ul class="d-flex flex-column gap-9 mb-0">
                    <li>
                        <a href="../main/form-basic.html" class="fs-4 text-light link-primary">Form
                            Basic</a>
                    </li>
                    <li>
                        <a href="../main/form-horizontal.html" class="fs-4 text-light link-primary">Form
                            Horizontal</a>
                    </li>
                    <li>
                        <a href="../main/form-wizard.html" class="fs-4 text-light link-primary">Form
                            Wizard</a>
                    </li>
                    <li>
                        <a href="../main/form-bootstrap-validation.html" class="fs-4 text-light link-primary">Form Validation
                        </a>
                    </li>
                    <li>
                        <a href="../main/form-editor-quill.html" class="fs-4 text-light link-primary">Quill
                            Editor</a>
                    </li>
                </ul>
            </div>
            <div class="col-md-3 col-6 mb-7 mb-md-0">
                <h3 class="fs-4 text-white fw-semibold mb-7">Tables</h3>
                <ul class="d-flex flex-column gap-9 mb-0">
                    <li>
                        <a href="../main/table-basic.html" class="fs-4 text-light link-primary">Basic
                            Table</a>
                    </li>
                    <li>
                        <a href="../main/table-dark-basic.html" class="fs-4 text-light link-primary">Table
                            Dark Basic</a>
                    </li>
                    <li>
                        <a href="../main/table-sizing.html" class="fs-4 text-light link-primary">Table
                            Sizing</a>
                    </li>
                    <li>
                        <a href="../main/table-layout-coloured.html" class="fs-4 text-light link-primary">Coloured Table</a>
                    </li>
                    <li>
                        <a href="../main/table-datatable-basic.html" class="fs-4 text-light link-primary">Basic Initialisation</a>
                    </li>
                    <li>
                        <a href="../main/table-datatable-api.html" class="fs-4 text-light link-primary">API</a>
                    </li>
                </ul>
            </div>
            <div class="col-md-3 col-6 mb-7 mb-md-0">
                <h3 class="fs-4 text-white fw-semibold mb-7">Follow us</h3>
                <div class="d-flex gap-9">
                    <a href="javascript:void(0)" data-bs-toggle="tooltip" data-bs-title="Facebook">
                        <img src="../assets/images/frontend-pages/icon-facebook.svg" alt="facebook">
                    </a>
                    <a href="javascript:void(0)" data-bs-toggle="tooltip" data-bs-title="Twitter">
                        <img src="../assets/images/frontend-pages/icon-twitter.svg" alt="twitter">
                    </a>
                    <a href="javascript:void(0)" data-bs-toggle="tooltip" data-bs-title="Instagram">
                        <img src="../assets/images/frontend-pages/icon-instagram.svg" alt="instagram">
                    </a>
                </div>
            </div>
        </div>
        <div class="d-flex justify-content-between flex-md-nowrap flex-wrap py-13 border-top border-dark-subtle">
            <div class="d-flex gap-3">
                <img src="../assets/images/logos/logo-icon-white.svg" alt="logo">
                <p class="text-white opacity-50 mb-0">All rights reserved by MatDash. </p>
            </div>
            <div>
                <p class="text-white mb-0">
                    <span class="opacity-50">Produced by</span>
                    <a href="https://adminmart.com/" class="text-white link-primary">AdminMart</a>.
                </p>
            </div>
        </div>
    </div>
</footer>
<!-- ------------------------------------- -->
<!-- Footer End -->
<!-- ------------------------------------- -->

<!-- Scroll Top -->
<a href="javascript:void(0)" class="top-btn btn btn-primary d-flex align-items-center justify-content-center round-54 p-0 rounded-circle">
    <i class="ti ti-arrow-up fs-7"></i>
</a>


<!-- Import Js Files -->
@include('partials.footer')

<script src="../assets/libs/owl.carousel/dist/owl.carousel.min.js"></script>
<script src="../assets/js/frontend-landingpage/homepage.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


@stack('scripts')

</body>

</html>
