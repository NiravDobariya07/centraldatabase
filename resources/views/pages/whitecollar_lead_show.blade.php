@extends('layout.master')

@section('page-title', config('app.name') . ' - WhiteCollar Lead Show')

@section('custom-page-style')
    <style>
        .view-cake-response-btn {
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            padding: 0;
            border-radius: 50%;
            background-color: #0dcaf0;
            border: 2px solid #0dcaf0;
            color: white;
            margin: 0;
            transition: all 0.2s ease;
        }

        .view-cake-response-btn:hover {
            background-color: #0aa2c0;
            border-color: #0aa2c0;
            color: white;
            transform: scale(1.05);
        }

        .view-cake-response-btn:focus {
            box-shadow: 0 0 0 0.2rem rgba(13, 202, 240, 0.25);
        }

        .view-cake-response-btn i {
            font-size: 14px;
            font-weight: bold;
            line-height: 1;
        }

        #cake-response-content {
            max-height: 70vh;
            overflow-y: auto;
            overflow-x: hidden;
            width: 100%;
            word-break: break-all;
            overflow-wrap: break-word;
        }

        #cake-response-content pre {
            white-space: pre;
            font-family: monospace;
            font-size: 0.9rem;
            line-height: 1.6;
            margin: 0;
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
                    <div class="row justify-content-between">
                        <h5 class="card-title text-primary mb-4 col-4">WhiteCollar Lead Details</h5>
                        <a href="{{ route('whitecollar-leads.index') }}" class="col-4 mb-4 btn btn-primary w-auto">Back</a>
                    </div>
                    @foreach ($leadDetails as $index => $leadDetailsGroup)
                        <div class="row mb-2">
                        @foreach ($leadDetailsGroup as $section => $fields)
                            <div class="col-6 pe-5">
                            <h5 class="text-secondary border-bottom pb-2 mt-4 fw-bold">{{ $section }}</h5>
                            @foreach ($fields as $label => $value)
                                <div class="row mb-2">
                                    <label class="col-sm-4 fw-bold">{{ $label }}:</label>
                                    <span class="col">
                                        @if ($label === 'Cake Response' || $label === 'Fetch Paid Response')
                                            @php
                                                $responseData = $lead->fetch_paid_response ?? '';
                                                $encodedResponse = $responseData ? base64_encode($responseData) : '';
                                            @endphp
                                            <div style="text-align: center; display: inline-block;">
                                                <button type="button" class="btn btn-sm btn-outline-info view-cake-response-btn"
                                                    data-response-encoded="{{ $encodedResponse }}"
                                                    data-lead-id="{{ $lead->id }}"
                                                    title="View Cake Response">
                                                    <i class="bx bx-info-circle"></i>
                                                </button>
                                            </div>
                                        @elseif (!empty($value))
                                            @if (in_array($label, ['Email', 'Email Address']))
                                                <a href="mailto:{{ $value }}">{{ $value }}</a>
                                            @elseif (in_array($label, ['Phone', 'Alt Phone']))
                                                <a href="tel:{{ $value }}">{{ $value }}</a>
                                            @elseif (in_array($label, ['Page URL']))
                                            <a href="{{ $value }}" target="_blank" rel="noopener noreferrer">{{ $value }}</a>
                                            @elseif (in_array($label, ['Date of Birth', 'Date Subscribed', 'Import Date', 'Created At', 'Updated At', 'Lead Timestamp', 'Ongage At']))
                                                @if (\Carbon\Carbon::parse($value)->isValid())
                                                    {{ \Carbon\Carbon::parse($value)->format('d M Y, h:i A') }}
                                                @else
                                                    {{ $value }}
                                                @endif
                                            @elseif (in_array($label, ['Tax Debt Amount', 'Credit Card Debt', 'Payout Paid']))
                                                {{ formatCurrency($value) }}
                                            @else
                                                {{ $value }}
                                            @endif

                                            {{-- Copy icon for specific sections --}}
                                            @if(in_array($section, ['Identifiers', 'Sub IDs & Affiliate IDs']))
                                                <button type="button" class="btn btn-sm px-2" onclick="copyToClipboard('{{ $value }}')">
                                                    <i class="menu-icon tf-icons bx bx-copy text-primary"></i>
                                                </button>
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

    <!-- Cake Response Modal -->
    <div class="modal fade" id="cake-response-modal" tabindex="-1" aria-labelledby="cakeResponseModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header border border-bottom-4 p-3">
                    <h5 class="modal-title" id="cakeResponseModalLabel">XML Response</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Response Data:</label>
                        <div id="cake-response-content" class="bg-light p-3 rounded"></div>
                    </div>
                </div>
                <div class="modal-footer border border-top-2 p-3">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="copyCakeResponse()">Copy to Clipboard</button>
                </div>
            </div>
        </div>
    </div>
    <!-- ./Cake Response Modal -->
@endsection


@section('custom-page-scripts')
<script src="{{ asset('js/dashboards-analytics.js') }}?v={{ currentVersion() }}"></script>
<script src="{{ asset('vendor/js/jquery-3.6.0.min.js') }}?v={{ currentVersion() }}"></script>
<script>
    $(document).ready(function () {
        $('.menu-item').removeClass('active');
        $('.menu-item-whitecollar-leads').addClass('active');
    });

    // Function to format XML for display
    function formatXMLForDisplay(xmlString) {
        if (!xmlString || xmlString.trim() === '') {
            return '';
        }

        try {
            // Unescape any escaped characters from database
            xmlString = xmlString.replace(/\\"/g, '"')
                                .replace(/\\\//g, '/')
                                .replace(/\\r\\n/g, '\n')
                                .replace(/\\n/g, '\n')
                                .replace(/\\t/g, '\t');

            // Check if already formatted (has proper indentation)
            if (xmlString.indexOf('\n') > -1 && (xmlString.indexOf('  <') > -1 || xmlString.indexOf('\r\n  <') > -1)) {
                // Already formatted, just normalize line endings
                return xmlString.replace(/\r\n/g, '\n').replace(/\r/g, '\n');
            }

            // Need to format it - use DOMParser
            var parser = new DOMParser();
            var xmlDoc = parser.parseFromString(xmlString.replace(/>\s+</g, '><').trim(), 'text/xml');

            // Check for parsing errors
            if (xmlDoc.getElementsByTagName('parsererror').length > 0) {
                // If parsing fails, return as-is
                return xmlString;
            }

            // Format using DOM tree (2 spaces indentation)
            return formatXMLFromDOM(xmlDoc, 0);

        } catch (e) {
            console.error('Error formatting XML:', e);
            return xmlString;
        }
    }

    // Format XML from DOM tree
    function formatXMLFromDOM(node, indent) {
        var formatted = '';
        var tab = '  '; // 2 spaces
        var indentStr = '';
        for (var i = 0; i < indent; i++) {
            indentStr += tab;
        }

        if (node.nodeType === 1) { // Element node
            formatted += indentStr + '<' + node.nodeName;

            // Add attributes
            if (node.attributes && node.attributes.length > 0) {
                for (var j = 0; j < node.attributes.length; j++) {
                    var attr = node.attributes[j];
                    formatted += ' ' + attr.name + '="' + attr.value + '"';
                }
            }

            // Check children
            var hasElementChildren = false;
            var textContent = '';

            for (var k = 0; k < node.childNodes.length; k++) {
                var child = node.childNodes[k];
                if (child.nodeType === 1) {
                    hasElementChildren = true;
                    break;
                } else if (child.nodeType === 3) {
                    var text = child.textContent.trim();
                    if (text) {
                        textContent += text;
                    }
                }
            }

            if (hasElementChildren) {
                // Has child elements
                formatted += '>\n';
                for (var l = 0; l < node.childNodes.length; l++) {
                    var childNode = node.childNodes[l];
                    if (childNode.nodeType === 1) {
                        formatted += formatXMLFromDOM(childNode, indent + 1);
                    } else if (childNode.nodeType === 3 && childNode.textContent.trim()) {
                        formatted += indentStr + tab + childNode.textContent.trim() + '\n';
                    }
                }
                formatted += indentStr + '</' + node.nodeName + '>\n';
            } else if (textContent) {
                // Has only text content
                formatted += '>' + textContent + '</' + node.nodeName + '>\n';
            } else {
                // Empty element - self-closing
                formatted += ' />\n';
            }
        }

        return formatted;
    }

    // Handle click on Cake Response icon
    $(document).on('click', '.view-cake-response-btn', function() {
        var encodedData = $(this).attr('data-response-encoded');
        var response = '';

        try {
            // Decode from base64
            if (encodedData && encodedData !== '') {
                try {
                    response = decodeURIComponent(escape(atob(encodedData)));
                } catch (e) {
                    // If base64 decode fails, try direct decode
                    try {
                        response = atob(encodedData);
                    } catch (e2) {
                        console.error('Error decoding base64:', e2);
                        response = '';
                    }
                }
            }
        } catch (e) {
            console.error('Error processing response:', e, 'Raw data:', encodedData);
            response = '';
        }

        // If no response data, show message
        if (!response || response === '' || response.trim() === '') {
            $('#cake-response-content').html('<pre style="margin: 0; white-space: pre; font-family: monospace; font-size: 0.9rem;">No response data available.</pre>');
        } else {
            // Format XML with proper indentation
            var formattedXML = formatXMLForDisplay(response);
            // Escape HTML entities and wrap in <pre>
            var escapedXmlContent = formattedXML.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
            var formattedXmlContent = '<pre style="margin: 0; white-space: pre; font-family: monospace; font-size: 0.9rem; line-height: 1.6;">' + escapedXmlContent + '</pre>';
            $('#cake-response-content').html(formattedXmlContent);
        }

        $('#cake-response-modal').modal('show');
    });

    // Copy Cake Response to clipboard
    function copyCakeResponse() {
        var content = $('#cake-response-content pre').text();
        if (!content) {
            content = $('#cake-response-content').text();
        }

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(content).then(function() {
                alert('Response data copied to clipboard!');
            }, function(err) {
                console.error('Failed to copy:', err);
                fallbackCopyTextToClipboard(content);
            });
        } else {
            fallbackCopyTextToClipboard(content);
        }
    }

    function fallbackCopyTextToClipboard(text) {
        var textArea = document.createElement("textarea");
        textArea.value = text;
        textArea.style.top = "0";
        textArea.style.left = "0";
        textArea.style.position = "fixed";
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        try {
            var successful = document.execCommand('copy');
            if (successful) {
                alert('Response data copied to clipboard!');
            } else {
                alert('Failed to copy to clipboard');
            }
        } catch (err) {
            console.error('Fallback: Oops, unable to copy', err);
            alert('Failed to copy to clipboard');
        }
        document.body.removeChild(textArea);
    }
</script>
@endsection
