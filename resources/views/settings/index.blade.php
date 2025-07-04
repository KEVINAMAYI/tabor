<!DOCTYPE html>
<html lang="en" dir="ltr" data-bs-theme="light" data-color-theme="Blue_Theme" data-layout="vertical">

<head>

    <base href="{{ URL::to('/') }}">

    <!-- Required meta tags -->
    <meta charset="UTF-8"/>
    <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>

    <!-- Favicon icon-->
    <link rel="shortcut icon" type="image/png" href="assets/images/logos/favicon.png"/>

    <!-- Core Css -->
    <link rel="stylesheet" href="assets/css/styles.css"/>

    <title>MatDash Bootstrap Admin</title>
</head>

<body class="link-sidebar">
<!-- Preloader -->
<div class="preloader">
    <img src="assets/images/logos/favicon.png" alt="loader" class="lds-ripple img-fluid"/>
</div>
<div id="main-wrapper">
    <!-- Sidebar Start -->
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

                    <li class="sidebar-item">
                        <a class="sidebar-link has-arrow" href="javascript:void(0)" aria-expanded="false">
                            <iconify-icon icon="solar:shield-user-line-duotone"></iconify-icon>
                            <span class="hide-menu">Students</span>
                        </a>
                        <ul aria-expanded="false" class="collapse first-level">
                            <li class="sidebar-item">
                                <a class="sidebar-link" href="default-sidebar/frontend-landingpage.html">
                                    <span class="icon-small"></span>
                                    <span class="hide-menu">Add Student</span>
                                </a>
                            </li>
                            <li class="sidebar-item">
                                <a class="sidebar-link" href="default-sidebar/frontend-aboutpage.html">
                                    <span class="icon-small"></span>
                                    <span class="hide-menu">List Students</span>
                                </a>
                            </li>
                        </ul>
                    </li>

                    <li class="sidebar-item">
                        <a class="sidebar-link has-arrow" href="javascript:void(0)" aria-expanded="false">
                            <iconify-icon icon="solar:user-plus-rounded-line-duotone"></iconify-icon>
                            <span class="hide-menu">Lecturers</span>
                        </a>
                        <ul aria-expanded="false" class="collapse first-level">
                            <li class="sidebar-item">
                                <a class="sidebar-link" href="default-sidebar/frontend-landingpage.html">
                                    <span class="icon-small"></span>
                                    <span class="hide-menu">Add Lecturers</span>
                                </a>
                            </li>
                            <li class="sidebar-item">
                                <a class="sidebar-link" href="default-sidebar/frontend-aboutpage.html">
                                    <span class="icon-small"></span>
                                    <span class="hide-menu">List Lecturers</span>
                                </a>
                            </li>
                        </ul>
                    </li>

                    <li class="sidebar-item">
                        <a class="sidebar-link has-arrow" href="javascript:void(0)" aria-expanded="false">
                            <iconify-icon icon="solar:clipboard-text-line-duotone"></iconify-icon>
                            <span class="hide-menu">Classes/Year</span>
                        </a>
                        <ul aria-expanded="false" class="collapse first-level">
                            <li class="sidebar-item">
                                <a class="sidebar-link" href="default-sidebar/frontend-landingpage.html">
                                    <span class="icon-small"></span>
                                    <span class="hide-menu">Add Class/Year</span>
                                </a>
                            </li>
                            <li class="sidebar-item">
                                <a class="sidebar-link" href="default-sidebar/frontend-aboutpage.html">
                                    <span class="icon-small"></span>
                                    <span class="hide-menu">List Classes/Year</span>
                                </a>
                            </li>
                        </ul>
                    </li>

                    <li class="sidebar-item">
                        <a class="sidebar-link has-arrow" href="javascript:void(0)" aria-expanded="false">
                            <iconify-icon icon="solar:repeat-one-minimalistic-bold-duotone"></iconify-icon>
                            <span class="hide-menu">Attendance Tracking</span>
                        </a>
                        <ul aria-expanded="false" class="collapse first-level">
                            <li class="sidebar-item">
                                <a class="sidebar-link" href="default-sidebar/frontend-aboutpage.html">
                                    <span class="icon-small"></span>
                                    <span class="hide-menu">View Attendance</span>
                                </a>
                            </li>
                        </ul>
                    </li>

                    <li class="sidebar-item">
                        <a class="sidebar-link has-arrow" href="javascript:void(0)" aria-expanded="false">
                            <iconify-icon icon="solar:wallet-line-duotone"></iconify-icon>
                            <span class="hide-menu">Financials</span>
                        </a>
                        <ul aria-expanded="false" class="collapse first-level">
                            <li class="sidebar-item">
                                <a class="sidebar-link" href="default-sidebar/frontend-landingpage.html">
                                    <span class="icon-small"></span>
                                    <span class="hide-menu">Record Payment</span>
                                </a>
                            </li>
                            <li class="sidebar-item">
                                <a class="sidebar-link" href="default-sidebar/frontend-aboutpage.html">
                                    <span class="icon-small"></span>
                                    <span class="hide-menu">View Payment</span>
                                </a>
                            </li>
                        </ul>
                    </li>

                    <!-- Additional Recommended Sections -->

                    <li class="sidebar-item">
                        <a class="sidebar-link has-arrow" href="javascript:void(0)" aria-expanded="false">
                            <iconify-icon icon="solar:book-line-duotone"></iconify-icon>
                            <span class="hide-menu">Library</span>
                        </a>
                        <ul aria-expanded="false" class="collapse first-level">
                            <li class="sidebar-item">
                                <a class="sidebar-link" href="#">
                                    <span class="icon-small"></span>
                                    <span class="hide-menu">Add Book</span>
                                </a>
                            </li>
                            <li class="sidebar-item">
                                <a class="sidebar-link" href="#">
                                    <span class="icon-small"></span>
                                    <span class="hide-menu">Issue Book</span>
                                </a>
                            </li>
                        </ul>
                    </li>

                    <li class="sidebar-item">
                        <a class="sidebar-link has-arrow" href="javascript:void(0)" aria-expanded="false">
                            <iconify-icon icon="solar:document-text-line-duotone"></iconify-icon>
                            <span class="hide-menu">Exams</span>
                        </a>
                        <ul aria-expanded="false" class="collapse first-level">
                            <li class="sidebar-item">
                                <a class="sidebar-link" href="#">
                                    <span class="icon-small"></span>
                                    <span class="hide-menu">Create Exam</span>
                                </a>
                            </li>
                            <li class="sidebar-item">
                                <a class="sidebar-link" href="#">
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


                </ul>
            </nav>

        </div>
    </aside>
    <!--  Sidebar End -->
    <div class="page-wrapper">
        <!--  Header Start -->
        <header class="topbar">
            <div class="with-vertical">
                <!-- ---------------------------------- -->
                <!-- Start Vertical Layout Header -->
                <!-- ---------------------------------- -->
                <nav class="navbar navbar-expand-lg p-0">
                    <ul class="navbar-nav">
                        <li class="nav-item nav-icon-hover-bg rounded-circle d-flex">
                            <a class="nav-link  sidebartoggler" id="headerCollapse" href="javascript:void(0)">
                                <iconify-icon icon="solar:hamburger-menu-line-duotone" class="fs-6"></iconify-icon>
                            </a>
                        </li>
                        <li class="nav-item d-none d-xl-flex nav-icon-hover-bg rounded-circle">
                            <a class="nav-link" href="javascript:void(0)" data-bs-toggle="modal"
                               data-bs-target="#exampleModal">
                                <iconify-icon icon="solar:magnifer-linear" class="fs-6"></iconify-icon>
                            </a>
                        </li>
                        <li class="nav-item d-none d-lg-flex dropdown nav-icon-hover-bg rounded-circle">
                            <div class="hover-dd">
                                <a class="nav-link" id="drop2" href="#" aria-haspopup="true" aria-expanded="false">
                                    <iconify-icon icon="solar:widget-3-line-duotone" class="fs-6"></iconify-icon>
                                </a>
                                <div
                                    class="dropdown-menu dropdown-menu-nav dropdown-menu-animate-up py-0 overflow-hidden"
                                    aria-labelledby="drop2">
                                    <div class="position-relative">
                                        <div class="row">
                                            <div class="col-8">
                                                <div class="p-4 pb-3">

                                                    <div class="row">
                                                        <div class="col-6">
                                                            <div class="position-relative">
                                                                <a href="default-sidebar/app-chat.html"
                                                                   class="d-flex align-items-center pb-9 position-relative">
                                                                    <div
                                                                        class="bg-primary-subtle rounded round-48 me-3 d-flex align-items-center justify-content-center">
                                                                        <iconify-icon
                                                                            icon="solar:chat-line-bold-duotone"
                                                                            class="fs-7 text-primary"></iconify-icon>
                                                                    </div>
                                                                    <div class="d-inline-block">
                                                                        <h6 class="mb-0">Chat Application</h6>
                                                                        <span class="fs-11 d-block text-body-color">New messages arrived</span>
                                                                    </div>
                                                                </a>
                                                                <a href="default-sidebar/app-invoice.html"
                                                                   class="d-flex align-items-center pb-9 position-relative">
                                                                    <div
                                                                        class="bg-secondary-subtle rounded round-48 me-3 d-flex align-items-center justify-content-center">
                                                                        <iconify-icon
                                                                            icon="solar:bill-list-bold-duotone"
                                                                            class="fs-7 text-secondary"></iconify-icon>
                                                                    </div>
                                                                    <div class="d-inline-block">
                                                                        <h6 class="mb-0">Invoice App</h6>
                                                                        <span class="fs-11 d-block text-body-color">Get latest invoice</span>
                                                                    </div>
                                                                </a>
                                                                <a href="default-sidebar/app-contact2.html"
                                                                   class="d-flex align-items-center pb-9 position-relative">
                                                                    <div
                                                                        class="bg-warning-subtle rounded round-48 me-3 d-flex align-items-center justify-content-center">
                                                                        <iconify-icon
                                                                            icon="solar:phone-calling-rounded-bold-duotone"
                                                                            class="fs-7 text-warning"></iconify-icon>
                                                                    </div>
                                                                    <div class="d-inline-block">
                                                                        <h6 class="mb-0">Contact Application</h6>
                                                                        <span class="fs-11 d-block text-body-color">2 Unsaved Contacts</span>
                                                                    </div>
                                                                </a>
                                                                <a href="default-sidebar/app-email.html"
                                                                   class="d-flex align-items-center pb-9 position-relative">
                                                                    <div
                                                                        class="bg-danger-subtle rounded round-48 me-3 d-flex align-items-center justify-content-center">
                                                                        <iconify-icon icon="solar:letter-bold-duotone"
                                                                                      class="fs-7 text-danger"></iconify-icon>
                                                                    </div>
                                                                    <div class="d-inline-block">
                                                                        <h6 class="mb-0">Email App</h6>
                                                                        <span class="fs-11 d-block text-body-color">Get new emails</span>
                                                                    </div>
                                                                </a>
                                                            </div>
                                                        </div>
                                                        <div class="col-6">
                                                            <div class="position-relative">
                                                                <a href="default-sidebar/page-user-profile.html"
                                                                   class="d-flex align-items-center pb-9 position-relative">
                                                                    <div
                                                                        class="bg-success-subtle rounded round-48 me-3 d-flex align-items-center justify-content-center">
                                                                        <iconify-icon icon="solar:user-bold-duotone"
                                                                                      class="fs-7 text-success"></iconify-icon>
                                                                    </div>
                                                                    <div class="d-inline-block">
                                                                        <h6 class="mb-0">User Profile</h6>
                                                                        <span class="fs-11 d-block text-body-color">learn more information</span>
                                                                    </div>
                                                                </a>
                                                                <a href="default-sidebar/app-calendar.html"
                                                                   class="d-flex align-items-center pb-9 position-relative">
                                                                    <div
                                                                        class="bg-primary-subtle rounded round-48 me-3 d-flex align-items-center justify-content-center">
                                                                        <iconify-icon
                                                                            icon="solar:calendar-minimalistic-bold-duotone"
                                                                            class="fs-7 text-primary"></iconify-icon>
                                                                    </div>
                                                                    <div class="d-inline-block">
                                                                        <h6 class="mb-0">Calendar App</h6>
                                                                        <span class="fs-11 d-block text-body-color">Get dates</span>
                                                                    </div>
                                                                </a>
                                                                <a href="default-sidebar/app-contact.html"
                                                                   class="d-flex align-items-center pb-9 position-relative">
                                                                    <div
                                                                        class="bg-secondary-subtle rounded round-48 me-3 d-flex align-items-center justify-content-center">
                                                                        <iconify-icon
                                                                            icon="solar:smartphone-2-bold-duotone"
                                                                            class="fs-7 text-secondary"></iconify-icon>
                                                                    </div>
                                                                    <div class="d-inline-block">
                                                                        <h6 class="mb-0">Contact List Table</h6>
                                                                        <span class="fs-11 d-block text-body-color">Add new contact</span>
                                                                    </div>
                                                                </a>
                                                                <a href="default-sidebar/app-notes.html"
                                                                   class="d-flex align-items-center pb-9 position-relative">
                                                                    <div
                                                                        class="bg-warning-subtle rounded round-48 me-3 d-flex align-items-center justify-content-center">
                                                                        <iconify-icon icon="solar:notes-bold-duotone"
                                                                                      class="fs-7 text-warning"></iconify-icon>
                                                                    </div>
                                                                    <div class="d-inline-block">
                                                                        <h6 class="mb-0">Notes Application</h6>
                                                                        <span class="fs-11 d-block text-body-color">To-do and Daily tasks</span>
                                                                    </div>
                                                                </a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <img src="assets/images/backgrounds/mega-dd-bg.jpg" alt="mega-dd"
                                                     class="img-fluid mega-dd-bg"/>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </li>
                    </ul>

                    <div class="d-block d-lg-none py-9 py-xl-0">
                        <img src="assets/images/logos/logo.svg" alt="matdash-img"/>
                    </div>
                    <a class="navbar-toggler p-0 border-0 nav-icon-hover-bg rounded-circle" href="javascript:void(0)"
                       data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav"
                       aria-expanded="false" aria-label="Toggle navigation">
                        <iconify-icon icon="solar:menu-dots-bold-duotone" class="fs-6"></iconify-icon>
                    </a>
                    <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                        <div class="d-flex align-items-center justify-content-between">
                            <ul class="navbar-nav flex-row mx-auto ms-lg-auto align-items-center justify-content-center">
                                <li class="nav-item dropdown">
                                    <a href="javascript:void(0)"
                                       class="nav-link nav-icon-hover-bg rounded-circle d-flex d-lg-none align-items-center justify-content-center"
                                       type="button" data-bs-toggle="offcanvas" data-bs-target="#mobilenavbar"
                                       aria-controls="offcanvasWithBothOptions">
                                        <iconify-icon icon="solar:sort-line-duotone" class="fs-6"></iconify-icon>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link moon dark-layout nav-icon-hover-bg rounded-circle"
                                       href="javascript:void(0)">
                                        <iconify-icon icon="solar:moon-line-duotone" class="moon fs-6"></iconify-icon>
                                    </a>
                                    <a class="nav-link sun light-layout nav-icon-hover-bg rounded-circle"
                                       href="javascript:void(0)" style="display: none">
                                        <iconify-icon icon="solar:sun-2-line-duotone" class="sun fs-6"></iconify-icon>
                                    </a>
                                </li>
                                <li class="nav-item d-block d-xl-none">
                                    <a class="nav-link nav-icon-hover-bg rounded-circle" href="javascript:void(0)"
                                       data-bs-toggle="modal" data-bs-target="#exampleModal">
                                        <iconify-icon icon="solar:magnifer-line-duotone" class="fs-6"></iconify-icon>
                                    </a>
                                </li>

                                <!-- ------------------------------- -->
                                <!-- start notification Dropdown -->
                                <!-- ------------------------------- -->
                                <li class="nav-item dropdown nav-icon-hover-bg rounded-circle">
                                    <a class="nav-link position-relative" href="javascript:void(0)" id="drop2"
                                       aria-expanded="false">
                                        <iconify-icon icon="solar:bell-bing-line-duotone" class="fs-6"></iconify-icon>
                                    </a>
                                    <div class="dropdown-menu content-dd dropdown-menu-end dropdown-menu-animate-up"
                                         aria-labelledby="drop2">
                                        <div class="d-flex align-items-center justify-content-between py-3 px-7">
                                            <h5 class="mb-0 fs-5 fw-semibold">Notifications</h5>
                                            <span class="badge text-bg-primary rounded-4 px-3 py-1 lh-sm">5 new</span>
                                        </div>
                                        <div class="message-body" data-simplebar>
                                            <a href="javascript:void(0)"
                                               class="py-6 px-7 d-flex align-items-center dropdown-item gap-3">
                          <span
                              class="flex-shrink-0 bg-danger-subtle rounded-circle round d-flex align-items-center justify-content-center fs-6 text-danger">
                            <iconify-icon icon="solar:widget-3-line-duotone"></iconify-icon>
                          </span>
                                                <div class="w-75 d-inline-block ">
                                                    <div class="d-flex align-items-center justify-content-between">
                                                        <h6 class="mb-1 fw-semibold">Launch Admin</h6>
                                                        <span class="d-block fs-2">9:30 AM</span>
                                                    </div>
                                                    <span class="d-block text-truncate text-truncate fs-11">Just see the my new admin!</span>
                                                </div>
                                            </a>
                                            <a href="javascript:void(0)"
                                               class="py-6 px-7 d-flex align-items-center dropdown-item gap-3">
                          <span
                              class="flex-shrink-0 bg-primary-subtle rounded-circle round d-flex align-items-center justify-content-center fs-6 text-primary">
                            <iconify-icon icon="solar:calendar-line-duotone"></iconify-icon>
                          </span>
                                                <div class="w-75 d-inline-block ">
                                                    <div class="d-flex align-items-center justify-content-between">
                                                        <h6 class="mb-1 fw-semibold">Event today</h6>
                                                        <span class="d-block fs-2">9:15 AM</span>
                                                    </div>
                                                    <span class="d-block text-truncate text-truncate fs-11">Just a reminder that you have event</span>
                                                </div>
                                            </a>
                                            <a href="javascript:void(0)"
                                               class="py-6 px-7 d-flex align-items-center dropdown-item gap-3">
                          <span
                              class="flex-shrink-0 bg-secondary-subtle rounded-circle round d-flex align-items-center justify-content-center fs-6 text-secondary">
                            <iconify-icon icon="solar:settings-line-duotone"></iconify-icon>
                          </span>
                                                <div class="w-75 d-inline-block ">
                                                    <div class="d-flex align-items-center justify-content-between">
                                                        <h6 class="mb-1 fw-semibold">Settings</h6>
                                                        <span class="d-block fs-2">4:36 PM</span>
                                                    </div>
                                                    <span class="d-block text-truncate text-truncate fs-11">You can customize this template as you want</span>
                                                </div>
                                            </a>
                                            <a href="javascript:void(0)"
                                               class="py-6 px-7 d-flex align-items-center dropdown-item gap-3">
                          <span
                              class="flex-shrink-0 bg-warning-subtle rounded-circle round d-flex align-items-center justify-content-center fs-6 text-warning">
                            <iconify-icon icon="solar:widget-4-line-duotone"></iconify-icon>
                          </span>
                                                <div class="w-75 d-inline-block ">
                                                    <div class="d-flex align-items-center justify-content-between">
                                                        <h6 class="mb-1 fw-semibold">Launch Admin</h6>
                                                        <span class="d-block fs-2">9:30 AM</span>
                                                    </div>
                                                    <span class="d-block text-truncate text-truncate fs-11">Just see the my new admin!</span>
                                                </div>
                                            </a>
                                            <a href="javascript:void(0)"
                                               class="py-6 px-7 d-flex align-items-center dropdown-item gap-3">
                          <span
                              class="flex-shrink-0 bg-primary-subtle rounded-circle round d-flex align-items-center justify-content-center fs-6 text-primary">
                            <iconify-icon icon="solar:calendar-line-duotone"></iconify-icon>
                          </span>
                                                <div class="w-75 d-inline-block ">
                                                    <div class="d-flex align-items-center justify-content-between">
                                                        <h6 class="mb-1 fw-semibold">Event today</h6>
                                                        <span class="d-block fs-2">9:15 AM</span>
                                                    </div>
                                                    <span class="d-block text-truncate text-truncate fs-11">Just a reminder that you have event</span>
                                                </div>
                                            </a>
                                            <a href="javascript:void(0)"
                                               class="py-6 px-7 d-flex align-items-center dropdown-item gap-3">
                          <span
                              class="flex-shrink-0 bg-secondary-subtle rounded-circle round d-flex align-items-center justify-content-center fs-6 text-secondary">
                            <iconify-icon icon="solar:settings-line-duotone"></iconify-icon>
                          </span>
                                                <div class="w-75 d-inline-block ">
                                                    <div class="d-flex align-items-center justify-content-between">
                                                        <h6 class="mb-1 fw-semibold">Settings</h6>
                                                        <span class="d-block fs-2">4:36 PM</span>
                                                    </div>
                                                    <span class="d-block text-truncate text-truncate fs-11">You can customize this template as you want</span>
                                                </div>
                                            </a>
                                        </div>
                                        <div class="py-6 px-7 mb-1">
                                            <button class="btn btn-primary w-100">See All Notifications</button>
                                        </div>

                                    </div>
                                </li>
                                <!-- ------------------------------- -->
                                <!-- end notification Dropdown -->
                                <!-- ------------------------------- -->

                                <!-- ------------------------------- -->
                                <!-- start language Dropdown -->
                                <!-- ------------------------------- -->
                                <li class="nav-item dropdown nav-icon-hover-bg rounded-circle">
                                    <a class="nav-link" href="javascript:void(0)" id="drop2" data-bs-toggle="dropdown"
                                       aria-expanded="false">
                                        <img src="assets/images/flag/icon-flag-en.svg" alt="matdash-img" width="20px"
                                             height="20px" class="rounded-circle object-fit-cover round-20"/>
                                    </a>
                                    <div class="dropdown-menu dropdown-menu-end dropdown-menu-animate-up"
                                         aria-labelledby="drop2">
                                        <div class="message-body">
                                            <a href="javascript:void(0)"
                                               class="d-flex align-items-center gap-2 py-3 px-4 dropdown-item">
                                                <div class="position-relative">
                                                    <img src="assets/images/flag/icon-flag-en.svg" alt="matdash-img"
                                                         width="20px" height="20px"
                                                         class="rounded-circle object-fit-cover round-20"/>
                                                </div>
                                                <p class="mb-0 fs-3">English (UK)</p>
                                            </a>
                                            <a href="javascript:void(0)"
                                               class="d-flex align-items-center gap-2 py-3 px-4 dropdown-item">
                                                <div class="position-relative">
                                                    <img src="assets/images/flag/icon-flag-cn.svg" alt="matdash-img"
                                                         width="20px" height="20px"
                                                         class="rounded-circle object-fit-cover round-20"/>
                                                </div>
                                                <p class="mb-0 fs-3">中国人 (Chinese)</p>
                                            </a>
                                            <a href="javascript:void(0)"
                                               class="d-flex align-items-center gap-2 py-3 px-4 dropdown-item">
                                                <div class="position-relative">
                                                    <img src="assets/images/flag/icon-flag-fr.svg" alt="matdash-img"
                                                         width="20px" height="20px"
                                                         class="rounded-circle object-fit-cover round-20"/>
                                                </div>
                                                <p class="mb-0 fs-3">français (French)</p>
                                            </a>
                                            <a href="javascript:void(0)"
                                               class="d-flex align-items-center gap-2 py-3 px-4 dropdown-item">
                                                <div class="position-relative">
                                                    <img src="assets/images/flag/icon-flag-sa.svg" alt="matdash-img"
                                                         width="20px" height="20px"
                                                         class="rounded-circle object-fit-cover round-20"/>
                                                </div>
                                                <p class="mb-0 fs-3">عربي (Arabic)</p>
                                            </a>
                                        </div>
                                    </div>
                                </li>
                                <!-- ------------------------------- -->
                                <!-- end language Dropdown -->
                                <!-- ------------------------------- -->

                                <!-- ------------------------------- -->
                                <!-- start profile Dropdown -->
                                <!-- ------------------------------- -->
                                <li class="nav-item dropdown">
                                    <a class="nav-link" href="javascript:void(0)" id="drop1" aria-expanded="false">
                                        <div class="d-flex align-items-center gap-2 lh-base">
                                            <img src="assets/images/profile/user-1.jpg" class="rounded-circle"
                                                 width="35" height="35" alt="matdash-img"/>
                                            <iconify-icon icon="solar:alt-arrow-down-bold" class="fs-2"></iconify-icon>
                                        </div>
                                    </a>
                                    <div
                                        class="dropdown-menu profile-dropdown dropdown-menu-end dropdown-menu-animate-up"
                                        aria-labelledby="drop1">
                                        <div class="position-relative px-4 pt-3 pb-2">
                                            <div class="d-flex align-items-center mb-3 pb-3 border-bottom gap-6">
                                                <img src="assets/images/profile/user-1.jpg" class="rounded-circle"
                                                     width="56" height="56" alt="matdash-img"/>
                                                <div>
                                                    <h5 class="mb-0 fs-12">David McMichael <span
                                                            class="text-success fs-11">Pro</span>
                                                    </h5>
                                                    <p class="mb-0 text-dark">
                                                        david@wrappixel.com
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="message-body">
                                                <a href="default-sidebar/page-user-profile.html"
                                                   class="p-2 dropdown-item h6 rounded-1">
                                                    My Profile
                                                </a>
                                                <a href="default-sidebar/page-pricing.html"
                                                   class="p-2 dropdown-item h6 rounded-1">
                                                    My Subscription
                                                </a>
                                                <a href="default-sidebar/app-invoice.html"
                                                   class="p-2 dropdown-item h6 rounded-1">
                                                    My Invoice <span
                                                        class="badge bg-danger-subtle text-danger rounded ms-8">4</span>
                                                </a>
                                                <a href="default-sidebar/page-account-settings.html"
                                                   class="p-2 dropdown-item h6 rounded-1">
                                                    Account Settings
                                                </a>
                                                <a href="default-sidebar/authentication-login2.html"
                                                   class="p-2 dropdown-item h6 rounded-1">
                                                    Sign Out
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                                <!-- ------------------------------- -->
                                <!-- end profile Dropdown -->
                                <!-- ------------------------------- -->
                            </ul>
                        </div>
                    </div>
                </nav>
                <!-- ---------------------------------- -->
                <!-- End Vertical Layout Header -->
                <!-- ---------------------------------- -->

                <!-- ------------------------------- -->
                <!-- apps Dropdown in Small screen -->
                <!-- ------------------------------- -->
                <!--  Mobilenavbar -->
                <div class="offcanvas offcanvas-start pt-0" data-bs-scroll="true" tabindex="-1" id="mobilenavbar"
                     aria-labelledby="offcanvasWithBothOptionsLabel">
                    <nav class="sidebar-nav scroll-sidebar">
                        <div class="offcanvas-header justify-content-between">
                            <a href="default-sidebar/index.html" class="text-nowrap logo-img">
                                <img src="assets/images/logos/logo-icon.svg" alt="Logo"/>
                            </a>
                            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"
                                    aria-label="Close"></button>
                        </div>
                        <div class="offcanvas-body pt-0" data-simplebar style="height: calc(100vh - 80px)">
                            <ul id="sidebarnav">
                                <li class="sidebar-item">
                                    <a class="sidebar-link has-arrow ms-0" href="javascript:void(0)"
                                       aria-expanded="false">
                      <span>
                        <iconify-icon icon="solar:slider-vertical-line-duotone" class="fs-7"></iconify-icon>
                      </span>
                                        <span class="hide-menu">Apps</span>
                                    </a>
                                    <ul aria-expanded="false" class="collapse first-level my-3 ps-3">
                                        <li class="sidebar-item py-2">
                                            <a href="default-sidebar/app-chat.html"
                                               class="d-flex align-items-center">
                                                <div
                                                    class="bg-primary-subtle rounded round-48 me-3 d-flex align-items-center justify-content-center">
                                                    <iconify-icon icon="solar:chat-line-bold-duotone"
                                                                  class="fs-7 text-primary"></iconify-icon>
                                                </div>
                                                <div class="d-inline-block">
                                                    <h6 class="mb-0 bg-hover-primary">Chat Application</h6>
                                                    <span
                                                        class="fs-11 d-block text-body-color">New messages arrived</span>
                                                </div>
                                            </a>
                                        </li>
                                        <li class="sidebar-item py-2">
                                            <a href="default-sidebar/app-invoice.html"
                                               class="d-flex align-items-center">
                                                <div
                                                    class="bg-secondary-subtle rounded round-48 me-3 d-flex align-items-center justify-content-center">
                                                    <iconify-icon icon="solar:bill-list-bold-duotone"
                                                                  class="fs-7 text-secondary"></iconify-icon>
                                                </div>
                                                <div class="d-inline-block">
                                                    <h6 class="mb-0 bg-hover-primary">Invoice App</h6>
                                                    <span
                                                        class="fs-11 d-block text-body-color">Get latest invoice</span>
                                                </div>
                                            </a>
                                        </li>
                                        <li class="sidebar-item py-2">
                                            <a href="default-sidebar/app-contact2.html"
                                               class="d-flex align-items-center">
                                                <div
                                                    class="bg-warning-subtle rounded round-48 me-3 d-flex align-items-center justify-content-center">
                                                    <iconify-icon icon="solar:phone-calling-rounded-bold-duotone"
                                                                  class="fs-7 text-warning"></iconify-icon>
                                                </div>
                                                <div class="d-inline-block">
                                                    <h6 class="mb-0 bg-hover-primary">Contact Application</h6>
                                                    <span
                                                        class="fs-11 d-block text-body-color">2 Unsaved Contacts</span>
                                                </div>
                                            </a>
                                        </li>
                                        <li class="sidebar-item py-2">
                                            <a href="default-sidebar/app-email.html"
                                               class="d-flex align-items-center">
                                                <div
                                                    class="bg-danger-subtle rounded round-48 me-3 d-flex align-items-center justify-content-center">
                                                    <iconify-icon icon="solar:letter-bold-duotone"
                                                                  class="fs-7 text-danger"></iconify-icon>
                                                </div>
                                                <div class="d-inline-block">
                                                    <h6 class="mb-0 bg-hover-primary">Email App</h6>
                                                    <span class="fs-11 d-block text-body-color">Get new emails</span>
                                                </div>
                                            </a>
                                        </li>
                                        <li class="sidebar-item py-2">
                                            <a href="default-sidebar/page-user-profile.html"
                                               class="d-flex align-items-center">
                                                <div
                                                    class="bg-success-subtle rounded round-48 me-3 d-flex align-items-center justify-content-center">
                                                    <iconify-icon icon="solar:user-bold-duotone"
                                                                  class="fs-7 text-success"></iconify-icon>
                                                </div>
                                                <div class="d-inline-block">
                                                    <h6 class="mb-0 bg-hover-primary">User Profile</h6>
                                                    <span
                                                        class="fs-11 d-block text-body-color">learn more information</span>
                                                </div>
                                            </a>
                                        </li>
                                        <li class="sidebar-item py-2">
                                            <a href="default-sidebar/app-calendar.html"
                                               class="d-flex align-items-center">
                                                <div
                                                    class="bg-primary-subtle rounded round-48 me-3 d-flex align-items-center justify-content-center">
                                                    <iconify-icon icon="solar:calendar-minimalistic-bold-duotone"
                                                                  class="fs-7 text-primary"></iconify-icon>
                                                </div>
                                                <div class="d-inline-block">
                                                    <h6 class="mb-0 bg-hover-primary">Calendar App</h6>
                                                    <span class="fs-11 d-block text-body-color">Get dates</span>
                                                </div>
                                            </a>
                                        </li>
                                        <li class="sidebar-item py-2">
                                            <a href="default-sidebar/app-contact.html"
                                               class="d-flex align-items-center">
                                                <div
                                                    class="bg-secondary-subtle rounded round-48 me-3 d-flex align-items-center justify-content-center">
                                                    <iconify-icon icon="solar:smartphone-2-bold-duotone"
                                                                  class="fs-7 text-secondary"></iconify-icon>
                                                </div>
                                                <div class="d-inline-block">
                                                    <h6 class="mb-0 bg-hover-primary">Contact List Table</h6>
                                                    <span class="fs-11 d-block text-body-color">Add new contact</span>
                                                </div>
                                            </a>
                                        </li>
                                        <li class="sidebar-item py-2">
                                            <a href="default-sidebar/app-notes.html"
                                               class="d-flex align-items-center">
                                                <div
                                                    class="bg-warning-subtle rounded round-48 me-3 d-flex align-items-center justify-content-center">
                                                    <iconify-icon icon="solar:notes-bold-duotone"
                                                                  class="fs-7 text-warning"></iconify-icon>
                                                </div>
                                                <div class="d-inline-block">
                                                    <h6 class="mb-0 bg-hover-primary">Notes Application</h6>
                                                    <span
                                                        class="fs-11 d-block text-body-color">To-do and Daily tasks</span>
                                                </div>
                                            </a>
                                        </li>
                                </li>
                            </ul>
                        </div>
                    </nav>
                </div>

            </div>
            <div class="app-header with-horizontal">
                <nav class="navbar navbar-expand-xl container-fluid p-0">
                    <ul class="navbar-nav align-items-center">
                        <li class="nav-item d-flex d-xl-none">
                            <a class="nav-link sidebartoggler nav-icon-hover-bg rounded-circle" id="sidebarCollapse"
                               href="javascript:void(0)">
                                <iconify-icon icon="solar:hamburger-menu-line-duotone" class="fs-7"></iconify-icon>
                            </a>
                        </li>
                        <li class="nav-item d-none d-xl-flex align-items-center">
                            <a href="horizontal/index.html" class="text-nowrap nav-link">
                                <img src="assets/images/logos/logo.svg" alt="matdash-img"/>
                            </a>
                        </li>
                        <li class="nav-item d-none d-xl-flex align-items-center nav-icon-hover-bg rounded-circle">
                            <a class="nav-link" href="javascript:void(0)" data-bs-toggle="modal"
                               data-bs-target="#exampleModal">
                                <iconify-icon icon="solar:magnifer-linear" class="fs-6"></iconify-icon>
                            </a>
                        </li>
                        <li class="nav-item d-none d-lg-flex align-items-center dropdown nav-icon-hover-bg rounded-circle">
                            <div class="hover-dd">
                                <a class="nav-link" id="drop2" href="javascript:void(0)" aria-haspopup="true"
                                   aria-expanded="false">
                                    <iconify-icon icon="solar:widget-3-line-duotone" class="fs-6"></iconify-icon>
                                </a>
                                <div
                                    class="dropdown-menu dropdown-menu-nav dropdown-menu-animate-up py-0 overflow-hidden"
                                    aria-labelledby="drop2">
                                    <div class="position-relative">
                                        <div class="row">
                                            <div class="col-8">
                                                <div class="p-4 pb-3">

                                                    <div class="row">
                                                        <div class="col-6">
                                                            <div class="position-relative">
                                                                <a href="default-sidebar/app-chat.html"
                                                                   class="d-flex align-items-center pb-9 position-relative">
                                                                    <div
                                                                        class="bg-primary-subtle rounded round-48 me-3 d-flex align-items-center justify-content-center">
                                                                        <iconify-icon
                                                                            icon="solar:chat-line-bold-duotone"
                                                                            class="fs-7 text-primary"></iconify-icon>
                                                                    </div>
                                                                    <div class="d-inline-block">
                                                                        <h6 class="mb-0">Chat Application</h6>
                                                                        <span class="fs-11 d-block text-body-color">New messages arrived</span>
                                                                    </div>
                                                                </a>
                                                                <a href="default-sidebar/app-invoice.html"
                                                                   class="d-flex align-items-center pb-9 position-relative">
                                                                    <div
                                                                        class="bg-secondary-subtle rounded round-48 me-3 d-flex align-items-center justify-content-center">
                                                                        <iconify-icon
                                                                            icon="solar:bill-list-bold-duotone"
                                                                            class="fs-7 text-secondary"></iconify-icon>
                                                                    </div>
                                                                    <div class="d-inline-block">
                                                                        <h6 class="mb-0">Invoice App</h6>
                                                                        <span class="fs-11 d-block text-body-color">Get latest invoice</span>
                                                                    </div>
                                                                </a>
                                                                <a href="default-sidebar/app-contact2.html"
                                                                   class="d-flex align-items-center pb-9 position-relative">
                                                                    <div
                                                                        class="bg-warning-subtle rounded round-48 me-3 d-flex align-items-center justify-content-center">
                                                                        <iconify-icon
                                                                            icon="solar:phone-calling-rounded-bold-duotone"
                                                                            class="fs-7 text-warning"></iconify-icon>
                                                                    </div>
                                                                    <div class="d-inline-block">
                                                                        <h6 class="mb-0">Contact Application</h6>
                                                                        <span class="fs-11 d-block text-body-color">2 Unsaved Contacts</span>
                                                                    </div>
                                                                </a>
                                                                <a href="default-sidebar/app-email.html"
                                                                   class="d-flex align-items-center pb-9 position-relative">
                                                                    <div
                                                                        class="bg-danger-subtle rounded round-48 me-3 d-flex align-items-center justify-content-center">
                                                                        <iconify-icon icon="solar:letter-bold-duotone"
                                                                                      class="fs-7 text-danger"></iconify-icon>
                                                                    </div>
                                                                    <div class="d-inline-block">
                                                                        <h6 class="mb-0">Email App</h6>
                                                                        <span class="fs-11 d-block text-body-color">Get new emails</span>
                                                                    </div>
                                                                </a>
                                                            </div>
                                                        </div>
                                                        <div class="col-6">
                                                            <div class="position-relative">
                                                                <a href="default-sidebar/page-user-profile.html"
                                                                   class="d-flex align-items-center pb-9 position-relative">
                                                                    <div
                                                                        class="bg-success-subtle rounded round-48 me-3 d-flex align-items-center justify-content-center">
                                                                        <iconify-icon icon="solar:user-bold-duotone"
                                                                                      class="fs-7 text-success"></iconify-icon>
                                                                    </div>
                                                                    <div class="d-inline-block">
                                                                        <h6 class="mb-0">User Profile</h6>
                                                                        <span class="fs-11 d-block text-body-color">learn more information</span>
                                                                    </div>
                                                                </a>
                                                                <a href="default-sidebar/app-calendar.html"
                                                                   class="d-flex align-items-center pb-9 position-relative">
                                                                    <div
                                                                        class="bg-primary-subtle rounded round-48 me-3 d-flex align-items-center justify-content-center">
                                                                        <iconify-icon
                                                                            icon="solar:calendar-minimalistic-bold-duotone"
                                                                            class="fs-7 text-primary"></iconify-icon>
                                                                    </div>
                                                                    <div class="d-inline-block">
                                                                        <h6 class="mb-0">Calendar App</h6>
                                                                        <span class="fs-11 d-block text-body-color">Get dates</span>
                                                                    </div>
                                                                </a>
                                                                <a href="default-sidebar/app-contact.html"
                                                                   class="d-flex align-items-center pb-9 position-relative">
                                                                    <div
                                                                        class="bg-secondary-subtle rounded round-48 me-3 d-flex align-items-center justify-content-center">
                                                                        <iconify-icon
                                                                            icon="solar:smartphone-2-bold-duotone"
                                                                            class="fs-7 text-secondary"></iconify-icon>
                                                                    </div>
                                                                    <div class="d-inline-block">
                                                                        <h6 class="mb-0">Contact List Table</h6>
                                                                        <span class="fs-11 d-block text-body-color">Add new contact</span>
                                                                    </div>
                                                                </a>
                                                                <a href="default-sidebar/app-notes.html"
                                                                   class="d-flex align-items-center pb-9 position-relative">
                                                                    <div
                                                                        class="bg-warning-subtle rounded round-48 me-3 d-flex align-items-center justify-content-center">
                                                                        <iconify-icon icon="solar:notes-bold-duotone"
                                                                                      class="fs-7 text-warning"></iconify-icon>
                                                                    </div>
                                                                    <div class="d-inline-block">
                                                                        <h6 class="mb-0">Notes Application</h6>
                                                                        <span class="fs-11 d-block text-body-color">To-do and Daily tasks</span>
                                                                    </div>
                                                                </a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <img src="assets/images/backgrounds/mega-dd-bg.jpg" alt="mega-dd"
                                                     class="img-fluid mega-dd-bg"/>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </li>
                    </ul>
                    <div class="d-block d-xl-none">
                        <a href="default-sidebar/index.html" class="text-nowrap nav-link">
                            <img src="assets/images/logos/logo.svg" alt="matdash-img"/>
                        </a>
                    </div>
                    <a class="navbar-toggler nav-icon-hover p-0 border-0 nav-icon-hover-bg rounded-circle"
                       href="javascript:void(0)" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                       aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
              <span class="p-2">
                <i class="ti ti-dots fs-7"></i>
              </span>
                    </a>
                    <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                        <div class="d-flex align-items-center justify-content-between px-0 px-xl-8">
                            <ul class="navbar-nav flex-row mx-auto ms-lg-auto align-items-center justify-content-center">
                                <li class="nav-item dropdown">
                                    <a href="javascript:void(0)"
                                       class="nav-link nav-icon-hover-bg rounded-circle d-flex d-lg-none align-items-center justify-content-center"
                                       type="button" data-bs-toggle="offcanvas" data-bs-target="#mobilenavbar"
                                       aria-controls="offcanvasWithBothOptions">
                                        <iconify-icon icon="solar:sort-line-duotone" class="fs-6"></iconify-icon>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link nav-icon-hover-bg rounded-circle moon dark-layout"
                                       href="javascript:void(0)">
                                        <iconify-icon icon="solar:moon-line-duotone" class="moon fs-6"></iconify-icon>
                                    </a>
                                    <a class="nav-link nav-icon-hover-bg rounded-circle sun light-layout"
                                       href="javascript:void(0)" style="display: none">
                                        <iconify-icon icon="solar:sun-2-line-duotone" class="sun fs-6"></iconify-icon>
                                    </a>
                                </li>
                                <li class="nav-item d-block d-xl-none">
                                    <a class="nav-link nav-icon-hover-bg rounded-circle" href="javascript:void(0)"
                                       data-bs-toggle="modal" data-bs-target="#exampleModal">
                                        <iconify-icon icon="solar:magnifer-line-duotone" class="fs-6"></iconify-icon>
                                    </a>
                                </li>

                                <!-- ------------------------------- -->
                                <!-- start notification Dropdown -->
                                <!-- ------------------------------- -->
                                <li class="nav-item dropdown nav-icon-hover-bg rounded-circle">
                                    <a class="nav-link position-relative" href="javascript:void(0)" id="drop2"
                                       aria-expanded="false">
                                        <iconify-icon icon="solar:bell-bing-line-duotone" class="fs-6"></iconify-icon>
                                    </a>
                                    <div class="dropdown-menu content-dd dropdown-menu-end dropdown-menu-animate-up"
                                         aria-labelledby="drop2">
                                        <div class="d-flex align-items-center justify-content-between py-3 px-7">
                                            <h5 class="mb-0 fs-5 fw-semibold">Notifications</h5>
                                            <span class="badge text-bg-primary rounded-4 px-3 py-1 lh-sm">5 new</span>
                                        </div>
                                        <div class="message-body" data-simplebar>
                                            <a href="javascript:void(0)"
                                               class="py-6 px-7 d-flex align-items-center dropdown-item gap-3">
                          <span
                              class="flex-shrink-0 bg-danger-subtle rounded-circle round d-flex align-items-center justify-content-center fs-6 text-danger">
                            <iconify-icon icon="solar:widget-3-line-duotone"></iconify-icon>
                          </span>
                                                <div class="w-75 d-inline-block ">
                                                    <div class="d-flex align-items-center justify-content-between">
                                                        <h6 class="mb-1 fw-semibold">Launch Admin</h6>
                                                        <span class="d-block fs-2">9:30 AM</span>
                                                    </div>
                                                    <span class="d-block text-truncate text-truncate fs-11">Just see the my new admin!</span>
                                                </div>
                                            </a>
                                            <a href="javascript:void(0)"
                                               class="py-6 px-7 d-flex align-items-center dropdown-item gap-3">
                          <span
                              class="flex-shrink-0 bg-primary-subtle rounded-circle round d-flex align-items-center justify-content-center fs-6 text-primary">
                            <iconify-icon icon="solar:calendar-line-duotone"></iconify-icon>
                          </span>
                                                <div class="w-75 d-inline-block ">
                                                    <div class="d-flex align-items-center justify-content-between">
                                                        <h6 class="mb-1 fw-semibold">Event today</h6>
                                                        <span class="d-block fs-2">9:15 AM</span>
                                                    </div>
                                                    <span class="d-block text-truncate text-truncate fs-11">Just a reminder that you have event</span>
                                                </div>
                                            </a>
                                            <a href="javascript:void(0)"
                                               class="py-6 px-7 d-flex align-items-center dropdown-item gap-3">
                          <span
                              class="flex-shrink-0 bg-secondary-subtle rounded-circle round d-flex align-items-center justify-content-center fs-6 text-secondary">
                            <iconify-icon icon="solar:settings-line-duotone"></iconify-icon>
                          </span>
                                                <div class="w-75 d-inline-block ">
                                                    <div class="d-flex align-items-center justify-content-between">
                                                        <h6 class="mb-1 fw-semibold">Settings</h6>
                                                        <span class="d-block fs-2">4:36 PM</span>
                                                    </div>
                                                    <span class="d-block text-truncate text-truncate fs-11">You can customize this template as you want</span>
                                                </div>
                                            </a>
                                            <a href="javascript:void(0)"
                                               class="py-6 px-7 d-flex align-items-center dropdown-item gap-3">
                          <span
                              class="flex-shrink-0 bg-warning-subtle rounded-circle round d-flex align-items-center justify-content-center fs-6 text-warning">
                            <iconify-icon icon="solar:widget-4-line-duotone"></iconify-icon>
                          </span>
                                                <div class="w-75 d-inline-block ">
                                                    <div class="d-flex align-items-center justify-content-between">
                                                        <h6 class="mb-1 fw-semibold">Launch Admin</h6>
                                                        <span class="d-block fs-2">9:30 AM</span>
                                                    </div>
                                                    <span class="d-block text-truncate text-truncate fs-11">Just see the my new admin!</span>
                                                </div>
                                            </a>
                                            <a href="javascript:void(0)"
                                               class="py-6 px-7 d-flex align-items-center dropdown-item gap-3">
                          <span
                              class="flex-shrink-0 bg-primary-subtle rounded-circle round d-flex align-items-center justify-content-center fs-6 text-primary">
                            <iconify-icon icon="solar:calendar-line-duotone"></iconify-icon>
                          </span>
                                                <div class="w-75 d-inline-block ">
                                                    <div class="d-flex align-items-center justify-content-between">
                                                        <h6 class="mb-1 fw-semibold">Event today</h6>
                                                        <span class="d-block fs-2">9:15 AM</span>
                                                    </div>
                                                    <span class="d-block text-truncate text-truncate fs-11">Just a reminder that you have event</span>
                                                </div>
                                            </a>
                                            <a href="javascript:void(0)"
                                               class="py-6 px-7 d-flex align-items-center dropdown-item gap-3">
                          <span
                              class="flex-shrink-0 bg-secondary-subtle rounded-circle round d-flex align-items-center justify-content-center fs-6 text-secondary">
                            <iconify-icon icon="solar:settings-line-duotone"></iconify-icon>
                          </span>
                                                <div class="w-75 d-inline-block ">
                                                    <div class="d-flex align-items-center justify-content-between">
                                                        <h6 class="mb-1 fw-semibold">Settings</h6>
                                                        <span class="d-block fs-2">4:36 PM</span>
                                                    </div>
                                                    <span class="d-block text-truncate text-truncate fs-11">You can customize this template as you want</span>
                                                </div>
                                            </a>
                                        </div>
                                        <div class="py-6 px-7 mb-1">
                                            <button class="btn btn-primary w-100">See All Notifications</button>
                                        </div>

                                    </div>
                                </li>
                                <!-- ------------------------------- -->
                                <!-- end notification Dropdown -->
                                <!-- ------------------------------- -->

                                <!-- ------------------------------- -->
                                <!-- start language Dropdown -->
                                <!-- ------------------------------- -->
                                <li class="nav-item dropdown nav-icon-hover-bg rounded-circle">
                                    <a class="nav-link" href="javascript:void(0)" id="drop2" data-bs-toggle="dropdown"
                                       aria-expanded="false">
                                        <img src="assets/images/flag/icon-flag-en.svg" alt="matdash-img" width="20px"
                                             height="20px" class="rounded-circle object-fit-cover round-20"/>
                                    </a>
                                    <div class="dropdown-menu dropdown-menu-end dropdown-menu-animate-up"
                                         aria-labelledby="drop2">
                                        <div class="message-body">
                                            <a href="javascript:void(0)"
                                               class="d-flex align-items-center gap-2 py-3 px-4 dropdown-item">
                                                <div class="position-relative">
                                                    <img src="assets/images/flag/icon-flag-en.svg" alt="matdash-img"
                                                         width="20px" height="20px"
                                                         class="rounded-circle object-fit-cover round-20"/>
                                                </div>
                                                <p class="mb-0 fs-3">English (UK)</p>
                                            </a>
                                            <a href="javascript:void(0)"
                                               class="d-flex align-items-center gap-2 py-3 px-4 dropdown-item">
                                                <div class="position-relative">
                                                    <img src="assets/images/flag/icon-flag-cn.svg" alt="matdash-img"
                                                         width="20px" height="20px"
                                                         class="rounded-circle object-fit-cover round-20"/>
                                                </div>
                                                <p class="mb-0 fs-3">中国人 (Chinese)</p>
                                            </a>
                                            <a href="javascript:void(0)"
                                               class="d-flex align-items-center gap-2 py-3 px-4 dropdown-item">
                                                <div class="position-relative">
                                                    <img src="assets/images/flag/icon-flag-fr.svg" alt="matdash-img"
                                                         width="20px" height="20px"
                                                         class="rounded-circle object-fit-cover round-20"/>
                                                </div>
                                                <p class="mb-0 fs-3">français (French)</p>
                                            </a>
                                            <a href="javascript:void(0)"
                                               class="d-flex align-items-center gap-2 py-3 px-4 dropdown-item">
                                                <div class="position-relative">
                                                    <img src="assets/images/flag/icon-flag-sa.svg" alt="matdash-img"
                                                         width="20px" height="20px"
                                                         class="rounded-circle object-fit-cover round-20"/>
                                                </div>
                                                <p class="mb-0 fs-3">عربي (Arabic)</p>
                                            </a>
                                        </div>
                                    </div>
                                </li>
                                <!-- ------------------------------- -->
                                <!-- end language Dropdown -->
                                <!-- ------------------------------- -->

                                <!-- ------------------------------- -->
                                <!-- start profile Dropdown -->
                                <!-- ------------------------------- -->
                                <li class="nav-item dropdown">
                                    <a class="nav-link" href="javascript:void(0)" id="drop1" aria-expanded="false">
                                        <div class="d-flex align-items-center gap-2 lh-base">
                                            <img src="assets/images/profile/user-1.jpg" class="rounded-circle"
                                                 width="35" height="35" alt="matdash-img"/>
                                            <iconify-icon icon="solar:alt-arrow-down-bold" class="fs-2"></iconify-icon>
                                        </div>
                                    </a>
                                    <div
                                        class="dropdown-menu profile-dropdown dropdown-menu-end dropdown-menu-animate-up"
                                        aria-labelledby="drop1">
                                        <div class="position-relative px-4 pt-3 pb-2">
                                            <div class="d-flex align-items-center mb-3 pb-3 border-bottom gap-6">
                                                <img src="assets/images/profile/user-1.jpg" class="rounded-circle"
                                                     width="56" height="56" alt="matdash-img"/>
                                                <div>
                                                    <h5 class="mb-0 fs-12">David McMichael <span
                                                            class="text-success fs-11">Pro</span>
                                                    </h5>
                                                    <p class="mb-0 text-dark">
                                                        david@wrappixel.com
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="message-body">
                                                <a href="default-sidebar/page-user-profile.html"
                                                   class="p-2 dropdown-item h6 rounded-1">
                                                    My Profile
                                                </a>
                                                <a href="default-sidebar/page-pricing.html"
                                                   class="p-2 dropdown-item h6 rounded-1">
                                                    My Subscription
                                                </a>
                                                <a href="default-sidebar/app-invoice.html"
                                                   class="p-2 dropdown-item h6 rounded-1">
                                                    My Invoice <span
                                                        class="badge bg-danger-subtle text-danger rounded ms-8">4</span>
                                                </a>
                                                <a href="default-sidebar/page-account-settings.html"
                                                   class="p-2 dropdown-item h6 rounded-1">
                                                    Account Settings
                                                </a>
                                                <a href="default-sidebar/authentication-login2.html"
                                                   class="p-2 dropdown-item h6 rounded-1">
                                                    Sign Out
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                                <!-- ------------------------------- -->
                                <!-- end profile Dropdown -->
                                <!-- ------------------------------- -->
                            </ul>
                        </div>
                    </div>
                </nav>

            </div>
        </header>
        <!--  Header End -->

        <aside class="left-sidebar with-horizontal">
            <!-- Sidebar scroll-->
            <div>
                <!-- Sidebar navigation-->
                <nav id="sidebarnavh" class="sidebar-nav scroll-sidebar container-fluid">
                    <ul id="sidebarnav">
                        <!-- ============================= -->
                        <!-- Home -->
                        <!-- ============================= -->
                        <li class="nav-small-cap">
                            <i class="ti ti-dots nav-small-cap-icon fs-4"></i>
                            <span class="hide-menu">Home</span>
                        </li>
                        <!-- =================== -->
                        <!-- Dashboard -->
                        <!-- =================== -->
                        <li class="sidebar-item">
                            <a class="sidebar-link has-arrow" href="javascript:void(0)" aria-expanded="false">
                  <span>
                    <iconify-icon icon="solar:layers-line-duotone" class="ti"></iconify-icon>
                  </span>
                                <span class="hide-menu">Dashboard</span>
                            </a>
                            <ul aria-expanded="false" class="collapse first-level">
                                <li class="sidebar-item">
                                    <a href="default-sidebar/index.html" class="sidebar-link">
                                        <i class="ti ti-aperture"></i>
                                        <span class="hide-menu">Dashboard 1</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a href="default-sidebar/index2.html" class="sidebar-link">
                                        <i class="ti ti-shopping-cart"></i>
                                        <span class="hide-menu">Dashboard 2</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a href="default-sidebar/index3.html" class="sidebar-link">
                                        <i class="ti ti-atom"></i>
                                        <span class="hide-menu">Dashboard 3</span>
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <!-- ============================= -->
                        <!-- Front Pages -->
                        <!-- ============================= -->
                        <li class="nav-small-cap">
                            <i class="ti ti-dots nav-small-cap-icon fs-4"></i>
                            <span class="hide-menu">Front Pages</span>
                        </li>

                        <!-- =================== -->
                        <!-- Icon -->
                        <!-- =================== -->
                        <li class="sidebar-item">
                            <a class="sidebar-link has-arrow" href="javascript:void(0)" aria-expanded="false">
                  <span class="rounded-3">
                    <iconify-icon icon="solar:home-angle-line-duotone" class="ti"></iconify-icon>
                  </span>
                                <span class="hide-menu">Front Pages</span>
                            </a>
                            <ul aria-expanded="false" class="collapse first-level">
                                <li class="sidebar-item">
                                    <a class="sidebar-link" href="default-sidebar/frontend-landingpage.html"
                                       aria-expanded="false">
                      <span class="rounded-3">
                        <i class="ti ti-circle"></i>
                      </span>
                                        <span class="hide-menu">Homepage</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link sidebar-link"
                                       href="default-sidebar/frontend-aboutpage.html" aria-expanded="false">
                      <span class="rounded-3">
                        <i class="ti ti-circle"></i>
                      </span>
                                        <span class="hide-menu">About Us</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link sidebar-link"
                                       href="default-sidebar/frontend-blogpage.html" aria-expanded="false">
                      <span class="rounded-3">
                        <i class="ti ti-circle"></i>
                      </span>
                                        <span class="hide-menu">Blog</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link sidebar-link"
                                       href="default-sidebar/frontend-blogdetailpage.html" aria-expanded="false">
                      <span class="rounded-3">
                        <i class="ti ti-circle"></i>
                      </span>
                                        <span class="hide-menu">Blog Details</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link sidebar-link"
                                       href="default-sidebar/frontend-contactpage.html" aria-expanded="false">
                      <span class="rounded-3">
                        <i class="ti ti-circle"></i>
                      </span>
                                        <span class="hide-menu">Contact Us</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link sidebar-link"
                                       href="default-sidebar/frontend-portfoliopage.html" aria-expanded="false">
                      <span class="rounded-3">
                        <i class="ti ti-circle"></i>
                      </span>
                                        <span class="hide-menu">Portfolio</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link sidebar-link"
                                       href="default-sidebar/frontend-pricingpage.html" aria-expanded="false">
                      <span class="rounded-3">
                        <i class="ti ti-circle"></i>
                      </span>
                                        <span class="hide-menu">Pricing</span>
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <!-- ============================= -->
                        <!-- Apps -->
                        <!-- ============================= -->
                        <li class="nav-small-cap">
                            <i class="ti ti-dots nav-small-cap-icon fs-4"></i>
                            <span class="hide-menu">Apps</span>
                        </li>
                        <li class="sidebar-item">
                            <a class="sidebar-link two-column has-arrow" href="javascript:void(0)"
                               aria-expanded="false">
                  <span>
                    <iconify-icon icon="solar:widget-line-duotone" class="ti"></iconify-icon>
                  </span>
                                <span class="hide-menu">Apps</span>
                            </a>
                            <ul aria-expanded="false" class="collapse first-level">
                                <li class="sidebar-item">
                                    <a href="default-sidebar/app-calendar.html" class="sidebar-link">
                                        <i class="ti ti-calendar"></i>
                                        <span class="hide-menu">Calendar</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a href="default-sidebar/apps-kanban.html" class="sidebar-link">
                                        <i class="ti ti-layout-kanban"></i>
                                        <span class="hide-menu">Kanban</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a href="default-sidebar/app-chat.html" class="sidebar-link">
                                        <i class="ti ti-message-dots"></i>
                                        <span class="hide-menu">Chat</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link" href="default-sidebar/app-email.html"
                                       aria-expanded="false">
                      <span>
                        <i class="ti ti-mail"></i>
                      </span>
                                        <span class="hide-menu">Email</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a href="default-sidebar/app-contact.html" class="sidebar-link">
                                        <i class="ti ti-phone"></i>
                                        <span class="hide-menu">Contact Table</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a href="default-sidebar/app-contact2.html" class="sidebar-link">
                                        <i class="ti ti-list-details"></i>
                                        <span class="hide-menu">Contact List</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a href="default-sidebar/app-notes.html" class="sidebar-link">
                                        <i class="ti ti-notes"></i>
                                        <span class="hide-menu">Notes</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a href="default-sidebar/app-invoice.html" class="sidebar-link">
                                        <i class="ti ti-file-text"></i>
                                        <span class="hide-menu">Invoice</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a href="default-sidebar/page-user-profile.html" class="sidebar-link">
                                        <i class="ti ti-user-circle"></i>
                                        <span class="hide-menu">User Profile</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a href="default-sidebar/blog-posts.html" class="sidebar-link">
                                        <i class="ti ti-article"></i>
                                        <span class="hide-menu">Posts</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a href="default-sidebar/blog-detail.html" class="sidebar-link">
                                        <i class="ti ti-details"></i>
                                        <span class="hide-menu">Detail</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a href="default-sidebar/eco-shop.html" class="sidebar-link">
                                        <i class="ti ti-shopping-cart"></i>
                                        <span class="hide-menu">Shop</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a href="default-sidebar/eco-shop-detail.html" class="sidebar-link">
                                        <i class="ti ti-basket"></i>
                                        <span class="hide-menu">Shop Detail</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a href="default-sidebar/eco-product-list.html" class="sidebar-link">
                                        <i class="ti ti-list-check"></i>
                                        <span class="hide-menu">List</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a href="default-sidebar/eco-checkout.html" class="sidebar-link">
                                        <i class="ti ti-brand-shopee"></i>
                                        <span class="hide-menu">Checkout</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link" href="default-sidebar/eco-add-product.html">
                                        <i class="ti ti-file-plus"></i>
                                        <span class="hide-menu">Add Product</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a class="sidebar-link" href="default-sidebar/eco-edit-product.html">
                                        <i class="ti ti-file-pencil"></i>
                                        <span class="hide-menu">Edit Product</span>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <!-- ============================= -->
                        <!-- PAGES -->
                        <!-- ============================= -->
                        <li class="nav-small-cap">
                            <i class="ti ti-dots nav-small-cap-icon fs-4"></i>
                            <span class="hide-menu">PAGES</span>
                        </li>
                        <li class="sidebar-item">
                            <a class="sidebar-link has-arrow" href="javascript:void(0)" aria-expanded="false">
                  <span>
                    <iconify-icon icon="solar:notes-line-duotone" class="ti"></iconify-icon>
                  </span>
                                <span class="hide-menu">Pages</span>
                            </a>
                            <ul aria-expanded="false" class="collapse first-level">
                                <li class="sidebar-item">
                                    <a href="default-sidebar/page-faq.html" class="sidebar-link">
                                        <i class="ti ti-help"></i>
                                        <span class="hide-menu">FAQ</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a href="default-sidebar/page-account-settings.html" class="sidebar-link">
                                        <i class="ti ti-user-circle"></i>
                                        <span class="hide-menu">Account Setting</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a href="default-sidebar/page-pricing.html" class="sidebar-link">
                                        <i class="ti ti-currency-dollar"></i>
                                        <span class="hide-menu">Pricing</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a href="default-sidebar/widgets-cards.html" class="sidebar-link">
                                        <i class="ti ti-cards"></i>
                                        <span class="hide-menu">Card</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a href="default-sidebar/widgets-banners.html" class="sidebar-link">
                                        <i class="ti ti-ad"></i>
                                        <span class="hide-menu">Banner</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a href="default-sidebar/widgets-charts.html" class="sidebar-link">
                                        <i class="ti ti-chart-bar"></i>
                                        <span class="hide-menu">Charts</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a href="default-sidebar/starter.html" class="sidebar-link">
                                        <i class="ti ti-file"></i>
                                        <span class="hide-menu">Starter</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a href="landingpage/index.html" class="sidebar-link">
                                        <i class="ti ti-app-window"></i>
                                        <span class="hide-menu">Landing Page</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a href="default-sidebar/icon-tabler.html" class="sidebar-link">
                                        <i class="ti ti-mood-smile"></i>
                                        <span class="hide-menu">Tabler Icon</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a href="default-sidebar/icon-solar.html" class="sidebar-link">
                                        <i class="ti ti-mood-smile"></i>
                                        <span class="hide-menu">Solar Icon</span>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <!-- ============================= -->
                        <!-- UI -->
                        <!-- ============================= -->
                        <li class="nav-small-cap">
                            <i class="ti ti-dots nav-small-cap-icon fs-4"></i>
                            <span class="hide-menu">UI</span>
                        </li>
                        <!-- =================== -->
                        <!-- UI Elements -->
                        <!-- =================== -->
                        <li class="sidebar-item mega-dropdown">
                            <a class="sidebar-link has-arrow" href="javascript:void(0)" aria-expanded="false">
                  <span class="rounded-3">
                    <iconify-icon icon="solar:archive-line-duotone" class="ti"></iconify-icon>
                  </span>
                                <span class="hide-menu">UI</span>
                            </a>
                            <ul aria-expanded="false" class="collapse first-level">
                                <li class="sidebar-item">
                                    <a href="default-sidebar/ui-accordian.html" class="sidebar-link">
                                        <i class="ti ti-circle"></i>
                                        <span class="hide-menu">Accordian</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a href="default-sidebar/ui-badge.html" class="sidebar-link">
                                        <i class="ti ti-circle"></i>
                                        <span class="hide-menu">Badge</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a href="default-sidebar/ui-buttons.html" class="sidebar-link">
                                        <i class="ti ti-circle"></i>
                                        <span class="hide-menu">Buttons</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a href="default-sidebar/ui-dropdowns.html" class="sidebar-link">
                                        <i class="ti ti-circle"></i>
                                        <span class="hide-menu">Dropdowns</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a href="default-sidebar/ui-modals.html" class="sidebar-link">
                                        <i class="ti ti-circle"></i>
                                        <span class="hide-menu">Modals</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a href="default-sidebar/ui-tab.html" class="sidebar-link">
                                        <i class="ti ti-circle"></i>
                                        <span class="hide-menu">Tab</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a href="default-sidebar/ui-tooltip-popover.html" class="sidebar-link">
                                        <i class="ti ti-circle"></i>
                                        <span class="hide-menu">Tooltip & Popover</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a href="default-sidebar/ui-notification.html" class="sidebar-link">
                                        <i class="ti ti-circle"></i>
                                        <span class="hide-menu">Notification</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a href="default-sidebar/ui-progressbar.html" class="sidebar-link">
                                        <i class="ti ti-circle"></i>
                                        <span class="hide-menu">Progressbar</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a href="default-sidebar/ui-pagination.html" class="sidebar-link">
                                        <i class="ti ti-circle"></i>
                                        <span class="hide-menu">Pagination</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a href="default-sidebar/ui-typography.html" class="sidebar-link">
                                        <i class="ti ti-circle"></i>
                                        <span class="hide-menu">Typography</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a href="default-sidebar/ui-bootstrap-ui.html" class="sidebar-link">
                                        <i class="ti ti-circle"></i>
                                        <span class="hide-menu">Bootstrap UI</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a href="default-sidebar/ui-breadcrumb.html" class="sidebar-link">
                                        <i class="ti ti-circle"></i>
                                        <span class="hide-menu">Breadcrumb</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a href="default-sidebar/ui-offcanvas.html" class="sidebar-link">
                                        <i class="ti ti-circle"></i>
                                        <span class="hide-menu">Offcanvas</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a href="default-sidebar/ui-lists.html" class="sidebar-link">
                                        <i class="ti ti-circle"></i>
                                        <span class="hide-menu">Lists</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a href="default-sidebar/ui-grid.html" class="sidebar-link">
                                        <i class="ti ti-circle"></i>
                                        <span class="hide-menu">Grid</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a href="default-sidebar/ui-carousel.html" class="sidebar-link">
                                        <i class="ti ti-circle"></i>
                                        <span class="hide-menu">Carousel</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a href="default-sidebar/ui-scrollspy.html" class="sidebar-link">
                                        <i class="ti ti-circle"></i>
                                        <span class="hide-menu">Scrollspy</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a href="default-sidebar/ui-spinner.html" class="sidebar-link">
                                        <i class="ti ti-circle"></i>
                                        <span class="hide-menu">Spinner</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a href="default-sidebar/ui-link.html" class="sidebar-link">
                                        <i class="ti ti-circle"></i>
                                        <span class="hide-menu">Link</span>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <!-- ============================= -->
                        <!-- Forms -->
                        <!-- ============================= -->
                        <li class="nav-small-cap">
                            <i class="ti ti-dots nav-small-cap-icon fs-4"></i>
                            <span class="hide-menu">Forms</span>
                        </li>
                        <!-- =================== -->
                        <!-- Forms -->
                        <!-- =================== -->
                        <li class="sidebar-item">
                            <a class="sidebar-link two-column has-arrow" href="javascript:void(0)"
                               aria-expanded="false">
                  <span class="rounded-3">
                    <iconify-icon icon="solar:folder-line-duotone" class="ti"></iconify-icon>
                  </span>
                                <span class="hide-menu">Forms</span>
                            </a>
                            <ul aria-expanded="false" class="collapse first-level">
                                <!-- form elements -->
                                <li class="sidebar-item">
                                    <a href="default-sidebar/form-inputs.html" class="sidebar-link">
                                        <i class="ti ti-circle"></i>
                                        <span class="hide-menu">Forms Input</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a href="default-sidebar/form-input-groups.html" class="sidebar-link">
                                        <i class="ti ti-circle"></i>
                                        <span class="hide-menu">Input Groups</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a href="default-sidebar/form-input-grid.html" class="sidebar-link">
                                        <i class="ti ti-circle"></i>
                                        <span class="hide-menu">Input Grid</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a href="default-sidebar/form-checkbox-radio.html" class="sidebar-link">
                                        <i class="ti ti-circle"></i>
                                        <span class="hide-menu">Checkbox & Radios</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a href="default-sidebar/form-bootstrap-switch.html" class="sidebar-link">
                                        <i class="ti ti-circle"></i>
                                        <span class="hide-menu">Bootstrap Switch</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a href="default-sidebar/form-select2.html" class="sidebar-link">
                                        <i class="ti ti-circle"></i>
                                        <span class="hide-menu">Select2</span>
                                    </a>
                                </li>
                                <!-- form inputs -->
                                <li class="sidebar-item">
                                    <a href="default-sidebar/form-basic.html" class="sidebar-link">
                                        <i class="ti ti-circle"></i>
                                        <span class="hide-menu">Basic Form</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a href="default-sidebar/form-vertical.html" class="sidebar-link">
                                        <i class="ti ti-circle"></i>
                                        <span class="hide-menu">Form Vertical</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a href="default-sidebar/form-horizontal.html" class="sidebar-link">
                                        <i class="ti ti-circle"></i>
                                        <span class="hide-menu">Form Horizontal</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a href="default-sidebar/form-actions.html" class="sidebar-link">
                                        <i class="ti ti-circle"></i>
                                        <span class="hide-menu">Form Actions</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a href="default-sidebar/form-row-separator.html" class="sidebar-link">
                                        <i class="ti ti-circle"></i>
                                        <span class="hide-menu">Row Separator</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a href="default-sidebar/form-bordered.html" class="sidebar-link">
                                        <i class="ti ti-circle"></i>
                                        <span class="hide-menu">Form Bordered</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a href="default-sidebar/form-detail.html" class="sidebar-link">
                                        <i class="ti ti-circle"></i>
                                        <span class="hide-menu">Form Detail</span>
                                    </a>
                                </li>
                                <!-- form wizard -->
                                <li class="sidebar-item">
                                    <a href="default-sidebar/form-wizard.html" class="sidebar-link">
                                        <i class="ti ti-circle"></i>
                                        <span class="hide-menu">Form Wizard</span>
                                    </a>
                                </li>
                                <!-- Quill Editor -->
                                <li class="sidebar-item">
                                    <a href="default-sidebar/form-editor-quill.html" class="sidebar-link">
                                        <i class="ti ti-circle"></i>
                                        <span class="hide-menu">Quill Editor</span>
                                    </a>
                                </li>
                                <!-- Tinymce Editor -->
                                <li class="sidebar-item">
                                    <a href="default-sidebar/form-editor-tinymce.html" class="sidebar-link">
                                        <i class="ti ti-circle"></i>
                                        <span class="hide-menu">Tinymce Editor</span>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <!-- ============================= -->
                        <!-- Tables -->
                        <!-- ============================= -->
                        <li class="nav-small-cap">
                            <i class="ti ti-dots nav-small-cap-icon fs-4"></i>
                            <span class="hide-menu">Tables</span>
                        </li>
                        <!-- =================== -->
                        <!-- Bootstrap Table -->
                        <!-- =================== -->
                        <li class="sidebar-item">
                            <a class="sidebar-link has-arrow" href="javascript:void(0)" aria-expanded="false">
                  <span class="rounded-3">
                    <iconify-icon icon="solar:tuning-square-2-line-duotone" class="ti"></iconify-icon>
                  </span>
                                <span class="hide-menu">Tables</span>
                            </a>
                            <ul aria-expanded="false" class="collapse first-level">
                                <li class="sidebar-item">
                                    <a href="default-sidebar/table-basic.html" class="sidebar-link">
                                        <i class="ti ti-circle"></i>
                                        <span class="hide-menu">Basic Table</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a href="default-sidebar/table-dark-basic.html" class="sidebar-link">
                                        <i class="ti ti-circle"></i>
                                        <span class="hide-menu">Dark Table</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a href="default-sidebar/table-sizing.html" class="sidebar-link">
                                        <i class="ti ti-circle"></i>
                                        <span class="hide-menu">Sizing Table</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a href="default-sidebar/table-layout-coloured.html" class="sidebar-link">
                                        <i class="ti ti-circle"></i>
                                        <span class="hide-menu">Coloured Table</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a href="default-sidebar/table-datatable-basic.html" class="sidebar-link">
                                        <i class="ti ti-circle"></i>
                                        <span class="hide-menu">Basic Initialisation</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a href="default-sidebar/table-datatable-api.html" class="sidebar-link">
                                        <i class="ti ti-circle"></i>
                                        <span class="hide-menu">API</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a href="default-sidebar/table-datatable-advanced.html" class="sidebar-link">
                                        <i class="ti ti-circle"></i>
                                        <span class="hide-menu">Advanced</span>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <!-- ============================= -->
                        <!-- Charts -->
                        <!-- ============================= -->
                        <li class="nav-small-cap">
                            <i class="ti ti-dots nav-small-cap-icon fs-4"></i>
                            <span class="hide-menu">Charts</span>
                        </li>
                        <!-- =================== -->
                        <!-- Apex Chart -->
                        <!-- =================== -->
                        <li class="sidebar-item">
                            <a class="sidebar-link has-arrow" href="javascript:void(0)" aria-expanded="false">
                  <span class="rounded-3">
                    <iconify-icon icon="solar:chart-square-line-duotone" class="ti"></iconify-icon>
                  </span>
                                <span class="hide-menu">Charts</span>
                            </a>
                            <ul aria-expanded="false" class="collapse first-level">
                                <li class="sidebar-item">
                                    <a href="default-sidebar/chart-apex-line.html" class="sidebar-link">
                                        <i class="ti ti-circle"></i>
                                        <span class="hide-menu">Line Chart</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a href="default-sidebar/chart-apex-area.html" class="sidebar-link">
                                        <i class="ti ti-circle"></i>
                                        <span class="hide-menu">Area Chart</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a href="default-sidebar/chart-apex-bar.html" class="sidebar-link">
                                        <i class="ti ti-circle"></i>
                                        <span class="hide-menu">Bar Chart</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a href="default-sidebar/chart-apex-pie.html" class="sidebar-link">
                                        <i class="ti ti-circle"></i>
                                        <span class="hide-menu">Pie Chart</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a href="default-sidebar/chart-apex-radial.html" class="sidebar-link">
                                        <i class="ti ti-circle"></i>
                                        <span class="hide-menu">Radial Chart</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a href="default-sidebar/chart-apex-radar.html" class="sidebar-link">
                                        <i class="ti ti-circle"></i>
                                        <span class="hide-menu">Radar Chart</span>
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <!-- multi level -->
                        <li class="sidebar-item">
                            <a class="sidebar-link has-arrow" href="javascript:void(0)" aria-expanded="false">
                  <span class="rounded-3">
                    <iconify-icon icon="solar:airbuds-case-minimalistic-line-duotone" class="ti"></iconify-icon>
                  </span>
                                <span class="hide-menu">Multi DD</span>
                            </a>
                            <ul aria-expanded="false" class="collapse first-level">
                                <li class="sidebar-item">
                                    <a href="docs/index.html" class="sidebar-link">
                                        <i class="ti ti-circle"></i>
                                        <span class="hide-menu">Documentation</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a href="javascript:void(0)" class="sidebar-link">
                                        <i class="ti ti-circle"></i>
                                        <span class="hide-menu">Page 1</span>
                                    </a>
                                </li>
                                <li class="sidebar-item">
                                    <a href="javascript:void(0)" class="sidebar-link has-arrow">
                                        <i class="ti ti-circle"></i>
                                        <span class="hide-menu">Page 2</span>
                                    </a>
                                    <ul aria-expanded="false" class="collapse second-level">
                                        <li class="sidebar-item">
                                            <a href="javascript:void(0)" class="sidebar-link">
                                                <i class="ti ti-circle"></i>
                                                <span class="hide-menu">Page 2.1</span>
                                            </a>
                                        </li>
                                        <li class="sidebar-item">
                                            <a href="javascript:void(0)" class="sidebar-link">
                                                <i class="ti ti-circle"></i>
                                                <span class="hide-menu">Page 2.2</span>
                                            </a>
                                        </li>
                                        <li class="sidebar-item">
                                            <a href="javascript:void(0)" class="sidebar-link">
                                                <i class="ti ti-circle"></i>
                                                <span class="hide-menu">Page 2.3</span>
                                            </a>
                                        </li>
                                    </ul>
                                </li>
                                <li class="sidebar-item">
                                    <a href="javascript:void(0)" class="sidebar-link">
                                        <i class="ti ti-circle"></i>
                                        <span class="hide-menu">Page 3</span>
                                    </a>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </nav>
                <!-- End Sidebar navigation -->
            </div>
            <!-- End Sidebar scroll-->
        </aside>

        <div class="body-wrapper">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-12">
                        <div class="widget-content searchable-container list">
                            <div class="card card-body">
                                <div class="row">
                                    <div class="col-md-4 col-xl-3">
                                        <form class="position-relative">
                                            <input type="text" class="form-control product-search ps-5" id="input-search" placeholder="Search Contacts..." />
                                            <i class="ti ti-search position-absolute top-50 start-0 translate-middle-y fs-6 text-dark ms-3"></i>
                                        </form>
                                    </div>
                                    <div class="col-md-8 col-xl-9 text-end d-flex justify-content-md-end justify-content-center mt-3 mt-md-0">
                                        <div class="action-btn show-btn">
                                            <a href="javascript:void(0)" class="delete-multiple bg-danger-subtle btn me-2 text-danger d-flex align-items-center ">
                                                <i class="ti ti-trash me-1 fs-5"></i> Delete All Row
                                            </a>
                                        </div>
                                        <a href="javascript:void(0)" id="btn-add-contact" class="btn btn-primary d-flex align-items-center">
                                            <i class="ti ti-users text-white me-1 fs-5"></i> Add Contact
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <!-- Modal -->
                            <div class="modal fade" id="addContactModal" tabindex="-1" role="dialog" aria-labelledby="addContactModalTitle" aria-hidden="true">
                                <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header d-flex align-items-center">
                                            <h5 class="modal-title">Contact</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="add-contact-box">
                                                <div class="add-contact-content">
                                                    <form id="addContactModalTitle">
                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <div class="mb-3 contact-name">
                                                                    <input type="text" id="c-name" class="form-control" placeholder="Name" />
                                                                    <span class="validation-text text-danger"></span>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <div class="mb-3 contact-email">
                                                                    <input type="text" id="c-email" class="form-control" placeholder="Email" />
                                                                    <span class="validation-text text-danger"></span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <div class="mb-3 contact-occupation">
                                                                    <input type="text" id="c-occupation" class="form-control" placeholder="Occupation" />
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <div class="mb-3 contact-phone">
                                                                    <input type="text" id="c-phone" class="form-control" placeholder="Phone" />
                                                                    <span class="validation-text text-danger"></span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-md-12">
                                                                <div class="mb-3 contact-location">
                                                                    <input type="text" id="c-location" class="form-control" placeholder="Location" />
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <div class="d-flex gap-6 m-0">
                                                <button id="btn-add" class="btn btn-success">Add</button>
                                                <button id="btn-edit" class="btn btn-success">Save</button>
                                                <button class="btn bg-danger-subtle text-danger" data-bs-dismiss="modal"> Discard
                                                </button>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card card-body">
                                <div class="table-responsive">
                                    <table class="table search-table align-middle text-nowrap">
                                        <thead class="header-item">
                                        <th>
                                            <div class="n-chk align-self-center text-center">
                                                <div class="form-check">
                                                    <input type="checkbox" class="form-check-input primary" id="contact-check-all" />
                                                    <label class="form-check-label" for="contact-check-all"></label>
                                                    <span class="new-control-indicator"></span>
                                                </div>
                                            </div>
                                        </th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Location</th>
                                        <th>Phone</th>
                                        <th>Action</th>
                                        </thead>
                                        <tbody>
                                        <!-- start row -->
                                        <tr class="search-items">
                                            <td>
                                                <div class="n-chk align-self-center text-center">
                                                    <div class="form-check">
                                                        <input type="checkbox" class="form-check-input contact-chkbox primary" id="checkbox1" />
                                                        <label class="form-check-label" for="checkbox1"></label>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="../assets/images/profile/user-10.jpg" alt="avatar" class="rounded-circle" width="35" />
                                                    <div class="ms-3">
                                                        <div class="user-meta-info">
                                                            <h6 class="user-name mb-0" data-name="Emma Adams">Emma Adams</h6>
                                                            <span class="user-work fs-3" data-occupation="Web Developer">Web Developer</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="usr-email-addr" data-email="adams@mail.com">adams@mail.com</span>
                                            </td>
                                            <td>
                                                <span class="usr-location" data-location="Boston, USA">Boston, USA</span>
                                            </td>
                                            <td>
                                                <span class="usr-ph-no" data-phone="+1 (070) 123-4567">+91 (070) 123-4567</span>
                                            </td>
                                            <td>
                                                <div class="action-btn">
                                                    <a href="javascript:void(0)" class="text-primary edit">
                                                        <i class="ti ti-eye fs-5"></i>
                                                    </a>
                                                    <a href="javascript:void(0)" class="text-dark delete ms-2">
                                                        <i class="ti ti-trash fs-5"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        <!-- end row -->
                                        <!-- start row -->
                                        <tr class="search-items">
                                            <td>
                                                <div class="n-chk align-self-center text-center">
                                                    <div class="form-check">
                                                        <input type="checkbox" class="form-check-input contact-chkbox primary" id="checkbox2" />
                                                        <label class="form-check-label" for="checkbox2"></label>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="../assets/images/profile/user-2.jpg" alt="avatar" class="rounded-circle" width="35" />
                                                    <div class="ms-3">
                                                        <div class="user-meta-info">
                                                            <h6 class="user-name mb-0" data-name="Olivia Allen">Olivia Allen</h6>
                                                            <span class="user-work fs-3" data-occupation="Web Designer">Web Designer</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="usr-email-addr" data-email="allen@mail.com">allen@mail.com</span>
                                            </td>
                                            <td>
                                                <span class="usr-location" data-location="Sydney, Australia">Sydney, Australia</span>
                                            </td>
                                            <td>
                                                <span class="usr-ph-no" data-phone="+91 (125) 450-1500">+91 (125) 450-1500</span>
                                            </td>
                                            <td>
                                                <div class="action-btn">
                                                    <a href="javascript:void(0)" class="text-primary edit">
                                                        <i class="ti ti-eye fs-5"></i>
                                                    </a>
                                                    <a href="javascript:void(0)" class="text-dark delete ms-2">
                                                        <i class="ti ti-trash fs-5"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        <!-- end row -->
                                        <!-- start row -->
                                        <tr class="search-items">
                                            <td>
                                                <div class="n-chk align-self-center text-center">
                                                    <div class="form-check">
                                                        <input type="checkbox" class="form-check-input contact-chkbox primary" id="checkbox3" />
                                                        <label class="form-check-label" for="checkbox3"></label>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="../assets/images/profile/user-3.jpg" alt="avatar" class="rounded-circle" width="35" />
                                                    <div class="ms-3">
                                                        <div class="user-meta-info">
                                                            <h6 class="user-name mb-0" data-name="Isabella Anderson"> Isabella Anderson </h6>
                                                            <span class="user-work fs-3" data-occupation="UX/UI Designer">UX/UI Designer</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="usr-email-addr" data-email="anderson@mail.com">anderson@mail.com</span>
                                            </td>
                                            <td>
                                                <span class="usr-location" data-location="Miami, USA">Miami, USA</span>
                                            </td>
                                            <td>
                                                <span class="usr-ph-no" data-phone="+91 (100) 154-1254">+91 (100) 154-1254</span>
                                            </td>
                                            <td>
                                                <div class="action-btn">
                                                    <a href="javascript:void(0)" class="text-primary edit">
                                                        <i class="ti ti-eye fs-5"></i>
                                                    </a>
                                                    <a href="javascript:void(0)" class="text-dark delete ms-2">
                                                        <i class="ti ti-trash fs-5"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        <!-- end row -->
                                        <!-- start row -->
                                        <tr class="search-items">
                                            <td>
                                                <div class="n-chk align-self-center text-center">
                                                    <div class="form-check">
                                                        <input type="checkbox" class="form-check-input contact-chkbox primary" id="checkbox4" />
                                                        <label class="form-check-label" for="checkbox4"></label>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="../assets/images/profile/user-4.jpg" alt="avatar" class="rounded-circle" width="35" />
                                                    <div class="ms-3">
                                                        <div class="user-meta-info">
                                                            <h6 class="user-name mb-0" data-name="Amelia Armstrong"> Amelia Armstrong </h6>
                                                            <span class="user-work fs-3" data-occupation="Ethical Hacker">Ethical Hacker</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="usr-email-addr" data-email="armstrong@mail.com">armstrong@mail.com</span>
                                            </td>
                                            <td>
                                                <span class="usr-location" data-location="Tokyo, Japan">Tokyo, Japan</span>
                                            </td>
                                            <td>
                                                <span class="usr-ph-no" data-phone="+91 (154) 199- 1540">+91 (154) 199- 1540</span>
                                            </td>
                                            <td>
                                                <div class="action-btn">
                                                    <a href="javascript:void(0)" class="text-primary edit">
                                                        <i class="ti ti-eye fs-5"></i>
                                                    </a>
                                                    <a href="javascript:void(0)" class="text-dark delete ms-2">
                                                        <i class="ti ti-trash fs-5"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        <!-- end row -->
                                        <!-- start row -->
                                        <tr class="search-items">
                                            <td>
                                                <div class="n-chk align-self-center text-center">
                                                    <div class="form-check">
                                                        <input type="checkbox" class="form-check-input contact-chkbox primary" id="checkbox5" />
                                                        <label class="form-check-label" for="checkbox5"></label>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="../assets/images/profile/user-5.jpg" alt="avatar" class="rounded-circle" width="35" />
                                                    <div class="ms-3">
                                                        <div class="user-meta-info">
                                                            <h6 class="user-name mb-0" data-name="Emily Atkinson"> Emily Atkinson </h6>
                                                            <span class="user-work fs-3" data-occupation="Web developer">Web developer</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="usr-email-addr" data-email="atkinson@mail.com">atkinson@mail.com</span>
                                            </td>
                                            <td>
                                                <span class="usr-location" data-location="Edinburgh, UK">Edinburgh, UK</span>
                                            </td>
                                            <td>
                                                <span class="usr-ph-no" data-phone="+91 (900) 150- 1500">+91 (900) 150- 1500</span>
                                            </td>
                                            <td>
                                                <div class="action-btn">
                                                    <a href="javascript:void(0)" class="text-primary edit">
                                                        <i class="ti ti-eye fs-5"></i>
                                                    </a>
                                                    <a href="javascript:void(0)" class="text-dark delete ms-2">
                                                        <i class="ti ti-trash fs-5"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        <!-- end row -->
                                        <!-- start row -->
                                        <tr class="search-items">
                                            <td>
                                                <div class="n-chk align-self-center text-center">
                                                    <div class="form-check">
                                                        <input type="checkbox" class="form-check-input contact-chkbox primary" id="checkbox6" />
                                                        <label class="form-check-label" for="checkbox6"></label>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="../assets/images/profile/user-12.jpg" alt="avatar" class="rounded-circle" width="35" />
                                                    <div class="ms-3">
                                                        <div class="user-meta-info">
                                                            <h6 class="user-name mb-0" data-name="John Deo">John Deo</h6>
                                                            <span class="user-work fs-3" data-occupation="UX/UI Designer">UX/UI Designer</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="usr-email-addr" data-email="bailey@mail.com">bailey@mail.com</span>
                                            </td>
                                            <td>
                                                <span class="usr-location" data-location="New York, USA">New York, USA</span>
                                            </td>
                                            <td>
                                                <span class="usr-ph-no" data-phone="+91 (001) 160- 1845">+91 (001) 160- 1845</span>
                                            </td>
                                            <td>
                                                <div class="action-btn">
                                                    <a href="javascript:void(0)" class="text-primary edit">
                                                        <i class="ti ti-eye fs-5"></i>
                                                    </a>
                                                    <a href="javascript:void(0)" class="text-dark delete ms-2">
                                                        <i class="ti ti-trash fs-5"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr class="search-items">
                                            <td>
                                                <div class="n-chk align-self-center text-center">
                                                    <div class="form-check">
                                                        <input type="checkbox" class="form-check-input contact-chkbox primary" id="checkbox7" />
                                                        <label class="form-check-label" for="checkbox7"></label>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="../assets/images/profile/user-2.jpg" alt="avatar" class="rounded-circle" width="35" />
                                                    <div class="ms-3">
                                                        <div class="user-meta-info">
                                                            <h6 class="user-name mb-0" data-name="Victoria Sharma"> Victoria Sharma </h6>
                                                            <span class="user-work fs-3" data-occupation="Project Manager">Project Manager</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="usr-email-addr" data-email="sharma@mail.com">sharma@mail.com</span>
                                            </td>
                                            <td>
                                                <span class="usr-location" data-location="Miami, USA">Miami, USA</span>
                                            </td>
                                            <td>
                                                <span class="usr-ph-no" data-phone="+91 (110) 180- 1600">+91 (110) 180- 1600</span>
                                            </td>
                                            <td>
                                                <div class="action-btn">
                                                    <a href="javascript:void(0)" class="text-primary edit">
                                                        <i class="ti ti-eye fs-5"></i>
                                                    </a>
                                                    <a href="javascript:void(0)" class="text-dark delete ms-2">
                                                        <i class="ti ti-trash fs-5"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr class="search-items">
                                            <td>
                                                <div class="n-chk align-self-center text-center">
                                                    <div class="form-check">
                                                        <input type="checkbox" class="form-check-input contact-chkbox primary" id="checkbox8" />
                                                        <label class="form-check-label" for="checkbox8"></label>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="../assets/images/profile/user-3.jpg" alt="avatar" class="rounded-circle" width="35" />
                                                    <div class="ms-3">
                                                        <div class="user-meta-info">
                                                            <h6 class="user-name mb-0" data-name="Penelope Baker"> Penelope Baker </h6>
                                                            <span class="user-work fs-3" data-occupation="Web Developer">Web Developer</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="usr-email-addr" data-email="baker@mail.com">baker@mail.com</span>
                                            </td>
                                            <td>
                                                <span class="usr-location" data-location="Edinburgh, UK">Edinburgh, UK</span>
                                            </td>
                                            <td>
                                                <span class="usr-ph-no" data-phone="+91 (405) 483- 4512">+91 (405) 483- 4512</span>
                                            </td>
                                            <td>
                                                <div class="action-btn">
                                                    <a href="javascript:void(0)" class="text-primary edit">
                                                        <i class="ti ti-eye fs-5"></i>
                                                    </a>
                                                    <a href="javascript:void(0)" class="text-dark delete ms-2">
                                                        <i class="ti ti-trash fs-5"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                    </div>

                </div>
            </div>
        </div>

    </div>

    <!--  Search Bar -->
    <div class="modal fade" id="exampleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-bottom">
                    <input type="search" class="form-control" placeholder="Search here" id="search"/>
                    <a href="javascript:void(0)" data-bs-dismiss="modal" class="lh-1">
                        <i class="ti ti-x fs-5 ms-3"></i>
                    </a>
                </div>
                <div class="modal-body message-body" data-simplebar="">
                    <h5 class="mb-0 fs-5 p-1">Quick Page Links</h5>
                    <ul class="list mb-0 py-2">
                        <li class="p-1 mb-1 bg-hover-light-black rounded px-2">
                            <a href="javascript:void(0)">
                                <span class="text-dark fw-semibold d-block">Analytics</span>
                                <span class="fs-2 d-block text-body-secondary">/dashboards/dashboard1</span>
                            </a>
                        </li>
                        <li class="p-1 mb-1 bg-hover-light-black rounded px-2">
                            <a href="javascript:void(0)">
                                <span class="text-dark fw-semibold d-block">eCommerce</span>
                                <span class="fs-2 d-block text-body-secondary">/dashboards/dashboard2</span>
                            </a>
                        </li>
                        <li class="p-1 mb-1 bg-hover-light-black rounded px-2">
                            <a href="javascript:void(0)">
                                <span class="text-dark fw-semibold d-block">CRM</span>
                                <span class="fs-2 d-block text-body-secondary">/dashboards/dashboard3</span>
                            </a>
                        </li>
                        <li class="p-1 mb-1 bg-hover-light-black rounded px-2">
                            <a href="javascript:void(0)">
                                <span class="text-dark fw-semibold d-block">Contacts</span>
                                <span class="fs-2 d-block text-body-secondary">/apps/contacts</span>
                            </a>
                        </li>
                        <li class="p-1 mb-1 bg-hover-light-black rounded px-2">
                            <a href="javascript:void(0)">
                                <span class="text-dark fw-semibold d-block">Posts</span>
                                <span class="fs-2 d-block text-body-secondary">/apps/blog/posts</span>
                            </a>
                        </li>
                        <li class="p-1 mb-1 bg-hover-light-black rounded px-2">
                            <a href="javascript:void(0)">
                                <span class="text-dark fw-semibold d-block">Detail</span>
                                <span class="fs-2 d-block text-body-secondary">/apps/blog/detail/streaming-video-way-before-it-was-cool-go-dark-tomorrow</span>
                            </a>
                        </li>
                        <li class="p-1 mb-1 bg-hover-light-black rounded px-2">
                            <a href="javascript:void(0)">
                                <span class="text-dark fw-semibold d-block">Shop</span>
                                <span class="fs-2 d-block text-body-secondary">/apps/ecommerce/shop</span>
                            </a>
                        </li>
                        <li class="p-1 mb-1 bg-hover-light-black rounded px-2">
                            <a href="javascript:void(0)">
                                <span class="text-dark fw-semibold d-block">Modern</span>
                                <span class="fs-2 d-block text-body-secondary">/dashboards/dashboard1</span>
                            </a>
                        </li>
                        <li class="p-1 mb-1 bg-hover-light-black rounded px-2">
                            <a href="javascript:void(0)">
                                <span class="text-dark fw-semibold d-block">Dashboard</span>
                                <span class="fs-2 d-block text-body-secondary">/dashboards/dashboard2</span>
                            </a>
                        </li>
                        <li class="p-1 mb-1 bg-hover-light-black rounded px-2">
                            <a href="javascript:void(0)">
                                <span class="text-dark fw-semibold d-block">Contacts</span>
                                <span class="fs-2 d-block text-body-secondary">/apps/contacts</span>
                            </a>
                        </li>
                        <li class="p-1 mb-1 bg-hover-light-black rounded px-2">
                            <a href="javascript:void(0)">
                                <span class="text-dark fw-semibold d-block">Posts</span>
                                <span class="fs-2 d-block text-body-secondary">/apps/blog/posts</span>
                            </a>
                        </li>
                        <li class="p-1 mb-1 bg-hover-light-black rounded px-2">
                            <a href="javascript:void(0)">
                                <span class="text-dark fw-semibold d-block">Detail</span>
                                <span class="fs-2 d-block text-body-secondary">/apps/blog/detail/streaming-video-way-before-it-was-cool-go-dark-tomorrow</span>
                            </a>
                        </li>
                        <li class="p-1 mb-1 bg-hover-light-black rounded px-2">
                            <a href="javascript:void(0)">
                                <span class="text-dark fw-semibold d-block">Shop</span>
                                <span class="fs-2 d-block text-body-secondary">/apps/ecommerce/shop</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

</div>
<div class="dark-transparent sidebartoggler"></div>
<!-- Import Js Files -->
<script src="assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/libs/simplebar/dist/simplebar.min.js"></script>
<script src="assets/js/theme/app.init.js"></script>
<script src="assets/js/theme/theme.js"></script>
<script src="assets/js/theme/app.min.js"></script>
<script src="assets/js/theme/sidebarmenu-default.js"></script>

<!-- solar icons -->
<script src="https://cdn.jsdelivr.net/npm/iconify-icon@1.0.8/dist/iconify-icon.min.js"></script>
<script src="assets/libs/fullcalendar/index.global.min.js"></script>
<script src="assets/js/apps/calendar-init.js"></script>
<script src="assets/js/vendor.min.js"></script>
<script src="assets/libs/apexcharts/dist/apexcharts.min.js"></script>
<script src="assets/js/dashboards/dashboard3.js"></script>
<script src="../assets/js/apps/contact.js"></script>
</body>

</html>
