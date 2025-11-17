@extends('layout.master')

@section('page-title', config('app.name') . ' - Lead Show')

@section('custom-page-style')
    <style>
        #preview-profile-image {
            width: 50%;
            height: auto;
            object-fit: cover;
            /* max-width: 50%; */
        }
    </style>
@endsection

@section('page-content')
    <div class="content-wrapper">
        <!-- Content -->
        <div class="container-xxl flex-grow-1 container-p-y">
            <div class="row">
                <div class="col-lg-12 mb-4 order-0">
                    <div class="card">
                        <div class="d-flex align-items-end row">
                            <div class="col-sm-12">
                                <!--  -->
                                <div class="card-body">
                                    <div class="row">
                                        <h5 class="fw-bolder fs-3 card-title text-primary mb-4 col-4">Account Details
                                        </h5>
                                        <a href="{{ route('index') }}"
                                            class="col-4 mb-4 btn btn-primary w-auto me-2 ms-auto">Back</a>
                                    </div>
                                    <div class="row">
                                        <div class="col-6">
                                            <form id="admin-profile-update-form" action="{{ route('admin.profile.update') }}"
                                                method="POST" enctype="multipart/form-data">
                                                @csrf
                                                <h6 class="fs-5 text-secondary border-bottom pb-3 mt-0 mb-4">Profile Information
                                                </h6>
                                                <div class="row mb-3">
                                                    <label class="col-4 fw-bold" for="full-name">Full Name:</label>
                                                    <span class="col">
                                                        <input name="name" value="{{ $user->name ?? '' }}" class="form-control"
                                                            id="full-name" placeholder="Enter Full Name" />
                                                        @error('name')
                                                            <div class="error">{{ $message }}</div>
                                                        @enderror
                                                    </span>
                                                </div>

                                                <div class="row mb-3">
                                                    <label class="col-4 fw-bold" for="admin-email">Email Address:</label>
                                                    <span class="col">
                                                        <input name="email" value="{{ $user->email ?? '' }}" class="form-control"
                                                            id="admin-email" placeholder="Enter Email Id" readonly />
                                                    </span>
                                                </div>

                                                <div class="row mb-3">
                                                    <!-- <div class="col-sm-2"></div> -->

                                                    <label class="col-4 fw-bold" for="profile-image">Profile
                                                        Picture:</label>
                                                    <!-- <span class="col-3"><input name="email" value="{{ $user->email ?? '' }}" class="form-control" readonly /></span> -->
                                                    <div class="col">
                                                        <div class="input-group mb-3">
                                                            <input type="file" class="form-control" name="profile_image"
                                                                id="profile-image" accept="image/jpeg, image/png, image/webp"
                                                                onchange="readURL(this);">
                                                            <span class="error d-none" id="profile-image-error"></span>
                                                        </div>
                                                        <img id="preview-profile-image"
                                                            src="{{ isset(Auth::user()->profile_image_url) ? Auth::user()->profile_image_url : asset('img/avatars/admin.png') }}"
                                                            alt="Profile Image" />

                                                    </div>
                                                    <div class="row mb-2 pb-4 mt-3">
                                                        <div class="col-4"></div>
                                                        <div class="col-6">
                                                            <button class="btn btn-primary w-100" type="submit">Update Account Details</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                        <div class="col-6">
                                            <form id="admin-password-reset-form">
                                                <h6 class="fs-5 text-secondary border-bottom pb-3 mb-4">Security Settings</h6>
                                                <div class="row mb-3">
                                                    <label class="col-5 fw-bold" for="two_fa_enabled_checkbox">Two-Factor Authentication (2FA):</label>
                                                    <div class="form-check form-switch mb-2 mx-3 col">
                                                        <input class="form-check-input" type="checkbox" name="two_fa_enabled"
                                                            id="two_fa_enabled_checkbox" disabled {{ $user->two_fa_enabled ? 'checked' : '' }}>
                                                    </div>
                                                </div>

                                                @if($user->two_fa_enabled)
                                                    <div class="row mb-3">
                                                        <label class="col-5 fw-bold">2FA Verification Method:</label>
                                                        <span
                                                            class="col">{{ ucfirst($user->two_fa_method ?? 'N/A') }}</span>
                                                    </div>
                                                @endif
                                                <div class="row mb-3">
                                                    <label class="col-5 fw-bold">New Password:</label>
                                                    <div class="col-5 position-relative">
                                                        <input type="password" id="password" name="password" class="form-control" placeholder="Enter Password" />
                                                        <span class="cursor-pointer pass-icon" id="togglePassword">
                                                            <i class="bx bx-hide" id="eyeIcon"></i>
                                                        </span>
                                                    </div>
                                                </div>

                                                <div class="row mb-3">
                                                    <label class="col-5 fw-bold">Confirm Password:</label>
                                                    <div class="col-5 position-relative">
                                                        <input type="password" id="confirmPassword" name="password_confirmation" class="form-control" placeholder="Enter Confirm Password" />
                                                        <span class="cursor-pointer pass-icon" id="toggleConfirmPassword">
                                                            <i class="bx bx-hide" id="eyeIconConfirm"></i>
                                                        </span>
                                                    </div>
                                                </div>

                                                <div class="row my-3">
                                                    <div class="col-5 fw-bold"></div>
                                                    <div class="col-5">
                                                        <button id="generate-code" class="btn btn-primary w-100 " type="button"
                                                            style="width:fit-content;">Generate Authentication Code</button>
                                                    </div>
                                                </div>

                                                <div class="row mb-3 d-none" id="step-2">
                                                    <label class="col-5 fw-bold">Enter 2FA Code:</label>
                                                    <div class="col-5">
                                                        <input type="text" id="otp-input" class="form-control" placeholder="Enter 2FA Code" autofocus />
                                                        <input type="hidden" id="code" name="code"   />
                                                    </div>
                                                    <div class="row mt-3">
                                                    <label class="col-5 fw-bold"></label>
                                                        <div class="col-5 ms-2">
                                                            <button class="btn btn-primary mb-2 w-100" type="button"
                                                                id="resend-code">Send Code Again</button>
                                                            <button class="btn btn-primary w-100" type="submit"
                                                                id="update-password">Update Password</button>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-5 fw-bold"></div>
                                                    <div class="col-5 password-reset-server-errors d-none">
                                                        <label class="error">Enter 2FA Code:</label>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                <!--  -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- / Content -->

            <!-- Footer -->
            @include('layout.footer')
            <!-- / Footer -->

            <div class="content-backdrop fade"></div>
        </div>
@endsection

@section('custom-page-scripts')
<script src="{{ asset('js/dashboards-analytics.js') }}?v={{ currentVersion() }}"></script>
<script>
    function readURL(input) {
        console.log("input : ", input.files);
        if (input.files && input.files[0]) {
            const file = input.files[0];
            const allowedTypes = ["image/jpeg", "image/png", "image/webp"]; // No GIFs

            console.log("AAAA", file.type);
            if (!allowedTypes.includes(file.type)) {
                // alert("Only static images (JPEG, PNG, WEBP) are allowed. GIFs are not permitted.");
                $("#profile-image-error")
                .removeClass("d-none")
                .text("Only static images (JPEG, PNG, WEBP) are allowed. GIFs are not permitted.");

                input.value = ""; // Clear the input
                return;
            }

            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('preview-profile-image').src = e.target.result; // Assuming there's an image preview element
            };
            reader.readAsDataURL(file);
        }
    }

    $(document).ready(function () {
        $('.menu-item').removeClass('active');

        $("#otp-input").inputmask({
            mask: "9 9 9 9 9 9", // Mask format with spaces
            placeholder: "-",
            showMaskOnHover: false,
            showMaskOnFocus: true,
            oncomplete: function () {
                $("#code").val(this.value.replace(/\s/g, "")); // Store raw OTP
            }
        });

        $("#admin-password-reset-form").validate({
            rules: {
                password: {
                    required: true,
                    minlength: 8
                },
                password_confirmation: {
                    required: true,
                    minlength: 8,
                    equalTo: "[name='password']"
                },
                code: {
                    required: true,
                    digits: true,
                    minlength: 6,
                    maxlength: 6
                }
            },
            messages: {
                password: {
                    required: "Please enter your password.",
                    minlength: "Password must be at least 8 characters long."
                },
                password_confirmation: {
                    required: "Please confirm your password.",
                    minlength: "Password must be at least 8 characters long.",
                    equalTo: "Passwords do not match."
                },
                code: {
                    required: "Please enter the verification code.",
                    digits: "Code must be numeric.",
                    minlength: "Code must be 6 digits long.",
                    maxlength: "Code must be 6 digits long."
                }
            },
            errorPlacement: function (error, element) {
                error.insertAfter(element);
            }
        });

        // Handle "Generate Code" & "Resend Code"
        $("#resend-code, #generate-code").click(function () {
	    $("#admin-password-reset-form input[name='code']").rules("remove");
            $(".password-reset-server-errors").addClass("d-none"); // Hide previous errors
            $("#update-password").addClass("d-none");

            if ($("#admin-password-reset-form").valid()) {
                $('#preloader').show();
                $.ajax({
                    url: "{{ route('admin.generate-password-update-token') }}",
                    type: "POST",
                    data: {
                        password: $("[name='password']").val(),
                        password_confirmation: $("[name='password_confirmation']").val(),
                        _token: "{{ csrf_token() }}"
                    },
                    success: function (response) {
                        $('#preloader').hide();
                        toastr.success(response.message);
                        $("#admin-password-reset-form input[name='password'], #admin-password-reset-form input[name='password_confirmation']").prop("disabled", true);
                        $("#step-2").removeClass("d-none"); // Show 2FA Code input
                        $("#generate-code").parent().addClass("d-none");
                        $("#update-password").removeClass("d-none");
                    },
                    error: function (xhr) {
                        $('#preloader').hide();
                        $(".password-reset-server-errors label").text(xhr.responseJSON.message);
                        $(".password-reset-server-errors").removeClass("d-none");
                        toastr.error(xhr.responseJSON.message);
                    }
                });
            }

	    // Re-add validation to "code" field
            $("#admin-password-reset-form input[name='code']").rules("add", {
                required: true,
                digits: true,
                minlength: 6,
                maxlength: 6,
                messages: {
                    required: "Please enter the verification code.",
                    digits: "Code must be numeric.",
                    minlength: "Code must be 6 digits long.",
                    maxlength: "Code must be 6 digits long."
                }
            });
        });

        // Handle "Update Password" Click
        $("#admin-password-reset-form").submit(function (e) {
            e.preventDefault();
            $(".password-reset-server-errors").addClass("d-none"); // Hide previous errors

            if ($("#admin-password-reset-form").valid()) {
                $('#preloader').show();
                $.ajax({
                    url: "{{ route('admin.update-admin-password') }}",
                    type: "POST",
                    data: {
                        password: $("#admin-password-reset-form input[name='password']").val(),
                        password_confirmation: $("#admin-password-reset-form input[name='password_confirmation']").val(),
                        code: $("#admin-password-reset-form input[name='code']").val(),
                        _token: "{{ csrf_token() }}"
                    },
                    success: function (response) {
                        $('#preloader').hide();
                        toastr.success(response.message);
                        setTimeout(() => {
                            window.location.replace("{{ route('login') }}"); // Redirect to login page
                        }, 2000);
                    },
                    error: function (xhr) {
                        $('#preloader').hide();
                        $(".password-reset-server-errors label").text(xhr.responseJSON.message);
                        $(".password-reset-server-errors").removeClass("d-none");
                        toastr.error(xhr.responseJSON.message);
                    }
                });
            }
        });

        $("#two_fa_enabled_checkbox").change(function () {
            let isChecked = $(this).prop("checked");
            let title = isChecked ? "Enable Two-Factor Authentication?" : "Disable Two-Factor Authentication?";
            let text = isChecked
                ? "For added security, we recommend keeping 2FA enabled."
                : "Disabling 2FA may reduce your account security.";

            Swal.fire({
                title: title,
                text: text,
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Yes, proceed",
                cancelButtonText: "Cancel",
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ route('admin.profile.update-two-factor-authentication') }}",
                        type: "POST",
                        data: {
                            _token: "{{ csrf_token() }}",
                            two_fa_enabled: isChecked ? 1 : 0,
                        },
                        success: function (response) {
                            toastr.success(response.message);
                        },
                        error: function (xhr) {
                            let errorMessage = xhr.responseJSON ? xhr.responseJSON.message : "Something went wrong!";
                            toastr.error(errorMessage);

                            // Revert the toggle state in case of an error
                            $("#two_fa_enabled_checkbox").prop("checked", !isChecked);
                        },
                    });
                } else {
                    // Revert toggle if user cancels the confirmation
                    $("#two_fa_enabled_checkbox").prop("checked", !isChecked);
                }
            });
        });
    });

    function togglePasswordVisibility(inputId, iconId) {
        let passwordInput = document.getElementById(inputId);
        let eyeIcon = document.getElementById(iconId);

        if (passwordInput.type === "password") {
            passwordInput.type = "text";
            eyeIcon.classList.remove("bx-hide");
            eyeIcon.classList.add("bx-show");
        } else {
            passwordInput.type = "password";
            eyeIcon.classList.remove("bx-show");
            eyeIcon.classList.add("bx-hide");
        }
    }

    document.getElementById("togglePassword").addEventListener("click", function () {
        togglePasswordVisibility("password", "eyeIcon");
    });

    document.getElementById("toggleConfirmPassword").addEventListener("click", function () {
        togglePasswordVisibility("confirmPassword", "eyeIconConfirm");
    });
</script>
@endsection