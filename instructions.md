# 🐳 Laravel Sail Setup Guide

> A complete step-by-step guide to install and run **Laravel Sail** — Laravel's official Docker-based development environment.

---

## 🧰 Prerequisites

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) (Installed and running)
- PHP and Composer installed (for initial setup only)
- Git installed

---

## 🚀 Getting Started

### 1. Clone or Create a Laravel Project

```bash
# Clone an existing Laravel project
git clone https://github.com/Jxhn-Canxii/Basketball-League-Simulation-System.git
cd your-laravel-app

# OR create a new Laravel project
composer create-project laravel/laravel example-app
cd example-app

#2. Install Laravel Sail
composer require laravel/sail --dev

#3. Publish Sail’s Docker Configuration
php artisan sail:install

#Step 2: Run Docker Compose commands
#From the terminal (in the same folder as docker-compose.yml):
docker-compose up -d


# 4. Start Sail (Docker Containers)

./vendor/bin/sail up

#Use -d to run in the background:
./vendor/bin/sail up -d 

#5. Run Artisan and Composer Commands via Sail
./vendor/bin/sail artisan migrate
./vendor/bin/sail composer install

# Optional: Add a Sail alias to make command usage easier
alias sail='[ -f sail ] && bash sail || bash vendor/bin/sail'

# Then just use:
sail up
sail artisan migrate



