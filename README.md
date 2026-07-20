# Trip Planner

A production-minded route planning application built with **Laravel 10, Vue 3, Inertia.js, MySQL, Tailwind CSS, Leaflet and OpenStreetMap services**.

The project demonstrates end-to-end feature ownership: authenticated persistence, API design, third-party integrations, background processing, route visualisation, validation, authorisation and automated testing.

## Core workflow

An authenticated user can:

1. Search for a location or select one by double-clicking the map.
2. Save multiple destinations to a private itinerary.
3. Reorder or remove destinations.
4. Generate a route from the user's current location through all saved stops.
5. View distance, travel-time and estimated-fuel summaries.
6. Discover nearby points of interest.

## Architecture

The backend follows SOLID principles and a Controller → Service → Repository structure.

```text
HTTP request
    ↓
Form Request validation
    ↓
API Controller
    ↓
Domain Service
    ↓
Repository Interface
    ↓
Eloquent Repository
    ↓
Database
```

Key design decisions:

- **Thin controllers** coordinate requests and responses only.
- **Services** contain workflow and business rules.
- **Repository contracts** decouple domain logic from Eloquent persistence.
- **DTOs** provide explicit data transfer across application boundaries.
- **Policies** enforce destination ownership.
- **API resources** provide stable response contracts.
- **Queued listeners** warm points-of-interest caches after a destination is created.
- **Map providers are proxied by Laravel**, allowing validation, retries, caching and centralised failure handling.
- **Vue composables and API services** keep UI state separate from transport logic.

## Main directories

```text
app/
├── Contracts/Repositories
├── DataTransferObjects
├── Events
├── Http/Controllers/Api
├── Http/Requests
├── Http/Resources
├── Listeners
├── Policies
├── Repositories
└── Services

resources/js/
├── Composables
├── Pages
└── Services
```

## Requirements

- PHP 8.1+
- Composer 2
- Node.js 18+
- MySQL 8+, MariaDB, PostgreSQL or SQLite

## Installation

```bash
git clone https://github.com/infotrix1/trip.git
cd trip
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Configure the database in `.env`, then run:

```bash
php artisan migrate
npm run build
php artisan serve
```

For active frontend development, run these in separate terminals:

```bash
php artisan serve
npm run dev
php artisan queue:work
```

Open `http://127.0.0.1:8000`.

## External services

The application uses:

- OpenStreetMap tiles
- Nominatim for geocoding
- Overpass API for nearby points of interest
- Leaflet Routing Machine for route calculation

Provider URLs are configurable:

```dotenv
NOMINATIM_URL=https://nominatim.openstreetmap.org
OVERPASS_URL=https://overpass-api.de/api/interpreter
```

For a commercial production deployment, replace public community endpoints with providers whose service terms and capacity match expected traffic.

## Testing

Run the complete suite:

```bash
php artisan test
```

The feature suite covers:

- Authentication boundaries
- Destination ownership isolation
- Validation
- Creation and deletion
- Policy enforcement
- Reordering integrity
- Third-party API response normalisation

## Code quality

```bash
./vendor/bin/pint
php artisan test
npm run build
```

## Security considerations

- All application APIs require Sanctum authentication.
- Form Requests validate untrusted input.
- Policies prevent cross-user deletion.
- Reordering validates the complete set of user-owned destination IDs.
- External provider calls use server-side timeouts, retries and caching.
- No API credentials or private customer data are stored in the repository.

## Possible production extensions

- Workspace-based multi-tenancy
- Persistent trips with named itineraries
- WebSocket progress updates for long-running route calculations
- Provider circuit breakers and observability
- Docker-based local environment
- CI workflow for tests, linting and frontend builds
- AI-assisted itinerary recommendations grounded in saved trip data

## Author

Tobi Oladokun  
GitHub: [infotrix1](https://github.com/infotrix1)
