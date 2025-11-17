<script src="{{ asset('vendor/libs/jquery/jquery.js') }}?v={{ currentVersion() }}"></script>
<script src="{{ asset('vendor/libs/jquery/jquery.validate.min.js') }}?v={{ currentVersion() }}"></script>
<script src="{{ asset('vendor/libs/popper/popper.js') }}?v={{ currentVersion() }}"></script>
<script src="{{ asset('vendor/js/bootstrap.js') }}?v={{ currentVersion() }}"></script>
<script src="{{ asset('vendor/libs/perfect-scrollbar/perfect-scrollbar.js') }}?v={{ currentVersion() }}"></script>
<script src="{{ asset('vendor/js/toastr.min.js') }}?v={{ currentVersion() }}"></script>
<script src="{{ asset('vendor/js/sweetalert2.all.min.js') }}?v={{ currentVersion() }}"></script>
<script src="{{ asset('vendor/js/jquery.inputmask.min.js') }}?v={{ currentVersion() }}"></script>
<script src="{{ asset('vendor/js/jquery.dataTables.min.js') }}?v={{ currentVersion() }}"></script>
<script src="{{ asset('vendor/js/dataTables.bootstrap5.min.js') }}?v={{ currentVersion() }}"></script>
<script src="{{ asset('vendor/js/jquery-ui.min.js') }}?v={{ currentVersion() }}"></script>
<script type="text/javascript" src="{{ asset('vendor/js/moment.min.js') }}?v={{ currentVersion() }}"></script>
<script type="text/javascript" src="{{ asset('vendor/js/moment-timezone-with-data.min.js') }}?v={{ currentVersion() }}"></script>

<script src="{{ asset('vendor/js/menu.js') }}?v={{ currentVersion() }}"></script>

<script>
    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(() => {
            let toastEl = document.getElementById('copyToast');
            let toast = new bootstrap.Toast(toastEl, { delay: 1500 }); // Auto-hide in 2 seconds
            toast.show();
        }).catch(err => {
            console.error("Failed to copy!", err);
        });
    }

    $(document).ready(function() {
        @if(session('success'))
            toastr.success(@json(session('success')));
        @endif

        @if(session('error'))
            toastr.error(@json(session('error')));
        @endif
    });
</script>
