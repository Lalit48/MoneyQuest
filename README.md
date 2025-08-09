# MoneyQuest - Financial Literacy Platform

A responsive gamified financial literacy platform built with PHP, MySQL, HTML, CSS, JavaScript, jQuery, Bootstrap, and Three.js. Features interactive quizzes, stock market simulation, achievements, and real-time updates.

## 🎯 Features

### Core Features
- **Interactive Quizzes**: Test financial knowledge with multiple-choice questions
- **Stock Market Simulator**: Practice investing with real-time stock data
- **Virtual Wallet**: Manage virtual currency and track financial progress
- **Achievement System**: Unlock badges and rewards for progress
- **Leaderboard**: Compete with other users
- **Real-time Updates**: Live wallet balance and points updates

### Technical Features
- **Responsive Design**: Works on mobile, tablet, and desktop
- **Three.js Animations**: Interactive 3D elements (coins, trophies, badges)
- **AJAX Integration**: Real-time updates without page refresh
- **Secure Authentication**: Password hashing and session management
- **Database-driven**: MySQL backend with proper relationships

## 🚀 Quick Start

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- Python 3.7+ (for stock data updates)
- Alpha Vantage API key (free tier available)

### Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/yourusername/moneyquest.git
   cd moneyquest
   ```

2. **Set up the database**
   - Create a MySQL database named `moneyquest`
   - Update database credentials in `config/database.php`
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'your_username');
   define('DB_PASS', 'your_password');
   define('DB_NAME', 'moneyquest');
   ```

3. **Configure web server**
   - Point your web server to the project directory
   - Ensure PHP has write permissions for logs

4. **Install Python dependencies**
   ```bash
   pip install requests mysql-connector-python
   ```

5. **Configure Alpha Vantage API**
   - Get a free API key from [Alpha Vantage](https://www.alphavantage.co/support/#api-key)
   - Update `scripts/stock_updater.py` with your API key
   ```python
   ALPHA_VANTAGE_API_KEY = "your_api_key_here"
   ```

6. **Set up cron job for stock updates**
   ```bash
   # Add to crontab (runs every 5 minutes)
   */5 * * * * /usr/bin/python3 /path/to/moneyquest/scripts/stock_updater.py
   ```

7. **Access the application**
   - Open your browser and navigate to `http://localhost/moneyquest`
   - Register a new account and start learning!

## 📁 Project Structure

```
moneyquest/
├── index.php                 # Landing page with Three.js animations
├── signup.php               # User registration
├── login.php                # User authentication
├── dashboard.php            # Main dashboard
├── quiz.php                 # Interactive quizzes
├── stocks.php               # Stock market simulator
├── leaderboard.php          # User rankings
├── achievements.php         # Achievement system
├── profile.php              # User profile
├── logout.php               # Session logout
├── config/
│   └── database.php         # Database configuration
├── api/
│   ├── get_user_stats.php   # AJAX endpoint for user stats
│   └── get_quiz_questions.php # AJAX endpoint for quiz data
├── scripts/
│   └── stock_updater.py     # Python script for stock data
└── README.md                # This file
```

## 🎮 How to Use

### For Users
1. **Registration**: Create an account to get started
2. **Take Quizzes**: Choose from budgeting, investing, or saving categories
3. **Trade Stocks**: Practice investing with virtual money
4. **Earn Points**: Complete quizzes and activities to earn points
5. **Unlock Achievements**: Reach milestones to unlock badges
6. **Compete**: Check the leaderboard to see your ranking

### For Administrators
1. **Database Management**: Monitor user activity and transactions
2. **Stock Updates**: Ensure the cron job is running for real-time data
3. **Content Management**: Add new quizzes and questions via database
4. **System Monitoring**: Check logs for any issues

## 🛠️ Customization

### Adding New Quizzes
1. Insert quiz data into the `quizzes` table
2. Add questions to the `questions` table
3. Link questions to quizzes using `quiz_id`

### Adding New Stocks
1. Update the `STOCK_SYMBOLS` list in `scripts/stock_updater.py`
2. Add company names to the `company_names` dictionary
3. Restart the stock updater script

### Styling Changes
- Modify CSS in individual PHP files
- Update Bootstrap classes for layout changes
- Customize Three.js animations in JavaScript sections

## 🔧 Troubleshooting

### Common Issues

1. **Database Connection Failed**
   - Check database credentials in `config/database.php`
   - Ensure MySQL service is running
   - Verify database exists

2. **Stock Data Not Updating**
   - Check Alpha Vantage API key
   - Verify cron job is running
   - Check Python script logs

3. **Three.js Not Working**
   - Ensure JavaScript is enabled
   - Check browser console for errors
   - Verify Three.js CDN is accessible

4. **Session Issues**
   - Check PHP session configuration
   - Ensure proper file permissions
   - Verify session storage directory

### Logs
- Application logs: Check web server error logs
- Stock updater logs: `scripts/stock_updater.log`
- Database logs: MySQL error log

## 📊 Database Schema

### Key Tables
- `users`: User accounts and balances
- `quizzes`: Quiz categories and titles
- `questions`: Quiz questions and answers
- `stocks`: Stock data and prices
- `portfolio`: User stock holdings
- `transactions`: Financial transactions
- `achievements`: Achievement definitions

## 🔒 Security Features

- Password hashing using PHP's `password_hash()`
- Prepared statements to prevent SQL injection
- Session-based authentication
- Input validation and sanitization
- CSRF protection (implement as needed)

## 🎨 Design Features

- Modern gradient backgrounds
- Responsive Bootstrap grid system
- Interactive hover effects
- Three.js 3D animations
- FontAwesome icons
- Smooth transitions and animations

## 📈 Performance Optimization

- AJAX for real-time updates
- Efficient database queries
- Optimized images and assets
- Caching strategies (implement as needed)
- CDN for external libraries

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## 📄 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 🙏 Acknowledgments

- Alpha Vantage for stock market data
- Bootstrap for responsive design framework
- Three.js for 3D graphics
- FontAwesome for icons
- jQuery for JavaScript utilities

## 📞 Support

For support and questions:
- Create an issue on GitHub
- Check the troubleshooting section
- Review the documentation

---

**Happy Learning! 🎓💰** 