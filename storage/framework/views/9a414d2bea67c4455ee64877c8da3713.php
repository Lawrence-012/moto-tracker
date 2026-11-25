<?php $__env->startSection('content'); ?>
<style>
    .mn-form-card {
        background: #fff;
        border: 2px solid #1e6031;
        border-radius: 12px;
        box-shadow: 0 4px 8px rgba(30, 96, 49, 0.10);
    }
    .mn-section-label {
        color: #1e6031;
        font-weight: 700;
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }
    .mn-input, .mn-select, .mn-textarea {
        margin-top: 0.25rem;
        display: block;
        width: 100%;
        padding: 0.5rem 0.75rem;
        border: 1px solid #cbd5e1;
        border-radius: 0.375rem;
        box-shadow: 0 1px 2px rgba(0,0,0,0.04);
        font-size: 0.875rem;
    }
    .mn-input:focus, .mn-select:focus, .mn-textarea:focus {
        outline: none;
        border-color: #1e6031;
        box-shadow: 0 0 0 3px rgba(30, 96, 49, 0.15);
    }
</style>

<div class="py-6">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Heading -->
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-bold" style="color: #1e6031; font-size: 1.75rem;">Add New Vehicle Maintenance</h2>
            <a href="<?php echo e(route('vehicle-maintenance.index')); ?>"
               class="inline-flex items-center px-3 py-1.5 bg-white border border-[#1e6031] rounded-md font-semibold text-xs text-[#1e6031] uppercase tracking-wider hover:bg-[#1e6031] hover:text-white transition ease-in-out duration-150">
                <i class="fas fa-arrow-left mr-1 text-xs"></i> Back to List
            </a>
        </div>

        <div class="mn-form-card overflow-hidden">
            <div class="p-6 text-gray-900">
                <form method="POST" action="<?php echo e(route('vehicle-maintenance.store')); ?>">
                    <?php echo csrf_field(); ?>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Vehicle -->
                        <div>
                            <label for="vehicle_id" class="mn-section-label">Vehicle <span class="text-red-500">*</span></label>
                            <select name="vehicle_id" id="vehicle_id" required class="mn-select">
                                <option value="">Select Vehicle</option>
                                <?php $__currentLoopData = $vehicles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $vehicle): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($vehicle->id); ?>" <?php echo e((old('vehicle_id') == $vehicle->id) || (request('vehicle_id') && request('vehicle_id') == $vehicle->id) ? 'selected' : ''); ?>>
                                        <?php echo e($vehicle->plate_number); ?> - <?php echo e($vehicle->model); ?>

                                    </option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                            <?php $__errorArgs = ['vehicle_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <p class="mt-1 text-sm text-red-600"><?php echo e($message); ?></p>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>

                        <!-- Odometer Reading -->
                        <div>
                            <label for="odometer_reading" class="mn-section-label">Odometer Reading (km)</label>
                            <input type="number" name="odometer_reading" id="odometer_reading" value="<?php echo e(old('odometer_reading')); ?>"
                                   class="mn-input" min="0">
                            <?php $__errorArgs = ['odometer_reading'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <p class="mt-1 text-sm text-red-600"><?php echo e($message); ?></p>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>

                        <!-- Date Started -->
                        <div>
                            <label for="date_started" class="mn-section-label">Date Started <span class="text-red-500">*</span></label>
                            <input type="date" name="date_started" id="date_started" value="<?php echo e(old('date_started')); ?>" required
                                   class="mn-input">
                            <?php $__errorArgs = ['date_started'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <p class="mt-1 text-sm text-red-600"><?php echo e($message); ?></p>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>

                        <!-- Make or Type -->
                        <div>
                            <label for="make_or_type" class="mn-section-label">Make or Type <span class="text-red-500">*</span></label>
                            <input type="text" name="make_or_type" id="make_or_type" value="<?php echo e(old('make_or_type')); ?>" required
                                   class="mn-input">
                            <?php $__errorArgs = ['make_or_type'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <p class="mt-1 text-sm text-red-600"><?php echo e($message); ?></p>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>

                        <!-- Person/Office/Unit -->
                        <div>
                            <label for="person_office_unit" class="mn-section-label">Person/Office/Unit <span class="text-red-500">*</span></label>
                            <input type="text" name="person_office_unit" id="person_office_unit" value="<?php echo e(old('person_office_unit')); ?>" required
                                   class="mn-input">
                            <?php $__errorArgs = ['person_office_unit'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <p class="mt-1 text-sm text-red-600"><?php echo e($message); ?></p>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>

                        <!-- Place -->
                        <div>
                            <label for="place" class="mn-section-label">Place <span class="text-red-500">*</span></label>
                            <input type="text" name="place" id="place" value="<?php echo e(old('place')); ?>" required
                                   class="mn-input">
                            <?php $__errorArgs = ['place'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <p class="mt-1 text-sm text-red-600"><?php echo e($message); ?></p>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>

                        <!-- Mechanics Assigned -->
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label class="mn-section-label">Mechanics Assigned <span class="text-red-500">*</span></label>
                                <button type="button" id="add-mechanic" class="inline-flex items-center px-2 py-1 bg-[#1e6031] border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-[#164f2a]">
                                    <svg class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                    </svg>
                                    Add
                                </button>
                            </div>
                            <div id="mechanics-container">
                                <div class="mechanic-item mb-2 flex">
                                    <input type="text" name="mechanic_assigned[]" required
                                           class="mn-input flex-1"
                                           placeholder="Mechanic Name">
                                    <button type="button" class="remove-mechanic ml-2 px-2 py-1 rounded-md text-sm" style="background-color:#dc3545; color:#fff;">Remove</button>
                                </div>
                            </div>
                            <?php $__errorArgs = ['mechanic_assigned.*'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <p class="mt-1 text-sm text-red-600"><?php echo e($message); ?></p>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>

                        <!-- Conforme -->
                        <div>
                            <label for="conforme" class="mn-section-label">Conforme <span class="text-red-500">*</span></label>
                            <input type="text" name="conforme" id="conforme" value="<?php echo e(old('conforme')); ?>" required
                                   class="mn-input">
                            <?php $__errorArgs = ['conforme'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <p class="mt-1 text-sm text-red-600"><?php echo e($message); ?></p>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>

                        <!-- Status -->
                        <div>
                            <label for="status" class="mn-section-label">Status <span class="text-red-500">*</span></label>
                            <select name="status" id="status" required class="mn-select">
                                <option value="">Select Status</option>
                                <option value="Pending" <?php echo e(old('status') == 'Pending' ? 'selected' : ''); ?>>Pending</option>
                                <option value="Ongoing" <?php echo e(old('status') == 'Ongoing' ? 'selected' : ''); ?>>Ongoing</option>
                                <option value="Completed" <?php echo e(old('status') == 'Completed' ? 'selected' : ''); ?>>Completed</option>
                            </select>
                            <?php $__errorArgs = ['status'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <p class="mt-1 text-sm text-red-600"><?php echo e($message); ?></p>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>

                        <!-- Date Completed -->
                        <div>
                            <label for="date_completed" class="mn-section-label">Date Completed</label>
                            <input type="date" name="date_completed" id="date_completed" value="<?php echo e(old('date_completed')); ?>"
                                   class="mn-input">
                            <?php $__errorArgs = ['date_completed'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <p class="mt-1 text-sm text-red-600"><?php echo e($message); ?></p>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                    </div>

                    <!-- Nature of Work -->
                    <div class="mt-6">
                        <label for="nature_of_work" class="mn-section-label">Nature of Work <span class="text-red-500">*</span></label>
                        <textarea name="nature_of_work" id="nature_of_work" rows="4" required
                                  class="mn-textarea"><?php echo e(old('nature_of_work')); ?></textarea>
                        <?php $__errorArgs = ['nature_of_work'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <p class="mt-1 text-sm text-red-600"><?php echo e($message); ?></p>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <!-- Oil change interval (shown when the work is an oil change) -->
                    <div id="oil-interval-wrap" class="mt-6" style="display:none;">
                        <label class="mn-section-label">Oil Change Interval</label>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-2">
                            <div>
                                <label for="oil_interval_km" class="text-xs font-semibold text-gray-600">Good for (km)</label>
                                <select name="oil_interval_km" id="oil_interval_km" class="mn-select">
                                    <option value="">Select</option>
                                    <option value="5000" <?php echo e(old('oil_interval_km') == '5000' ? 'selected' : ''); ?>>5,000 km</option>
                                    <option value="10000" <?php echo e(old('oil_interval_km') == '10000' ? 'selected' : ''); ?>>10,000 km</option>
                                </select>
                            </div>
                            <div>
                                <label for="oil_interval_months" class="text-xs font-semibold text-gray-600">Or good for (months)</label>
                                <select name="oil_interval_months" id="oil_interval_months" class="mn-select">
                                    <option value="">Select</option>
                                    <option value="3" <?php echo e(old('oil_interval_months') == '3' ? 'selected' : ''); ?>>3 months</option>
                                    <option value="6" <?php echo e(old('oil_interval_months') == '6' ? 'selected' : ''); ?>>6 months</option>
                                </select>
                            </div>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">The oil alert appears when either limit is reached.</p>
                    </div>

                    <!-- Remarks -->
                    <div class="mt-6">
                        <label for="remarks" class="mn-section-label">Remarks</label>
                        <textarea name="remarks" id="remarks" rows="3" class="mn-textarea"><?php echo e(old('remarks')); ?></textarea>
                        <?php $__errorArgs = ['remarks'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <p class="mt-1 text-sm text-red-600"><?php echo e($message); ?></p>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <!-- Materials/Parts -->
                    <div class="mt-6">
                        <div class="flex items-center justify-between mb-2">
                            <label class="mn-section-label">Materials/Parts</label>
                            <button type="button" id="add-material" class="inline-flex items-center px-2 py-1 bg-[#1e6031] border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-[#164f2a]">
                                <svg class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                </svg>
                                Add
                            </button>
                        </div>
                        <div id="materials-container">
                            <div class="material-item mb-2 flex">
                                <input type="text" name="materials_parts[]"
                                       class="mn-input flex-1"
                                       placeholder="Material/Part">
                                <button type="button" class="remove-material ml-2 px-2 py-1 rounded-md text-sm" style="background-color:#dc3545; color:#fff;">Remove</button>
                            </div>
                        </div>
                        <?php $__errorArgs = ['materials_parts.*'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <p class="mt-1 text-sm text-red-600"><?php echo e($message); ?></p>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <div class="mt-6 flex items-center justify-end border-t pt-4" style="border-color:#e5e7eb;">
                        <a href="<?php echo e(route('vehicle-maintenance.index')); ?>" class="mr-3 inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-[#1e6031] focus:ring-offset-2 transition ease-in-out duration-150">
                            Cancel
                        </a>
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-[#1e6031] border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-[#164f2a] focus:bg-[#164f2a] active:bg-[#103c1e] focus:outline-none focus:ring-2 focus:ring-[#1e6031] focus:ring-offset-2 transition ease-in-out duration-150">
                            <i class="fas fa-save mr-1"></i> Save Maintenance Record
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Show the oil interval options only when the work is an oil change.
(function () {
    const nature = document.getElementById('nature_of_work');
    const wrap = document.getElementById('oil-interval-wrap');
    function toggleOil() {
        const t = (nature.value || '').toLowerCase();
        wrap.style.display = (t.includes('change oil') || t.includes('oil change')) ? '' : 'none';
    }
    nature.addEventListener('input', toggleOil);
    toggleOil();
})();
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add material button
    document.getElementById('add-material').addEventListener('click', function() {
        const container = document.getElementById('materials-container');
        const materialItem = document.createElement('div');
        materialItem.className = 'material-item mb-2 flex';
        materialItem.innerHTML = `
            <input type="text" name="materials_parts[]"
                   class="mn-input flex-1"
                   placeholder="Material/Part">
            <button type="button" class="remove-material ml-2 px-2 py-1 rounded-md text-sm" style="background-color:#dc3545; color:#fff;">Remove</button>
        `;
        container.appendChild(materialItem);

        materialItem.querySelector('.remove-material').addEventListener('click', function() {
            if (container.querySelectorAll('.material-item').length > 1) {
                container.removeChild(materialItem);
            }
        });
    });

    // Add mechanic button
    document.getElementById('add-mechanic').addEventListener('click', function() {
        const container = document.getElementById('mechanics-container');
        const mechanicItem = document.createElement('div');
        mechanicItem.className = 'mechanic-item mb-2 flex';
        mechanicItem.innerHTML = `
            <input type="text" name="mechanic_assigned[]" required
                   class="mn-input flex-1"
                   placeholder="Mechanic Name">
            <button type="button" class="remove-mechanic ml-2 px-2 py-1 rounded-md text-sm" style="background-color:#dc3545; color:#fff;">Remove</button>
        `;
        container.appendChild(mechanicItem);

        mechanicItem.querySelector('.remove-mechanic').addEventListener('click', function() {
            if (container.querySelectorAll('.mechanic-item').length > 1) {
                container.removeChild(mechanicItem);
            }
        });
    });

    // Remove material button event listeners (for existing buttons)
    document.querySelectorAll('.remove-material').forEach(button => {
        button.addEventListener('click', function() {
            const container = document.getElementById('materials-container');
            if (container.querySelectorAll('.material-item').length > 1) {
                this.closest('.material-item').remove();
            }
        });
    });

    // Remove mechanic button event listeners (for existing buttons)
    document.querySelectorAll('.remove-mechanic').forEach(button => {
        button.addEventListener('click', function() {
            const container = document.getElementById('mechanics-container');
            if (container.querySelectorAll('.mechanic-item').length > 1) {
                this.closest('.mechanic-item').remove();
            }
        });
    });
});
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.motorpool-admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\MPIS_REPO_with_tracking\resources\views/vehicle-maintenance/create.blade.php ENDPATH**/ ?>