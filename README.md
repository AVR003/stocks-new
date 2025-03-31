# Stock Portfolio Manager

A modern web application for managing your stock portfolio with real-time market data and AI-powered insights.

## Features

- User authentication with JWT
- Real-time market data and stock quotes
- Portfolio management
- Stock search and analysis
- AI-powered chat assistant
- Market indices tracking
- Investment recommendations

## Requirements

- PHP 7.4 or higher
- MongoDB 4.4 or higher
- Composer
- Node.js and npm (for frontend)
- Alpha Vantage API key

## Installation

1. Clone the repository:
```bash
git clone <repository-url>
cd stock-portfolio-manager
```

2. Install PHP dependencies:
```bash
composer install
```

3. Install frontend dependencies:
```bash
npm install
```

4. Copy the environment file:
```bash
cp .env.example .env
```

5. Update the `.env` file with your configuration:
- Set your MongoDB connection string
- Add your Alpha Vantage API key
- Configure JWT secret
- Set other environment variables as needed

6. Create the database:
```bash
php scripts/create-database.php
```

## Running the Application

1. Start the PHP development server:
```bash
php -S localhost:8000 -t public
```

2. Start the frontend development server:
```bash
npm run dev
```

3. Access the application at `http://localhost:3000`

## API Endpoints

### Authentication
- POST `/api/auth/register` - Register a new user
- POST `/api/auth/login` - Login user
- POST `/api/auth/logout` - Logout user

### Portfolio
- GET `/api/portfolio` - Get user's portfolio
- POST `/api/portfolio/stocks` - Add stock to portfolio
- DELETE `/api/portfolio/stocks/{symbol}` - Remove stock from portfolio
- PUT `/api/portfolio/settings` - Update portfolio settings

### Market
- GET `/api/market/indices` - Get market indices
- GET `/api/market/trending` - Get trending stocks

### Stocks
- GET `/api/stocks/search` - Search stocks
- GET `/api/stocks/suggestions` - Get stock suggestions
- GET `/api/stocks/{symbol}` - Get stock details

### Chat
- POST `/api/chat/message` - Send chat message
- GET `/api/chat/history` - Get chat history

## Security

- JWT-based authentication
- Password hashing
- Input validation
- CORS protection
- Rate limiting

## Contributing

1. Fork the repository
2. Create your feature branch
3. Commit your changes
4. Push to the branch
5. Create a new Pull Request

## License

This project is licensed under the MIT License - see the LICENSE file for details. 