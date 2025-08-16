# Catapult Project Overview

## Purpose
Catapult is a Laravel web application with Filament admin panel for agricultural/farming operations management. It handles time tracking, crop planning, inventory management, and various agricultural workflows.

## Tech Stack
- **Framework**: Laravel 12.x
- **Admin Panel**: Filament 3.x
- **Database**: MySQL/MariaDB
- **PHP**: 8.2+
- **Activity Logging**: Spatie Laravel Activity Log 4.x
- **Permissions**: Spatie Laravel Permission 6.x
- **PDF**: Laravel DomPDF
- **Calendar**: Saade Filament Full Calendar
- **Payments**: Laravel Cashier (Stripe)

## Key Features
- Time card management with flagging/review system
- Crop planning and scheduling
- Inventory management for consumables (seeds, soil, packaging, etc.)
- Activity logging with extended metadata
- User management with roles/permissions
- Calendar integration for crop planning
- PDF reporting

## Architecture Notes
- Uses custom `ExtendedLogsActivity` trait for enhanced activity logging
- Filament resources organized with separate Form/Table/Action classes
- Models use relationships extensively (BelongsTo, HasMany, etc.)
- Extensive use of scopes for querying
- Seeders and factories for testing data