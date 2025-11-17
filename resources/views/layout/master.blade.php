<!DOCTYPE html>

<html
  lang="en"
  class="light-style layout-menu-fixed"
  dir="ltr"
  data-theme="theme-default"
  data-assets-path="{{ asset('') }}"
  data-template="vertical-menu-template-free"
>
  <head>
    <meta charset="utf-8" />
    <meta
      name="viewport"
      content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0"
    />

    <title>@yield('page-title', "TRA Central Database Admin")</title>

    <meta name="description" content="" />

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon"  href="{{ asset('img/favicon/favicon.ico') }}?v={{ currentVersion() }}" />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap"
      rel="stylesheet"
    />

    <!-- Icons. Uncomment required icon fonts -->
    <link rel="stylesheet" href="{{ asset('vendor/fonts/boxicons.css') }}?v={{ currentVersion() }}" />

    <!-- Core CSS -->
    <link rel="stylesheet" href="{{ asset('vendor/css/core.css') }}?v={{ currentVersion() }}" class="template-customizer-core-css" />
    <link rel="stylesheet" href="{{ asset('vendor/css/theme-default.css') }}?v={{ currentVersion() }}" class="template-customizer-theme-css" />
    <link rel="stylesheet" href="{{ asset('css/demo.css') }}?v={{ currentVersion() }}" />

    <!-- Vendors CSS -->
    <link rel="stylesheet" href="{{ asset('vendor/libs/perfect-scrollbar/perfect-scrollbar.css') }}?v={{ currentVersion() }}" />

    <link rel="stylesheet" href="{{ asset('vendor/libs/apex-charts/apex-charts.css') }}?v={{ currentVersion() }}" />
    <link rel="stylesheet" href="{{ asset('vendor/css/daterangepicker.css') }}?v={{ currentVersion() }}">
    <link rel="stylesheet" href="{{ asset('vendor/css/dataTables.bootstrap5.min.css') }}?v={{ currentVersion() }}">
    <link rel="stylesheet" href="{{ asset('vendor/css/toastr.min.css') }}?v={{ currentVersion() }}">
    <link rel="stylesheet" href="{{ asset('vendor/css/sweetalert2.min.css') }}?v={{ currentVersion() }}">
    <link rel="stylesheet" href="{{ asset('vendor/css/select2.min.css') }}?v={{ currentVersion() }}">
    <link rel="stylesheet" href="{{ asset('vendor/css/fontawesome/css/all.min.css') }}?v={{ currentVersion() }}">
    <!-- FontAwesome CDN -->

    @yield('custom-page-style')

    <!-- Page CSS -->

    <!-- Helpers -->
    <script src="{{ asset('vendor/js/helpers.js') }}?v={{ currentVersion() }}"></script>

    <!--! Template customizer & Theme config files MUST be included after core stylesheets and helpers.js in the <head> section -->
    <!--? Config:  Mandatory theme config file contain global vars & default theme options, Set your preferred theme option in this file.  -->
    <script src="{{ asset('js/config.js') }}?v={{ currentVersion() }}"></script>
  </head>

  <body>
    <div id="preloader">
        <div class="loader"></div>
    </div>
    <!-- Logout Form -->
    <form id="logout-form" action="{{ route('admin.logout') }}" method="POST" style="display: none;">
        @csrf
    </form>
    <!-- ./Logout Form -->

    <!-- Layout wrapper -->
    <div class="layout-wrapper layout-content-navbar">
      <div class="layout-container">
        <!-- Menu -->
        @include('layout.sidebar')
        <!-- / Menu -->

        <!-- Layout container -->
        <div class="layout-page">

          <!-- Navbar -->
          <nav
            class="layout-navbar container-fluid navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme"
            id="layout-navbar"
          >
            <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
              <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0)">
                <i class="bx bx-menu bx-sm"></i>
              </a>
            </div>

            <div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">
              <!-- Search -->
              <div class="navbar-nav align-items-center">
                <div class="nav-item d-flex align-items-center">
                  <h5 class="card-title text-primary mt-3">Welcome {{ auth()->user()->name }} !</h5>
                  <!-- <i class="bx bx-search fs-4 lh-0"></i>
                  <input
                    type="text"
                    class="form-control border-0 shadow-none"
                    placeholder="Search..."
                    aria-label="Search..."
                  /> -->
                </div>
              </div>
              <!-- /Search -->

              <ul class="navbar-nav flex-row align-items-center ms-auto">
                <li class="nav-item navbar-clock">
                    <div id="clock-container">
                        <i class="fa-solid fa-calendar-days text-primary"></i> <span id="date"></span> |
                        <i class="fa-solid fa-clock text-primary"></i> <span id="time"></span> |
                        <i class="fa-solid fa-globe text-primary"></i> <span id="timezone"></span>
                    </div>
                </li>
                <!-- User -->
                <li class="nav-item navbar-dropdown dropdown-user dropdown">
                  <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
                    <div class="avatar avatar-online">
                      <img src="{{ isset(Auth::user()->profile_image_url) ? Auth::user()->profile_image_url : asset('img/avatars/admin.png') }}" alt class="w-px-40 h-auto rounded-circle" />
                    </div>
                  </a>
                  <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                      <a class="dropdown-item" href="#">
                        <div class="d-flex">
                          <div class="flex-shrink-0 me-3">
                            <div class="avatar avatar-online">
                              <img src="{{ isset(Auth::user()->profile_image_url) ? Auth::user()->profile_image_url : asset('img/avatars/admin.png') }}" alt class="w-px-40 h-auto rounded-circle" />
                            </div>
                          </div>
                          <div class="flex-grow-1">
                            <span class="fw-semibold d-block">{{ isset(auth()->user()->name) ? auth()->user()->name : '' }}</span>
                            <small class="text-muted">Admin</small>
                          </div>
                        </div>
                      </a>
                    </li>
                    <li>
                      <div class="dropdown-divider"></div>
                    </li>
                    <li>
                      <a class="dropdown-item" href="{{ route('admin.profile') }}">
                        <i class="bx bx-user me-2"></i>
                        <span class="align-middle">My Profile</span>
                      </a>
                    </li>
                    <!-- <li>
                      <a class="dropdown-item" href="#">
                        <i class="bx bx-cog me-2"></i>
                        <span class="align-middle">Settings</span>
                      </a>
                    </li> -->
                    <!-- <li>
                      <a class="dropdown-item" href="#">
                        <span class="d-flex align-items-center align-middle">
                          <i class="flex-shrink-0 bx bx-credit-card me-2"></i>
                          <span class="flex-grow-1 align-middle">Billing</span>
                          <span class="flex-shrink-0 badge badge-center rounded-pill bg-danger w-px-20 h-px-20">4</span>
                        </span>
                      </a>
                    </li> -->
                    <li>
                      <div class="dropdown-divider"></div>
                    </li>
                    <li>
                      <a class="dropdown-item" href="javascript:void(0)" onclick="document.getElementById('logout-form').submit();">
                        <i class="bx bx-power-off me-2"></i>
                        <span class="align-middle">Log Out</span>
                      </a>
                    </li>
                  </ul>
                </li>
                <!--/ User -->
              </ul>
            </div>
          </nav>

          <!-- / Navbar -->

          <!-- Content wrapper -->
          @yield('page-content')
          <!-- Content wrapper -->

          <!-- Bootstrap Toast Container (Fixed at Bottom-Right) -->
          <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1050">
              <div id="copyToast" class="toast align-items-center text-white bg-dark border-0" role="alert" aria-live="polite" aria-atomic="true" data-bs-delay="2000">
                  <div class="d-flex">
                      <div class="toast-body">
                          ✅ Copied to clipboard!
                      </div>
                      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                  </div>
              </div>
          </div>
        </div>
        <!-- / Layout page -->

      </div>

      <!-- Overlay -->
      <div class="layout-overlay layout-menu-toggle"></div>
    </div>
    <!-- / Layout wrapper -->

    

    <!-- Core JS -->
    <!-- build:js assets/vendor/js/core.js -->
    @include('layout.footer-scripts')
    <!-- endbuild -->

    <!-- Vendors JS -->
    <script src="{{ asset('vendor/libs/apex-charts/apexcharts.js') }}?v={{ currentVersion() }}"></script>
    <!-- Place this tag in your head or just before your close body tag. -->
    <script async defer src="{{ asset('vendor/libs/buttons.js') }}?v={{ currentVersion() }}"></script>
    <script async defer src="{{ asset('vendor/js/luxon.min.js') }}?v={{ currentVersion() }}"></script>


    <!-- Main JS -->
    <script src="{{ asset('js/main.js') }}?v={{ currentVersion() }}"></script>

    <script>
        $(document).ready(function() {
            // Hide the preloader smoothly
            $('#preloader').hide();

            function updateClock() {
                const serverTimezone = "{{ config('app.timezone') }}"; // Laravel timezone
                const now = luxon.DateTime.now().setZone(serverTimezone);

                $(".navbar-clock #date").text(now.toFormat("EEEE, MMMM dd, yyyy"));
                $(".navbar-clock #time").text(now.toFormat("hh:mm:ss a"));
                $(".navbar-clock #timezone").text(now.toFormat("ZZZZ"));
            }

            setInterval(updateClock, 1000);
            updateClock();
        });
    </script>

    <!-- Page JS -->
    @yield('custom-page-scripts')
  </body>
</html>
