MPIS ACCESS FIX - FILES TO EDIT
================================

1. REPLACE  app/Http/Middleware/RoleMiddleware.php
2. ADD      resources/views/errors/403.blade.php   (new file)
3. EDIT     routes/web.php  (or copy the whole file from this zip)

If you do NOT want to replace routes/web.php, make these 2 edits:

  Edit A: Just before the line   // ADMIN - OFFICES   add:
      Route::middleware('role:motorpool_admin,admin')->group(function () {

  Edit B: Right after the line   )->name('admin.leaders.update');   add:
      });

Then run from the project folder:
  php artisan route:clear
  php artisan config:clear
  php artisan view:clear

Test: log in as Employee, open /admin/employees -> should show 403 "Access denied".
