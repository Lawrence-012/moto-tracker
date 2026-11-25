<!-- Hidden forms for SweetAlert actions -->
<form id="approveForm" method="POST" style="display: none;">
    @csrf
    @method('PUT')
</form>

<form id="rejectForm" method="POST" style="display: none;">
    @csrf
    @method('PUT')
</form>

<script>
    // Show Approve Modal (Simple Confirmation)
    function showApproveModal(travelOrderId, employeeName) {
        // Detect the current page context from the URL
        var isPresident = window.location.pathname.includes('president');
        var isDivisionHead = window.location.pathname.includes('divisionhead');
        var isVp = window.location.pathname.includes('vp');
        var isHead = window.location.pathname.includes('head');
        
        // Build the correct route based on context
        var actionUrl = '/travel-orders/' + travelOrderId + '/reject';
        if (isPresident) {
            actionUrl = '/travel-orders/' + travelOrderId + '/approve/president';
        } else if (isDivisionHead) {
            actionUrl = '/travel-orders/' + travelOrderId + '/approve/divisionhead';
        } else if (isVp) {
            actionUrl = '/travel-orders/' + travelOrderId + '/approve/vp';
        } else if (isHead) {
            actionUrl = '/travel-orders/' + travelOrderId + '/approve/head';
        } else {
            actionUrl = '/employee/travel-orders/approvals/' + travelOrderId + '/approve';
        }
        
        Swal.fire({
            title: 'Approve Travel Order?',
            html: '<div style="text-align: left;">' +
                  '<div style="background-color: #f8f9fa; padding: 10px; border-radius: 6px; margin-bottom: 12px;">' +
                  '<p style="margin: 0 0 6px 0; color: #6c757d; font-size: 12px;">Employee:</p>' +
                  '<p style="margin: 0; font-weight: 600; color: #212529; font-size: 13px;">' + employeeName + '</p>' +
                  '</div>' +
                  '<p style="margin: 0; color: #6c757d; font-size: 12px;">Are you sure you want to approve this travel order?</p>' +
                  '</div>',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#1e6031',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Yes, Approve',
            cancelButtonText: 'Cancel',
            buttonsStyling: true,
            width: '450px',
            customClass: {
                popup: 'rounded-lg',
                confirmButton: 'btn btn-success px-3 py-2 text-xs',
                cancelButton: 'btn btn-secondary px-3 py-2 text-xs'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                // Show loading SweetAlert for 2 seconds
                Swal.fire({
                    title: 'Processing...',
                    html: 'Approving travel order',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                // Make AJAX request to approve
                fetch(actionUrl, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    credentials: 'same-origin'
                })
                .then(response => {
                    // Try to parse as JSON, but handle redirects gracefully
                    const contentType = response.headers.get('content-type');
                    if (contentType && contentType.includes('application/json')) {
                        return response.json();
                    } else {
                        // If we get HTML instead of JSON, it might be a redirect or error page
                        throw new Error('Received non-JSON response. The server may have redirected.');
                    }
                })
                .then(data => {
                    // Hide loading and show success message after 2 seconds
                    setTimeout(() => {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: data.message || 'Travel order approved successfully.',
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            // Redirect to approved tab using URL from response
                            if (data.redirect_url) {
                                window.location.href = data.redirect_url;
                            } else {
                                window.location.reload();
                            }
                        });
                    }, 2000);
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'Failed to approve travel order. Please try again.'
                    });
                });
            }
        });
    }

    // Show Reject Modal (With Reason Input)
    function showRejectModal(travelOrderId, employeeName) {
        // Detect the current page context from the URL
        var isPresident = window.location.pathname.includes('president');
        var isDivisionHead = window.location.pathname.includes('divisionhead');
        var isVp = window.location.pathname.includes('vp');
        var isHead = window.location.pathname.includes('head');
        
        // Build the correct route based on context
        var actionUrl = '/travel-orders/' + travelOrderId + '/reject';
        if (isPresident) {
            actionUrl += '/president';
        } else if (isDivisionHead) {
            actionUrl += '/divisionhead';
        } else if (isVp) {
            actionUrl += '/vp';
        } else if (isHead) {
            actionUrl += '/head';
        } else {
            actionUrl = '/employee/travel-orders/approvals/' + travelOrderId + '/reject';
        }
        
        Swal.fire({
            title: '<span style="color: #dc3545;"><i class="fas fa-exclamation-triangle"></i> Reject Travel Order?</span>',
            html: '<div style="text-align: left;">' +
                  '<div style="background-color: #f8f9fa; padding: 12px; border-radius: 8px; margin-bottom: 15px;">' +
                  '<p style="margin: 0 0 8px 0; color: #6c757d; font-size: 14px;">Employee:</p>' +
                  '<p style="margin: 0; font-weight: 600; color: #212529; font-size: 15px;">' + employeeName + '</p>' +
                  '</div>' +
                  '<div style="margin-bottom: 12px;">' +
                  '<label style="display: block; margin-bottom: 8px; color: #495057; font-weight: 500; font-size: 14px;">' +
                  '<span style="color: #dc3545;">*</span> Reason for Rejection' +
                  '</label>' +
                  '<textarea id="rejectReason" class="swal2-textarea" rows="5" ' +
                  'placeholder="Please provide a detailed explanation for rejecting this travel order..." ' +
                  'style="width: 100%; min-height: 120px; resize: vertical; border: 2px solid #dee2e6; border-radius: 6px; padding: 12px; font-size: 14px; line-height: 1.5;" ' +
                  'onfocus="this.style.borderColor=\'#1e6031\'" onblur="this.style.borderColor=\'#dee2e6\'"></textarea>' +
                  '</div>' +
                  '<div style="background-color: #fff3cd; padding: 10px; border-radius: 6px; border-left: 3px solid #ffc107;">' +
                  '<p style="margin: 0; color: #856404; font-size: 13px;">' +
                  '<i class="fas fa-info-circle"></i> This reason will be sent to the employee for their reference.' +
                  '</p>' +
                  '</div>' +
                  '</div>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Reject It',
            cancelButtonText: 'Cancel',
            reverseButtons: true,
            width: '600px',
            customClass: {
                popup: 'rounded-lg shadow-xl',
                title: 'text-center pb-2',
                content: 'text-left'
            },
            preConfirm: () => {
                const reason = document.getElementById('rejectReason').value;
                if (!reason || reason.trim() === '') {
                    Swal.showValidationMessage('<i class="fas fa-exclamation-circle"></i> Please provide a reason for rejection');
                    return false;
                }
                if (reason.trim().length < 10) {
                    Swal.showValidationMessage('<i class="fas fa-exclamation-circle"></i> Reason must be at least 10 characters long');
                    return false;
                }
                return reason;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.getElementById('rejectForm');
                form.action = actionUrl;
                
                // Remove existing remarks field if any
                const existingRemarks = form.querySelector('input[name="remarks"]');
                if (existingRemarks) {
                    existingRemarks.remove();
                }
                
                // Create hidden input for remarks
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'remarks';
                hiddenInput.value = result.value;
                form.appendChild(hiddenInput);
                
                form.submit();
            }
        });
    }
</script>
