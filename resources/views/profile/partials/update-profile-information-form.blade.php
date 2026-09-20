<section>
    <header>
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-4 h-4 text-green-600">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </div>
            </div>
            <div class="ml-2">
                <h2 class="text-base font-medium text-gray-800">
                    {{ __('Account Information') }}
                </h2>
            </div>
        </div>
        <p class="mt-1 text-xs text-gray-600">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-4 space-y-4">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" :value="__('Name')" class="block text-xs font-medium text-gray-700 mb-1" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 text-sm" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-1" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" class="block text-xs font-medium text-gray-700 mb-1" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 text-sm" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-1" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-xs mt-1 text-gray-800">
                        {{ __('Your email address is unverified.') }}

                        <button form="send-verification" class="underline text-xs text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-1 font-medium text-xs text-green-600">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex items-center gap-3">
            <button type="button" onclick="handleProfileUpdate()" class="inline-flex items-center px-3 py-1.5 bg-green-600 border border-transparent rounded-md font-medium text-white text-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
                {{ __('Save') }}
            </button>

            @if (session('status') === 'profile-updated')
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
function handleProfileUpdate() {
    // Get form elements
    const nameInput = document.getElementById('name');
    const emailInput = document.getElementById('email');
    
    // Clear previous error messages
    clearInlineProfileErrors();
    
    // Get form data
    const form = document.querySelector('form[method="post"][action="{{ route('profile.update') }}"]');
    const formData = new FormData(form);
    
    let hasErrors = false;
    
    // Validation
    if (!nameInput.value.trim()) {
        showInlineProfileError(nameInput, 'Name is required.');
        hasErrors = true;
    }
    
    if (!emailInput.value.trim()) {
        showInlineProfileError(emailInput, 'Email is required.');
        hasErrors = true;
    } else if (!isValidEmail(emailInput.value.trim())) {
        showInlineProfileError(emailInput, 'Please enter a valid email address.');
        hasErrors = true;
    }
    
    // If there are validation errors, stop the process
    if (hasErrors) {
        return;
    }
    
    // Add the _method patch to the form data
    formData.append('_method', 'PATCH');
    
    // Show confirmation dialog
    Swal.fire({
        title: 'Confirm Update',
        text: 'Are you sure you want to update your profile information?',
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
                text: 'Please wait while we update your profile.',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Submit the form via AJAX
            fetch('{{ route('profile.update') }}', {
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
                        if (data.errors && data.errors.name) {
                            showInlineProfileError(nameInput, data.errors.name[0]);
                        }
                        if (data.errors && data.errors.email) {
                            showInlineProfileError(emailInput, data.errors.email[0]);
                        }
                        
                        
                    } else if (response.ok) {
                        // Success response
                        Swal.fire({
                            icon: 'success',
                            title: 'Updated!',
                            text: 'Your profile has been updated successfully.',
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
                                text: 'An error occurred while updating your profile.'
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
                            text: 'Your profile has been updated successfully.',
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
                            text: 'An error occurred while updating your profile.'
                        });
                    }
                }
            })
            .catch(error => {
                // Close the loading alert if it's open
                Swal.close();
                
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: error.message || 'An error occurred while updating your profile.'
                });
            });
        }
    });
}

function showInlineProfileError(inputElement, message) {
    // Remove any existing error message
    clearInlineProfileError(inputElement);
    
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

function clearInlineProfileError(inputElement) {
    // Remove error styling
    inputElement.classList.remove('border-red-500');
    
    // Remove error message if exists
    const errorElement = document.getElementById(inputElement.id + '-error');
    if (errorElement) {
        errorElement.remove();
    }
}

function clearInlineProfileErrors() {
    const nameInput = document.getElementById('name');
    const emailInput = document.getElementById('email');
    
    if (nameInput) clearInlineProfileError(nameInput);
    if (emailInput) clearInlineProfileError(emailInput);
}

function isValidEmail(email) {
    // Basic email validation regex
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}
</script>
