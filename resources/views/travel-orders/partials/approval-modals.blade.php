<!-- Hidden forms for approval/rejection actions -->
<form id="approveForm" method="POST" style="display: none;">
    @csrf
    @method('PUT')
</form>

<form id="rejectForm" method="POST" style="display: none;">
    @csrf
    @method('PUT')
</form>

<form id="signForm" method="POST" style="display: none;">
    @csrf
    @method('PUT')
</form>

<form id="rejectSignForm" method="POST" style="display: none;">
    @csrf
    @method('PUT')
</form>

<script>
    // Show Approve Modal (Simple Confirmation)
    function showApproveModal(travelOrderId, employeeName) {
        Swal.fire({
            title: 'Approve Travel Order?',
            html: 'Are you sure you want to approve this travel order?<br><strong>Employee:</strong> ' + employeeName,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#1e6031',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Yes, Approve',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('approveForm').action = '/employee/travel-orders/approvals/' + travelOrderId + '/approve';
                document.getElementById('approveForm').submit();
            }
        });
    }

    // Show Reject Modal (With Reason Input)
    function showRejectModal(travelOrderId, employeeName) {
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
                // Add the reason as a hidden input
                const form = document.getElementById('rejectForm');
                form.action = '/employee/travel-orders/approvals/' + travelOrderId + '/reject';
                
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

    // Show Sign Modal (Approver 2 Approval)
    function showSignModal(travelOrderId, employeeName) {
        Swal.fire({
            title: 'Approve as Approver 2?',
            html: 'Are you sure you want to approve this travel order as Approver 2?<br><strong>Employee:</strong> ' + employeeName + '<br><br>This will finalize the approval process.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#1e6031',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Yes, Approve',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('signForm').action = '/employee/travel-orders/approvals/' + travelOrderId + '/sign';
                document.getElementById('signForm').submit();
            }
        });
    }

    // Show Reject Sign Modal (Approver 2 Rejection with Reason)
    function showRejectSignModal(travelOrderId, employeeName) {
        Swal.fire({
            title: '<span style="color: #dc3545;"><i class="fas fa-exclamation-triangle"></i> Reject Approval?</span>',
            html: '<div style="text-align: left;">' +
                  '<div style="background-color: #f8f9fa; padding: 12px; border-radius: 8px; margin-bottom: 15px;">' +
                  '<p style="margin: 0 0 8px 0; color: #6c757d; font-size: 14px;">Employee:</p>' +
                  '<p style="margin: 0; font-weight: 600; color: #212529; font-size: 15px;">' + employeeName + '</p>' +
                  '</div>' +
                  '<div style="margin-bottom: 12px;">' +
                  '<label style="display: block; margin-bottom: 8px; color: #495057; font-weight: 500; font-size: 14px;">' +
                  '<span style="color: #dc3545;">*</span> Reason for Rejection' +
                  '</label>' +
                  '<textarea id="rejectSignReason" class="swal2-textarea" rows="5" ' +
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
                const reason = document.getElementById('rejectSignReason').value;
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
                // Add the reason as a hidden input
                const form = document.getElementById('rejectSignForm');
                form.action = '/employee/travel-orders/approvals/' + travelOrderId + '/reject-sign';
                
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
