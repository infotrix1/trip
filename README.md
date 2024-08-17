# Trip Planner Application

Welcome to the Trip Planner application! This web app allows users to plan their trips by adding destinations, viewing routes between locations, and exploring points of interest. It's built using Laravel, Vue 3, and Leaflet for a modern, interactive experience.

## Features

- **User Authentication**: Register and log in to manage your destinations.
- **Add Destinations**: Add new destinations via an input form or by clicking on a map.
- **View Routes**: Display routes between destinations and your current location on an interactive map.
- **Rearrange Destinations**: Change the order of destinations to customize your travel plan.
- **Points of Interest**: View popular attractions or points of interest near your destinations.
- **Journey Summary**: View a summary of your journey, including total distance, time, and fuel consumption.
- **Responsive Design**: Fully responsive interface to ensure a smooth experience on all devices.

## Technologies Used

- **Frontend**:
  - Vue 3
  - Tailwind CSS
  - Leaflet (for maps)
  - Axios (for HTTP requests)

- **Backend**:
  - Laravel (PHP framework)
  - Sanctum (for API token authentication)
  - MySQL (for database)

## Installation

Follow these steps to set up the Travel Planner application on your local machine.

### Prerequisites

- [Node.js](https://nodejs.org/) (includes npm)
- [Composer](https://getcomposer.org/) (for PHP dependencies)
- [MySQL](https://www.mysql.com/) (or another compatible database)
- [Laravel](https://laravel.com/) (PHP framework)

### Clone the Repository

git clone https://github.com/infotrix1/trip.git
cd trip

### Procedure on installing the application for Backend

1. Install the dependencies using composer install.
2. Configure the environment on .env
3. Create a new database in your MySQL server.
4. Run the migrations using php artisan migrate.
5. Run the seeders using php artisan db:seed.
6. Run the command php artisan key:generate to generate a new application key.
7. Run the command php artisan serve to start the server.

### Procedure for Setup Frontend
1. Install the dependencies using npm install.
2. Run the command npm run dev to build the assets.
2. Run the command npm run serve to start the development server.
3. Open your browser and navigate to http://localhost:8080.

### Running the Application

Open your browser and navigate to http://localhost:8000 to access the Travel Planner application.

### Contact for any difficulty during installation.
tobiseyi112@gmail.com
