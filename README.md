# Serenity 

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-FF2D20?style=flat&logo=laravel&logoColor=white"/>
  <img src="https://img.shields.io/badge/PHP-777BB4?style=flat&logo=php&logoColor=white"/>
  <img src="https://img.shields.io/badge/MySQL-4479A1?style=flat&logo=mysql&logoColor=white"/>
  <img src="https://img.shields.io/badge/Vite-646CFF?style=flat&logo=vite&logoColor=white"/>
  <img src="https://img.shields.io/badge/Composer-885630?style=flat&logo=composer&logoColor=white"/>
  <img src="https://img.shields.io/badge/PNPM-F69220?style=flat&logo=pnpm&logoColor=white"/>
  <img src="https://img.shields.io/badge/Tailwind_CSS-06B6D4?style=flat&logo=tailwindcss&logoColor=white"/>
  <img src="https://img.shields.io/badge/Inertia.js-155dfc?logo=inertia&logoColor=fff)"/>
  <img src="https://img.shields.io/badge/Blade-FF2D20?style=flat&logo=laravel&logoColor=white"/>
</p>

Serenity is a Yoga Management System built with Laravel. It is designed to help yoga studios or instructors manage users, classes, attendance, and more.

---

## Screenshots

## Table of Contents
- [Features](#features)
- [Demo](#demo)
- [Screenshots](#screenshots)
- [Getting Started](#getting-started)
- [Folder Structure](#folder-structure)
- [Tech Stack](#tech-stack)
- [Contributing](#contributing)
- [License](#license)

## Features
- User authentication (Fortify)
- Role-based access control (Admin, Instructor, Student)
- Class and attendance management
- Login logging and audit trail
- Responsive UI with Blade and Tailwind CSS
- Inertia.js for modern SPA experience
- Database migrations and seeders
- Activity logs for user actions
- Easy extensibility for new features


## Getting Started

### Prerequisites
- PHP 8.1+
- Composer
- Node.js (with PNPM or NPM)
- MySQL or compatible database

### Installation
1. **Clone the repository:**
   ```sh
   git clone <repo-url>
   cd yoga-management-system
   ```
2. **Install PHP dependencies:**
   ```sh
   composer install
   ```
3. **Install JS dependencies:**
   ```sh
   pnpm install
   # or
   npm install
   ```
4. **Copy and configure your environment:**
   ```sh
   cp .env.example .env
   # Edit .env with your DB and app settings
   ```
5. **Generate application key:**
   ```sh
   php artisan key:generate
   ```
6. **Run migrations and seeders:**
   ```sh
   php artisan migrate --seed
   ```
7. **Build frontend assets:**
   ```sh
   pnpm run build
   # or
   npm run build
   ```
8. **Start the development server:**
   ```sh
   php artisan serve
   ```

## Folder Structure
- `app/` - Application logic (Controllers, Models, Actions, etc.)
- `resources/views/` - Blade templates
- `resources/js/` - JavaScript (Inertia, Pages, Components)
- `resources/css/` - Styles (Tailwind CSS)
- `routes/` - Route definitions
- `database/` - Migrations, seeders, factories
- `public/` - Public assets and entry point

## Tech Stack
- **Backend:** Laravel, PHP
- **Frontend:** Blade, Inertia.js, Tailwind CSS, Vite
- **Database:** MySQL
- **Package Management:** Composer (PHP), PNPM/NPM (JS)

## Contributing
Pull requests are welcome! For major changes, please open an issue first to discuss what you would like to change.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/YourFeature`)
3. Commit your changes (`git commit -am 'Add some feature'`)
4. Push to the branch (`git push origin feature/YourFeature`)
5. Open a pull request

## License
This project is open source and available under the [MIT License](LICENSE).

---
