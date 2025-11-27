@extends('layout.master')

@section('page-title', config('app.name') . ' - TRA Lead Show')

@section('page-content')
<div class="content-wrapper">
    <!-- Content -->
    <div class="container-xxl flex-grow-1 container-p-y">
      <div class="row">
        <div class="col-lg-12 mb-4 order-0">
          <div class="card">
            <div class="d-flex align-items-end row">
              <div class="col-sm-12">
                <div class="card-body">
                    <div class="row justify-content-between">
                        <h5 class="card-title text-primary mb-4 col-4">TRA Lead Details</h5>
                        <a href="{{ route('tra-contacts.index') }}" class="col-4 mb-4 btn btn-primary w-auto">Back</a>
                    </div>
                    @foreach ($contactDetails as $index => $contactDetailsGroup)
                        <div class="row mb-2">
                        @foreach ($contactDetailsGroup as $section => $fields)
                            <div class="col-6 pe-5">
                            <h5 class="text-secondary border-bottom pb-2 mt-4 fw-bold">{{ $section }}</h5>
                            @foreach ($fields as $label => $value)
                                <div class="row mb-2">
                                    <label class="col-sm-4 fw-bold">{{ $label }}:</label>
                                    <span class="col">
                                        @if (!empty($value) && $value !== 'N/A')
                                            @if (in_array($label, ['Email']))
                                                <a href="mailto:{{ $value }}">{{ $value }}</a>
                                            @elseif (in_array($label, ['Created At', 'Updated At', 'Lead Time Stamp']))
                                                @if (\Carbon\Carbon::parse($value)->isValid())
                                                    {{ \Carbon\Carbon::parse($value)->format('d M Y, h:i A') }}
                                                @else
                                                    {{ $value }}
                                                @endif
                                            @else
                                                {{ $value }}
                                            @endif
                                        @else
                                            N/A
                                        @endif
                                    </span>
                                </div>
                            @endforeach
                            </div>
                        @endforeach
                        </div>
                    @endforeach
                </div>
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
    $(document).ready(function () {
        $('.menu-item').removeClass('active');
        $('.menu-item-tra-contacts').addClass('active');
    });
</script>
@endsection

