# Role-Based Authentication Setup

## Overview
The application now has role-based authentication with three user types:
- **Administrator** - Full system access
- **Technician** - Technical operations and maintenance
- **Counter Staff** - Customer service and counter operations

## Test Credentials

| Role | Email | Password | Dashboard Route |
|------|-------|----------|----------------|
| Administrator | admin@example.com | password | /admin/dashboard |
| Technician | technician@example.com | password | /technician/dashboard |
| Counter Staff | counter@example.com | password | /counter/dashboard |

## File Structure

### Core Files
- `app/Enums/Role.php` - Role enum definition
- `app/Models/User.php` - User model with role methods
- `app/Http/Middleware/EnsureUserHasRole.php` - Role-based access control middleware
- `routes/web.php` - Role-specific route groups

### Dashboard Components (Livewire Volt)
- `resources/views/livewire/admin/dashboard.blade.php` - Administrator dashboard
- `resources/views/livewire/technician/dashboard.blade.php` - Technician dashboard
- `resources/views/livewire/counter/dashboard.blade.php` - Counter staff dashboard

## Usage

### Check User Role in Code
```php
$user = auth()->user();

// Using helper methods
if ($user->isAdministrator()) {
    // Admin-only code
}

if ($user->isTechnician()) {
    // Technician code
}

if ($user->isCounterStaff()) {
    // Counter staff code
}

// Direct comparison
if ($user->role === Role::ADMINISTRATOR) {
    // Admin code
}
```

### Protect Routes
```php
// Single role
Route::middleware(['auth', 'role:administrator'])->group(function () {
    // Admin routes
});

// Multiple roles
Route::middleware(['auth', 'role:administrator,technician'])->group(function () {
    // Admin and technician routes
});
```

### Blade Directives
```blade
@if(auth()->user()->isAdministrator())
    <div>Admin content</div>
@endif

@if(auth()->user()->role === App\Enums\Role::TECHNICIAN)
    <div>Technician content</div>
@endif
```

## How It Works

1. **Login Flow**: User logs in via Fortify authentication
2. **Dashboard Redirect**: After login, users are redirected to `/dashboard` which automatically redirects to their role-specific dashboard
3. **Route Protection**: Middleware checks user role before allowing access to protected routes
4. **Access Control**: Users attempting to access unauthorized pages receive a 403 error

## Next Steps

### Add More Routes
Add routes for specific functionality in `routes/web.php`:

```php
// Administrator routes
Route::middleware(['auth', 'role:administrator'])->prefix('admin')->name('admin.')->group(function () {
    Volt::route('dashboard', 'admin.dashboard')->name('dashboard');
    Volt::route('users', 'admin.users')->name('users');
    Volt::route('settings', 'admin.settings')->name('settings');
});
```

### Create Additional Components
Create more Volt components for each role:

```bash
# Create new Volt component
php artisan make:volt admin/users
php artisan make:volt technician/tasks
php artisan make:volt counter/customers
```

### Database Schema Extension
If you need role-specific data, create additional tables:

```bash
php artisan make:migration create_technician_tasks_table
php artisan make:migration create_counter_transactions_table
```

## Testing

Run the tests to ensure everything works:

```bash
php artisan test
```

Login with any of the test accounts and verify:
1. Correct dashboard loads for each role
2. Role-specific routes are protected
3. Unauthorized access returns 403

## Production Notes

Before deploying to production:
1. Change all default passwords
2. Create real administrator accounts
3. Remove or secure test accounts
4. Set up proper role assignment workflow
5. Consider adding role management UI for administrators
