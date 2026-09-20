<section>
    <header>
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-4 h-4 text-green-600">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                    </svg>
                </div>
            </div>
            <div class="ml-2">
                <h2 class="text-base font-medium text-gray-800">
                    {{ __('Update Password') }}
                </h2>
            </div>
        </div>
        <p class="mt-1 text-xs text-gray-600">
            {{ __('Ensure your account is using a long, random password to stay secure.') }}
        </p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="mt-4 space-y-4">
        @csrf
        @method('put')

        <div>
            <x-input-label for="update_password_current_password" :value="__('Current Password')" class="block text-xs font-medium text-gray-700 mb-1" />
            <x-text-input id="update_password_current_password" name="current_password" type="password" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 text-sm" autocomplete="current-password" />
            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-1" />
        </div>

        <div>
            <x-input-label for="update_password_password" :value="__('New Password')" class="block text-xs font-medium text-gray-700 mb-1" />
            <x-text-input id="update_password_password" name="password" type="password" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 text-sm" autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-1" />
        </div>

        <div>
            <x-input-label for="update_password_password_confirmation" :value="__('Confirm Password')" class="block text-xs font-medium text-gray-700 mb-1" />
            <x-text-input id="update_password_password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 text-sm" autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-1" />
        </div>

        <div class="flex items-center gap-3 pt-3">
            <button type="button" onclick="handlePasswordUpdate()" class="inline-flex items-center px-3 py-1.5 bg-green-600 border border-transparent rounded-md font-medium text-white text-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
                {{ __('Save') }}
            </button>

            @if (session('status') === 'password-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-xs text-green-600 font-medium"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>

<script>
// Add blur event to current password field for early validation
document.addEventListener('DOMContentLoaded', function() {
    const currentPasswordInput = document.getElementById('update_password_current_password');
    
    if (currentPasswordInput) {
        currentPasswordInput.addEventListener('blur', function() {
            // If field has value, we can't validate without submitting to server
            // but we can at least check if it's not empty
            if (!this.value.trim()) {
                showInlineError(this, 'Current Password is required.');
            } else {
                // Clear error if field has content
                clearInlineError(this);
            }
        });
    }
});

function handlePasswordUpdate() {
    // Get form elements
    const currentPasswordInput = document.getElementById('update_password_current_password');
    const newPasswordInput = document.getElementById('update_password_password');
    const confirmPasswordInput = document.getElementById('update_password_password_confirmation');
    
    // Clear previous error messages
    clearInlineErrors();
    
    // Get form data
    const form = document.querySelector('form[method="post"][action="{{ route('password.update') }}"]');
    const formData = new FormData(form);
    
    let hasErrors = false;
    
    // Validation
    if (!currentPasswordInput.value.trim()) {
        showInlineError(currentPasswordInput, 'Current Password is required.');
        hasErrors = true;
    }
    
    if (!newPasswordInput.value.trim()) {
        showInlineError(newPasswordInput, 'New Password is required.');
        hasErrors = true;
    }
    
    if (!confirmPasswordInput.value.trim()) {
        showInlineError(confirmPasswordInput, 'Confirm Password is required.');
        hasErrors = true;
    }
    
    if (newPasswordInput.value && confirmPasswordInput.value && newPasswordInput.value !== confirmPasswordInput.value) {
        showInlineError(confirmPasswordInput, 'Passwords do not match.');
        hasErrors = true;
    }
    
    // If there are validation errors, stop the process
    if (hasErrors) {
        return;
    }
    
    // Add the _method put to the form data
    formData.append('_method', 'PUT');
    
    // Perform final validation before submission
    let finalHasErrors = false;
    
    // Final check for current password
    if (!currentPasswordInput.value.trim()) {
        showInlineError(currentPasswordInput, 'Current Password is required.');
        finalHasErrors = true;
    }
    
    if (!newPasswordInput.value.trim()) {
        showInlineError(newPasswordInput, 'New Password is required.');
        finalHasErrors = true;
    }
    
    if (!confirmPasswordInput.value.trim()) {
        showInlineError(confirmPasswordInput, 'Confirm Password is required.');
        finalHasErrors = true;
    }
    
    if (newPasswordInput.value && confirmPasswordInput.value && newPasswordInput.value !== confirmPasswordInput.value) {
        showInlineError(confirmPasswordInput, 'Passwords do not match.');
        finalHasErrors = true;
    }
    
    // If there are validation errors, stop the process
    if (finalHasErrors) {
        // Show error notification with specific message for current password
        let errorMessage = 'Please fix the errors before continuing.';
        let errorTitle = 'Validation Error';
        
        // Check if current password field has an error
        const currentPasswordErrorElement = document.getElementById('update_password_current_password-error');
        if (currentPasswordErrorElement && currentPasswordErrorElement.textContent.includes('incorrect')) {
            errorMessage = 'Current password is incorrect.';
            errorTitle = 'Incorrect Password';
        }
        
        Swal.fire({
            icon: 'error',
            title: errorTitle,
            text: errorMessage
        });
        return;
    }

    // Show confirmation dialog
    Swal.fire({
        title: 'Confirm Password Change',
        text: 'Are you sure you want to update your password?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, update it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading alert
            Swal.fire({
                title: 'Updating...',
                text: 'Please wait while we update your password.',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Submit the form via AJAX
            fetch('{{ route('password.update') }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(async response => {
                const contentType = response.headers.get('content-type');
                
                if (contentType && contentType.includes('application/json')) {
                    // Response is JSON (validation errors or other JSON response)
                    const data = await response.json();
                    
                    if (response.status === 422) { // Validation errors
                        // Close the loading alert if it's open
                        Swal.close();
                        
                        // Show inline errors for each field
                        if (data.errors && data.errors.current_password) {
                            showInlineError(currentPasswordInput, data.errors.current_password[0] || 'Current password is incorrect.');
                        }
                        if (data.errors && data.errors.password) {
                            showInlineError(newPasswordInput, data.errors.password[0]);
                        }
                        if (data.errors && data.errors.password_confirmation) {
                            showInlineError(confirmPasswordInput, data.errors.password_confirmation[0]);
                        }
                        
                        // Show error notification with specific message for current password
                        let errorMessage = 'Please fix the errors before continuing.';
                        let errorTitle = 'Validation Error';
                        
                        // Check if current password field has an error
                        const currentPasswordErrorElement = document.getElementById('update_password_current_password-error');
                        if (currentPasswordErrorElement && currentPasswordErrorElement.textContent.includes('incorrect')) {
                            errorMessage = 'Current password is incorrect.';
                            errorTitle = 'Incorrect Password';
                        }
                        
                        Swal.fire({
                            icon: 'error',
                            title: errorTitle,
                            text: errorMessage
                        });
                    } else if (response.ok) {
                        // Success response
                        Swal.fire({
                            icon: 'success',
                            title: 'Updated!',
                            text: 'Your password has been updated successfully.',
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload(); // Refresh the page to show updated information
                        });
                    } else {
                        // Close the loading alert if it's open
                        Swal.close();
                        
                        // Other error responses
                        if (data.message) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: data.message
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: 'An error occurred while updating your password.'
                            });
                        }
                    }
                } else {
                    // Close the loading alert if it's open
                    Swal.close();
                    
                    // Non-JSON response (possibly redirect or HTML)
                    if (response.ok) {
                        // Success response
                        Swal.fire({
                            icon: 'success',
                            title: 'Updated!',
                            text: 'Your password has been updated successfully.',
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload(); // Refresh the page to show updated information
                        });
                    } else {
                        // Error response without JSON
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: 'An error occurred while updating your password.'
                        });
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: error.message || 'An error occurred while updating your password.'
                });
            });
        }
    });
}

function showInlineError(inputElement, message) {
    // Remove any existing error message
    clearInlineError(inputElement);
    
    // Add error styling to input
    inputElement.classList.add('border-red-500');
    
    // Create error message element
    const errorElement = document.createElement('div');
    errorElement.className = 'text-red-600 text-xs mt-1';
    errorElement.id = inputElement.id + '-error';
    errorElement.textContent = message;
    
    // Insert error message after the input
    inputElement.parentNode.appendChild(errorElement);
}

function clearInlineError(inputElement) {
    // Remove error styling
    inputElement.classList.remove('border-red-500');
    
    // Remove error message if exists
    const errorElement = document.getElementById(inputElement.id + '-error');
    if (errorElement) {
        errorElement.remove();
    }
}

function clearInlineErrors() {
    const currentPasswordInput = document.getElementById('update_password_current_password');
    const newPasswordInput = document.getElementById('update_password_password');
    const confirmPasswordInput = document.getElementById('update_password_password_confirmation');
    
    if (currentPasswordInput) clearInlineError(currentPasswordInput);
    if (newPasswordInput) clearInlineError(newPasswordInput);
    if (confirmPasswordInput) clearInlineError(confirmPasswordInput);
}
</script>
