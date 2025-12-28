# IRF-CES - Intelligent Repair Flow & Client Engagement System

A modern customer service and repair management platform built with Laravel, featuring role-based access control, repair quote request management, user authentication, and comprehensive administrative capabilities.

## 🚀 Features

- **Role-Based Access Control**: Multi-tier user system with Administrator, Technician, and Counter Staff roles
- **Repair Quote Management**: Complete quote request lifecycle with status tracking
- **Staff Management**: Comprehensive staff administration with role assignments
- **User Authentication**: Secure authentication with Laravel Fortify
- **Real-time UI**: Interactive components with Livewire and Volt
- **Profile Management**: User profile and password management
- **Professional Design**: Tailwind CSS 4.x-powered responsive interface

## 🛠 Tech Stack

### Backend
- **Laravel 12.x** - PHP web application framework
- **PHP 8.2+** - Server-side scripting language
- **Laravel Fortify** - Authentication backend
- **Livewire 3.x** - Full-stack framework for dynamic interfaces
- **Livewire Volt** - Single-file Livewire components

### Frontend
- **Tailwind CSS 4.x** - Utility-first CSS framework
- **Alpine.js** - Lightweight JavaScript framework (via Livewire)
- **Vite 7.x** - Frontend build tool

### Database
- **MySQL 8.0+** - Primary database
- File Storage - Local storage for repair quote images

### Testing & Quality
- **Pest PHP** - Modern testing framework
- **Laravel Pint** - Code style fixer
- **Laravel Debugbar** - Development debugging tools
- **GitHub Actions** - CI/CD pipeline

## 📋 Requirements

- **PHP**: 8.2 or higher
- **Composer**: 2.0 or higher
- **Node.js**: 18.0 or higher
- **NPM**: 8.0 or higher
- **MySQL**: 8.0 or higher (or SQLite for development)
- **Web Server**: Apache/Nginx with mod_rewrite

## 🚀 Installation

### 1. Clone the Repository
```bash
git clone <repository-url>
cd IRF-CES
```

### 2. Install PHP Dependencies
```bash
composer install
```

### 3. Install Node.js Dependencies
```bash
npm install
```

### 4. Environment Configuration

Copy the environment file and configure it:

```bash
cp .env.example .env
```

Edit `.env` file with your database and application settings:

```env
APP_NAME="IRF-CES"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=irf_ces
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Email configuration
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"
```

### 5. Generate Application Key
```bash
php artisan key:generate
```

### 6. Database Setup

Create the database and run migrations:

```bash
php artisan migrate
```

Optionally, seed the database with sample data:

```bash
php artisan db:seed
```

### 7. Storage Link

Create the symbolic link for file storage:

```bash
php artisan storage:link
```

### 8. Build Assets

For development:

```bash
npm run dev
```

For production:

```bash
npm run build
```

### 9. Start the Development Server

```bash
php artisan serve
```

The application will be available at `http://localhost:8000`

## 👥 User Roles & Access

### Default Test Accounts

| Role | Email | Password | Dashboard Route |
|------|-------|----------|----------------|
| Administrator | admin@example.com | password | /admin/dashboard |
| Technician | technician@example.com | password | /technician/dashboard |
| Counter Staff | counter@example.com | password | /counter/dashboard |

### Role Permissions

**Administrator**
- Full system access
- Staff management (create, update, delete)
- Role assignments
- System settings

**Technician**
- Technical operations
- Repair quote management
- Technical dashboard access

**Counter Staff**
- Customer service operations
- Quote request intake
- Counter dashboard access

## 🔧 Development Commands

### Start Development Environment

Start the Laravel development server:

```bash
php artisan serve
```

Start asset watching:

```bash
npm run dev
```

### Code Quality

Run code style fixes:

```bash
php artisan pint
```

Run tests:

```bash
php artisan test
```

Or using Pest directly:

```bash
./vendor/bin/pest
```

### Other Useful Commands

```bash
# Clear all caches
php artisan optimize:clear

# Run queue worker
php artisan queue:work

# View logs in real-time
php artisan pail
```

## 📁 Project Structure

```
├── app/
│   ├── Enums/
│   │   └── Role.php           # User role definitions
│   ├── Http/
│   │   ├── Controllers/       # HTTP controllers
│   │   └── Middleware/        # Custom middleware (role checks)
│   ├── Livewire/
│   │   └── Actions/          # Livewire action components
│   ├── Models/
│   │   ├── User.php          # User model with role methods
│   │   └── RepairQuoteRequest.php
│   └── Providers/            # Service providers
├── database/
│   ├── migrations/           # Database migrations
│   ├── seeders/             # Database seeders
│   └── factories/           # Model factories
├── resources/
│   ├── views/
│   │   ├── livewire/        # Volt components
│   │   │   ├── admin/       # Admin dashboard
│   │   │   ├── technician/  # Technician dashboard
│   │   │   └── counter/     # Counter staff dashboard
│   │   ├── components/      # Blade components
│   │   └── partials/        # Partial views
│   ├── css/                 # Stylesheets
│   └── js/                  # JavaScript files
├── routes/
│   ├── web.php              # Web routes with role-based groups
│   └── console.php          # Artisan commands
├── tests/
│   ├── Feature/             # Feature tests
│   └── Unit/                # Unit tests
└── public/                  # Public assets
```

## 🎯 Key Models

- **User**: System users with role-based access
- **RepairQuoteRequest**: Customer repair quote requests with image uploads
- **Role** (Enum): Administrator, Technician, Counter Staff

## 🧪 Testing

The application includes comprehensive tests covering:

- Authentication flows
- Role-based access control
- Staff management operations
- Profile and settings management
- Dashboard access

### Run All Tests

```bash
php artisan test
```

### Run Specific Test Suites

```bash
# Feature tests
php artisan test --testsuite=Feature

# Unit tests
php artisan test --testsuite=Unit
```

### Run with Coverage

```bash
php artisan test --coverage
```

## 🔒 Security Features

- Password hashing with bcrypt
- CSRF protection on all forms
- Role-based middleware protection
- Secure password reset flow
- Email verification support
- Session management

## 🚀 Deployment

The project includes GitHub Actions CI/CD pipeline (`.github/workflows/tests.yml`).

### GitHub Actions Workflow

The pipeline automatically:
- Runs tests on PHP 8.2+
- Validates migrations
- Checks code quality

### Manual Deployment Steps

1. Set environment to production:
```bash
APP_ENV=production
APP_DEBUG=false
```

2. Optimize the application:
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

3. Build production assets:
```bash
npm run build
```

4. Set proper file permissions:
```bash
chmod -R 755 storage bootstrap/cache
```

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📝 License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## 🐛 Troubleshooting

### Common Issues

**Migration Errors**
```bash
php artisan migrate:fresh
```

**Cache Issues**
```bash
php artisan optimize:clear
```

**Permission Errors**
```bash
chmod -R 775 storage bootstrap/cache
```

**Asset Build Errors**
```bash
npm cache clean --force
rm -rf node_modules package-lock.json
npm install
```

## 📧 Support

For issues and questions, please open an issue on the GitHub repository.

---

Built with ❤️ using Laravel 12 and Livewire
