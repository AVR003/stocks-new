// State management
let currentUser = null;
let currentSession = null;
let activeSection = 'dashboard';
let marketData = null;

// API Configuration
const API_BASE_URL = 'http://localhost:8000';

// DOM Elements
const loginBtn = document.getElementById('loginBtn');
const logoutBtn = document.getElementById('logoutBtn');
const loginModal = document.getElementById('loginModal');
const addStockModal = document.getElementById('addStockModal');
const portfolioSettingsModal = document.getElementById('portfolioSettingsModal');
const locationModal = document.getElementById('locationModal');
const locationBtn = document.getElementById('locationBtn');
const closeModal = document.querySelector('.close');
const authForm = document.getElementById('authForm');
const addStockForm = document.getElementById('addStockForm');
const portfolioSettingsForm = document.getElementById('portfolioSettingsForm');
const usernameDisplay = document.getElementById('usernameDisplay');
const userInput = document.getElementById('userInput');
const sendBtn = document.getElementById('sendBtn');
const chatMessages = document.getElementById('chatMessages');
const portfolioDisplay = document.getElementById('portfolioDisplay');
const suggestionsDisplay = document.getElementById('suggestionsDisplay');
const activityList = document.getElementById('activityList');
const navItems = document.querySelectorAll('.nav-item');

// Market Data Elements
const sp500Value = document.getElementById('sp500Value');
const nasdaqValue = document.getElementById('nasdaqValue');
const dowValue = document.getElementById('dowValue');

// Video and Subtitles
const marketVideo = document.getElementById('marketVideo');
const subtitleTrack = document.getElementById('subtitleTrack');
const toggleSubtitlesBtn = document.getElementById('toggleSubtitles');
let subtitlesEnabled = true;

// Sample subtitles data (in a real app, this would come from a .srt or .vtt file)
const subtitles = [
    { start: 0, end: 5, text: "Welcome to Market Insights" },
    { start: 5, end: 10, text: "Today's market overview" },
    { start: 10, end: 15, text: "Key market indicators" },
    { start: 15, end: 20, text: "Investment strategies" },
    { start: 20, end: 25, text: "Risk management tips" }
];

// Add Chart.js CDN to index.html
document.head.insertAdjacentHTML('beforeend', '<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>');

// Initialize portfolio chart
let portfolioChart;

function initializePortfolioChart() {
    const ctx = document.getElementById('portfolioChart').getContext('2d');
    
    portfolioChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'Portfolio Value',
                data: [],
                borderColor: 'rgb(75, 192, 192)',
                tension: 0.1,
                fill: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: false,
                    ticks: {
                        callback: function(value) {
                            return '$' + value.toLocaleString();
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return '$' + context.parsed.y.toLocaleString();
                        }
                    }
                }
            }
        }
    });
}

// Update portfolio chart with new data
function updatePortfolioChart(data) {
    if (!portfolioChart) {
        initializePortfolioChart();
    }

    portfolioChart.data.labels = data.labels;
    portfolioChart.data.datasets[0].data = data.values;
    portfolioChart.update();
}

// Example data update (replace with real data from your API)
function loadPortfolioPerformance() {
    // Simulated data - replace with actual API call
    const data = {
        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
        values: [10000, 10500, 10200, 10800, 11200, 11500]
    };
    
    updatePortfolioChart(data);
}

// Stock Search
let stockChart = null;
let suggestionTimeout = null;
let suggestionsContainer = null;

function initializeStockSearch() {
    const searchInput = document.getElementById('stockSearchInput');
    const searchContainer = searchInput.parentElement;
    
    // Create suggestions container
    suggestionsContainer = document.createElement('div');
    suggestionsContainer.className = 'stock-suggestions';
    searchContainer.appendChild(suggestionsContainer);
    
    // Add input event listener
    searchInput.addEventListener('input', handleSearchInput);
    
    // Close suggestions when clicking outside
    document.addEventListener('click', (e) => {
        if (!searchContainer.contains(e.target)) {
            suggestionsContainer.style.display = 'none';
        }
    });
}

async function handleSearchInput(e) {
    const query = e.target.value.trim().toUpperCase();
    
    // Clear previous timeout
    if (suggestionTimeout) {
        clearTimeout(suggestionTimeout);
    }
    
    // Hide suggestions if query is empty
    if (!query) {
        suggestionsContainer.style.display = 'none';
        return;
    }
    
    // Set new timeout to prevent too many API calls
    suggestionTimeout = setTimeout(async () => {
        try {
            const response = await fetch(`${API_BASE_URL}/api/stock_suggestions.php?query=${encodeURIComponent(query)}`);
            const responseText = await response.text();
            console.log('Raw suggestions response:', responseText); // Debug log
            
            let data;
            try {
                data = JSON.parse(responseText);
            } catch (e) {
                console.error('JSON Parse Error:', e);
                console.error('Response Text:', responseText);
                return;
            }
            
            if (data.success) {
                displaySuggestions(data.data);
            }
        } catch (error) {
            console.error('Error fetching suggestions:', error);
        }
    }, 300); // Wait 300ms after typing stops
}

function displaySuggestions(suggestions) {
    if (!suggestions || !suggestions.length) {
        suggestionsContainer.style.display = 'none';
        return;
    }
    
    suggestionsContainer.innerHTML = suggestions.map(stock => `
        <div class="suggestion-item" onclick="selectSuggestion('${stock.symbol}')">
            <div class="suggestion-symbol">${stock.symbol}</div>
            <div class="suggestion-name">${stock.name}</div>
            <div class="suggestion-type">${stock.type}</div>
        </div>
    `).join('');
    
    suggestionsContainer.style.display = 'block';
}

function selectSuggestion(symbol) {
    document.getElementById('stockSearchInput').value = symbol;
    suggestionsContainer.style.display = 'none';
    searchStock();
}

async function searchStock() {
    const symbol = document.getElementById('stockSearchInput').value.trim().toUpperCase();
    if (!symbol) {
        showError('Please enter a stock symbol');
        return;
    }

    // Show loading state
    const resultsDiv = document.getElementById('stockSearchResults');
    resultsDiv.innerHTML = `
        <div class="stock-details fade-in">
            <div class="loading-state">
                <i class="fas fa-spinner fa-spin"></i>
                <p>Searching for ${symbol}...</p>
            </div>
        </div>
    `;

    try {
        const response = await fetch(`${API_BASE_URL}/api/stock_search.php`, {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ symbol })
        });

        const responseText = await response.text();
        console.log('Raw search response:', responseText); // Debug log
        
        let data;
        try {
            data = JSON.parse(responseText);
        } catch (e) {
            console.error('JSON Parse Error:', e);
            console.error('Response Text:', responseText);
            throw new Error('Invalid response from server');
        }
        
        if (!response.ok) {
            throw new Error(data.message || 'Failed to fetch stock data');
        }

        if (!data.success) {
            throw new Error(data.message || 'Failed to fetch stock data');
        }

        if (!data.data) {
            throw new Error('No data received from server');
        }

        displayStockResults(data.data);
    } catch (error) {
        console.error('Stock search error:', error);
        resultsDiv.innerHTML = `
            <div class="stock-details fade-in">
                <div class="error-state">
                    <i class="fas fa-exclamation-circle"></i>
                    <p>${error.message}</p>
                </div>
            </div>
        `;
    }
}

function displayStockResults(data) {
    const resultsDiv = document.getElementById('stockSearchResults');
    
    // Format the price and change values
    const price = parseFloat(data.price).toLocaleString(undefined, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
    
    const change = parseFloat(data.change);
    const changePercent = parseFloat(data.changePercent);
    const changeClass = change >= 0 ? 'positive' : 'negative';
    
    // Display stock details
    resultsDiv.innerHTML = `
        <div class="stock-details fade-in">
            <div class="stock-header">
                <div class="stock-title">
                    <span class="stock-symbol">${data.symbol}</span>
                </div>
                <div class="stock-price ${changeClass}">
                    $${price}
                    <span class="change">${change >= 0 ? '+' : ''}${change.toFixed(2)} (${changePercent.toFixed(2)}%)</span>
                </div>
            </div>
            <div class="stock-info">
                <div class="info-row">
                    <span class="label">Day High:</span>
                    <span class="value">$${parseFloat(data.dayHigh).toLocaleString()}</span>
                </div>
                <div class="info-row">
                    <span class="label">Day Low:</span>
                    <span class="value">$${parseFloat(data.dayLow).toLocaleString()}</span>
                </div>
                <div class="info-row">
                    <span class="label">Volume:</span>
                    <span class="value">${parseInt(data.volume).toLocaleString()}</span>
                </div>
                <div class="info-row">
                    <span class="label">Last Updated:</span>
                    <span class="value">${data.lastUpdated}</span>
                </div>
            </div>
        </div>
    `;
}

function formatMarketCap(value) {
    if (!value || value === 'N/A') return 'N/A';
    
    const num = parseFloat(value);
    if (num >= 1e12) return `$${(num / 1e12).toFixed(2)}T`;
    if (num >= 1e9) return `$${(num / 1e9).toFixed(2)}B`;
    if (num >= 1e6) return `$${(num / 1e6).toFixed(2)}M`;
    if (num >= 1e3) return `$${(num / 1e3).toFixed(2)}K`;
    return `$${num.toLocaleString()}`;
}

function initializeStockChart() {
    const ctx = document.getElementById('stockChart').getContext('2d');
    
    stockChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'Stock Price',
                data: [],
                borderColor: 'rgb(75, 192, 192)',
                tension: 0.1,
                fill: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: false,
                    ticks: {
                        callback: function(value) {
                            return '$' + value.toLocaleString();
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return '$' + context.parsed.y.toLocaleString();
                        }
                    }
                }
            }
        }
    });
}

// Event Listeners
document.addEventListener('DOMContentLoaded', () => {
    checkSession();
    setupEventListeners();
    loadPortfolioData();
    loadSuggestions();
    loadMarketData();
    loadRecentActivity();
    startMarketDataUpdates();
    setupVideoSubtitles();
    initializeStockSearch();
    
    // Initialize chart when dashboard is shown
    const dashboardSection = document.getElementById('dashboard');
    if (dashboardSection.classList.contains('active')) {
        loadPortfolioPerformance();
    }
});

// Setup Event Listeners
function setupEventListeners() {
    // Navigation
    navItems.forEach(item => {
        item.addEventListener('click', (e) => {
            e.preventDefault();
            const section = e.currentTarget.dataset.section;
            switchSection(section);
        });
    });

    // Authentication
    loginBtn.addEventListener('click', showLoginModal);
    logoutBtn.addEventListener('click', handleLogout);
    authForm.addEventListener('submit', handleLogin);
    document.getElementById('registerBtn').addEventListener('click', handleRegister);

    // Chat
    sendBtn.addEventListener('click', sendMessage);
    userInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') sendMessage();
    });

    // Portfolio
    document.getElementById('addStockBtn')?.addEventListener('click', showAddStockModal);
    addStockForm?.addEventListener('submit', handleAddStock);
    portfolioSettingsForm?.addEventListener('submit', handlePortfolioSettings);

    // Location
    locationBtn?.addEventListener('click', showLocationModal);

    // Close buttons for all modals
    document.querySelectorAll('.modal .close').forEach(closeBtn => {
        closeBtn.addEventListener('click', () => {
            closeBtn.closest('.modal').classList.remove('active');
            document.body.style.overflow = '';
        });
    });

    // Stock Search
    document.getElementById('searchStockBtn')?.addEventListener('click', searchStock);
    document.getElementById('stockSearchInput')?.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') searchStock();
    });
}

// Navigation
function switchSection(section) {
    // Update active nav item
    navItems.forEach(item => {
        item.classList.toggle('active', item.dataset.section === section);
    });

    // Update active section
    document.querySelectorAll('.section').forEach(s => {
        s.classList.toggle('active', s.id === section);
    });

    activeSection = section;

    if (section === 'dashboard') {
        loadPortfolioPerformance();
    }
}

// Authentication
function showLoginModal() {
    loginModal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function hideLoginModal() {
    loginModal.classList.remove('active');
    document.body.style.overflow = '';
}

async function handleLogin(e) {
    e.preventDefault();
    const username = document.getElementById('authUsername').value;
    const password = document.getElementById('authPassword').value;

    try {
        const response = await fetch(`${API_BASE_URL}/api/auth.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'login',
                username,
                password
            })
        });

        const data = await response.json();
        if (data.success) {
            currentUser = username;
            currentSession = data.sessionId;
            updateUIForLoggedInUser();
            hideLoginModal();
            loadPortfolioData();
            loadSuggestions();
        } else {
            showError(data.message);
        }
    } catch (error) {
        showError('Login failed. Please try again.');
    }
}

async function handleRegister() {
    const username = document.getElementById('authUsername').value;
    const password = document.getElementById('authPassword').value;

    if (!username || !password) {
        showError('Please fill in all fields');
        return;
    }

    try {
        const response = await fetch(`${API_BASE_URL}/api/auth.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'register',
                username,
                password
            })
        });

        const data = await response.json();
        if (data.success) {
            showSuccess('Registration successful! Please login.');
            // Switch to login mode
            document.getElementById('registerBtn').style.display = 'none';
            document.getElementById('loginSubmit').textContent = 'Login';
            // Clear the form
            document.getElementById('authUsername').value = '';
            document.getElementById('authPassword').value = '';
        } else {
            showError(data.message || 'Registration failed. Please try again.');
        }
    } catch (error) {
        console.error('Registration error:', error);
        showError('Registration failed. Please try again.');
    }
}

async function handleLogout() {
    try {
        const response = await fetch(`${API_BASE_URL}/api/auth.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'logout',
                sessionId: currentSession
            })
        });

        const data = await response.json();
        if (data.success) {
            currentUser = null;
            currentSession = null;
            updateUIForLoggedOutUser();
            clearPortfolioData();
            clearSuggestions();
        }
    } catch (error) {
        showError('Logout failed. Please try again.');
    }
}

async function checkSession() {
    try {
        const response = await fetch(`${API_BASE_URL}/api/auth.php?action=check_session`, {
            method: 'GET',
            headers: { 
                'Accept': 'application/json'
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        if (data.loggedIn) {
            currentUser = data.username;
            currentSession = data.sessionId;
            updateUIForLoggedInUser();
            loadPortfolioData();
            loadSuggestions();
        }
    } catch (error) {
        console.error('Session check failed:', error);
        // Don't show error to user, just keep them as guest
        currentUser = null;
        currentSession = null;
        updateUIForLoggedOutUser();
    }
}

// UI Updates
function updateUIForLoggedInUser() {
    usernameDisplay.textContent = currentUser;
    loginBtn.style.display = 'none';
    logoutBtn.style.display = 'block';
}

function updateUIForLoggedOutUser() {
    usernameDisplay.textContent = 'Guest';
    loginBtn.style.display = 'block';
    logoutBtn.style.display = 'none';
}

// Chat
async function sendMessage() {
    const message = userInput.value.trim();
    if (!message) return;

    // Add user message to chat
    addMessageToChat(message, 'user');
    userInput.value = '';

    try {
        const response = await fetch(`${API_BASE_URL}/api/chat.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'send_message',
                message,
                sessionId: currentSession
            })
        });

        const data = await response.json();
        if (data.success) {
            addMessageToChat(data.response, 'bot');
        } else {
            showError(data.message);
        }
    } catch (error) {
        showError('Failed to send message. Please try again.');
    }
}

function addMessageToChat(message, type) {
    const messageDiv = document.createElement('div');
    messageDiv.className = `message ${type}-message fade-in`;
    messageDiv.innerHTML = `
        <div class="message-content">
            <div class="message-text">${escapeHtml(message)}</div>
            <div class="message-time">${new Date().toLocaleTimeString()}</div>
        </div>
    `;
    chatMessages.appendChild(messageDiv);
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

// Portfolio
async function loadPortfolioData() {
    if (!currentSession) {
        // Show empty state for guests
        portfolioDisplay.innerHTML = `
            <div class="portfolio-stats">
                <div class="stat-item">
                    <div class="stat-value">$0</div>
                    <div class="stat-label">Total Value</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">0</div>
                    <div class="stat-label">Total Stocks</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">N/A</div>
                    <div class="stat-label">Risk Level</div>
                </div>
            </div>
        `;
        return;
    }

    try {
        const response = await fetch(`${API_BASE_URL}/api/portfolio.php`, {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                action: 'get_portfolio',
                sessionId: currentSession
            })
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        if (data.success) {
            updatePortfolioDisplay(data.portfolio);
        } else {
            throw new Error(data.message || 'Failed to load portfolio data');
        }
    } catch (error) {
        console.error('Portfolio data error:', error);
        showError('Failed to load portfolio data. Please try again.');
    }
}

function updatePortfolioDisplay(portfolio) {
    if (!portfolio) return;

    const stats = calculatePortfolioStats(portfolio);
    portfolioDisplay.innerHTML = `
        <div class="portfolio-stats">
            <div class="stat-item">
                <div class="stat-value">$${stats.totalValue.toLocaleString()}</div>
                <div class="stat-label">Total Value</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">${stats.totalStocks}</div>
                <div class="stat-label">Total Stocks</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">${stats.riskLevel}</div>
                <div class="stat-label">Risk Level</div>
            </div>
        </div>
    `;

    // Update portfolio table
    const tableBody = document.getElementById('portfolioTableBody');
    if (tableBody) {
        tableBody.innerHTML = portfolio.stocks.map(stock => `
            <tr class="fade-in">
                <td>${stock.symbol}</td>
                <td>${stock.name}</td>
                <td>${stock.sector}</td>
                <td>${stock.shares.toLocaleString()}</td>
                <td>$${stock.avg_price.toLocaleString()}</td>
                <td>$${(stock.shares * stock.avg_price).toLocaleString()}</td>
                <td>
                    <button class="btn-secondary btn-sm" onclick="removeStock('${stock.symbol}')">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `).join('');
    }

    // Initialize chart if needed
    if (typeof Chart !== 'undefined' && !portfolioChart) {
        initializePortfolioChart();
    }
}

function calculatePortfolioStats(portfolio) {
    const totalValue = portfolio.stocks.reduce((sum, stock) => 
        sum + (stock.shares * stock.avg_price), 0);
    
    return {
        totalValue,
        totalStocks: portfolio.stocks.length,
        riskLevel: portfolio.risk_tolerance
    };
}

// Suggestions
async function loadSuggestions() {
    if (!currentSession) return;

    try {
        const response = await fetch(`${API_BASE_URL}/api/suggestions.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'get_suggestions',
                sessionId: currentSession
            })
        });

        const data = await response.json();
        if (data.success) {
            updateSuggestionsDisplay(data.suggestions);
        }
    } catch (error) {
        showError('Failed to load suggestions.');
    }
}

function updateSuggestionsDisplay(suggestions) {
    suggestionsDisplay.innerHTML = suggestions.map(suggestion => `
        <div class="suggestion-card fade-in">
            <h3>${suggestion.name}</h3>
            <div class="suggestion-symbol">${suggestion.symbol}</div>
            <div class="suggestion-reason">${suggestion.reason}</div>
            <div class="suggestion-metrics">
                <div class="metric">
                    <span class="label">Expected Return:</span>
                    <span class="value">${suggestion.expected_return}%</span>
                </div>
                <div class="metric">
                    <span class="label">Risk Level:</span>
                    <span class="value">${suggestion.risk_level}</span>
                </div>
            </div>
        </div>
    `).join('');
}

// Market Data
async function loadMarketData() {
    try {
        const response = await fetch(`${API_BASE_URL}/api/market.php`, {
            method: 'GET',
            headers: { 
                'Accept': 'application/json'
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        if (data.success) {
            marketData = data.market;
            updateMarketDisplay();
        } else {
            console.error('Market data error:', data.message);
        }
    } catch (error) {
        console.error('Failed to load market data:', error);
        // Show default values
        sp500Value.textContent = 'N/A';
        nasdaqValue.textContent = 'N/A';
        dowValue.textContent = 'N/A';
    }
}

function updateMarketDisplay() {
    if (!marketData) return;

    sp500Value.textContent = `$${marketData.sp500.toLocaleString()}`;
    nasdaqValue.textContent = `$${marketData.nasdaq.toLocaleString()}`;
    dowValue.textContent = `$${marketData.dow.toLocaleString()}`;

    // Update indicators
    document.querySelectorAll('.market-indicator i').forEach((icon, index) => {
        const value = [marketData.sp500_change, marketData.nasdaq_change, marketData.dow_change][index];
        icon.className = value >= 0 ? 'fas fa-arrow-up' : 'fas fa-arrow-down';
        icon.style.color = value >= 0 ? 'var(--success-color)' : 'var(--danger-color)';
    });
}

function startMarketDataUpdates() {
    // Update market data every 5 minutes
    setInterval(loadMarketData, 5 * 60 * 1000);
}

// Portfolio Management
function showAddStockModal() {
    addStockModal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

async function handleAddStock(e) {
    e.preventDefault();
    const stockData = {
        symbol: document.getElementById('stockSymbol').value,
        name: document.getElementById('stockName').value,
        shares: parseInt(document.getElementById('stockShares').value),
        avg_price: parseFloat(document.getElementById('stockPrice').value),
        sector: document.getElementById('stockSector').value
    };

    try {
        const response = await fetch(`${API_BASE_URL}/api/portfolio.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'add_stock',
                sessionId: currentSession,
                stock: stockData
            })
        });

        const data = await response.json();
        if (data.success) {
            showSuccess('Stock added successfully!');
            addStockModal.classList.remove('active');
            document.body.style.overflow = '';
            loadPortfolioData();
            loadRecentActivity();
        } else {
            showError(data.message);
        }
    } catch (error) {
        showError('Failed to add stock. Please try again.');
    }
}

async function handlePortfolioSettings(e) {
    e.preventDefault();
    const settings = {
        risk_tolerance: document.getElementById('riskTolerance').value,
        investment_goal: document.getElementById('investmentGoal').value
    };

    try {
        const response = await fetch(`${API_BASE_URL}/api/portfolio.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'update_settings',
                sessionId: currentSession,
                settings: settings
            })
        });

        const data = await response.json();
        if (data.success) {
            showSuccess('Portfolio settings updated!');
            portfolioSettingsModal.classList.remove('active');
            document.body.style.overflow = '';
            loadPortfolioData();
            loadSuggestions();
        } else {
            showError(data.message);
        }
    } catch (error) {
        showError('Failed to update settings. Please try again.');
    }
}

// Recent Activity
async function loadRecentActivity() {
    if (!currentSession) return;

    try {
        const response = await fetch(`${API_BASE_URL}/api/activity.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'get_recent',
                sessionId: currentSession
            })
        });

        const data = await response.json();
        if (data.success) {
            updateActivityDisplay(data.activities);
        }
    } catch (error) {
        console.error('Failed to load recent activity:', error);
    }
}

function updateActivityDisplay(activities) {
    activityList.innerHTML = activities.map(activity => `
        <div class="activity-item fade-in">
            <div class="activity-icon">
                <i class="fas ${getActivityIcon(activity.type)}"></i>
            </div>
            <div class="activity-content">
                <div class="activity-title">${activity.title}</div>
                <div class="activity-time">${formatTime(activity.timestamp)}</div>
            </div>
        </div>
    `).join('');
}

function getActivityIcon(type) {
    switch (type) {
        case 'add_stock': return 'fa-plus-circle';
        case 'remove_stock': return 'fa-minus-circle';
        case 'update_settings': return 'fa-cog';
        case 'chat': return 'fa-comment';
        default: return 'fa-info-circle';
    }
}

function formatTime(timestamp) {
    const date = new Date(timestamp);
    const now = new Date();
    const diff = now - date;
    
    if (diff < 60000) return 'Just now';
    if (diff < 3600000) return `${Math.floor(diff / 60000)}m ago`;
    if (diff < 86400000) return `${Math.floor(diff / 3600000)}h ago`;
    return date.toLocaleDateString();
}

// Utility Functions
function escapeHtml(unsafe) {
    return unsafe
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

function showError(message) {
    const errorDiv = document.createElement('div');
    errorDiv.className = 'notification error fade-in';
    errorDiv.textContent = message;
    document.body.appendChild(errorDiv);
    setTimeout(() => errorDiv.remove(), 3000);
}

function showSuccess(message) {
    const successDiv = document.createElement('div');
    successDiv.className = 'notification success fade-in';
    successDiv.textContent = message;
    document.body.appendChild(successDiv);
    setTimeout(() => successDiv.remove(), 3000);
}

function clearPortfolioData() {
    portfolioDisplay.innerHTML = '';
}

function clearSuggestions() {
    suggestionsDisplay.innerHTML = '';
}

// Add remove stock functionality
async function removeStock(symbol) {
    if (!currentSession) return;

    try {
        const response = await fetch(`${API_BASE_URL}/api/portfolio.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'remove_stock',
                sessionId: currentSession,
                symbol: symbol
            })
        });

        const data = await response.json();
        if (data.success) {
            showSuccess('Stock removed successfully!');
            loadPortfolioData();
            loadRecentActivity();
        } else {
            showError(data.message);
        }
    } catch (error) {
        showError('Failed to remove stock. Please try again.');
    }
}

function setupVideoSubtitles() {
    if (!marketVideo || !subtitleTrack) return;

    // Toggle subtitles button
    toggleSubtitlesBtn?.addEventListener('click', () => {
        subtitlesEnabled = !subtitlesEnabled;
        subtitleTrack.style.display = subtitlesEnabled ? 'block' : 'none';
        toggleSubtitlesBtn.innerHTML = `<i class="fas fa-closed-captioning"></i> ${subtitlesEnabled ? 'Hide' : 'Show'} Subtitles`;
    });

    // Update subtitles based on video time
    marketVideo.addEventListener('timeupdate', () => {
        if (!subtitlesEnabled) return;

        const currentTime = marketVideo.currentTime;
        const currentSubtitle = subtitles.find(sub => 
            currentTime >= sub.start && currentTime <= sub.end
        );

        if (currentSubtitle) {
            subtitleTrack.textContent = currentSubtitle.text;
        } else {
            subtitleTrack.textContent = '';
        }
    });

    // Handle video loading
    marketVideo.addEventListener('loadedmetadata', () => {
        console.log('Video loaded successfully');
    });

    // Handle video errors
    marketVideo.addEventListener('error', (e) => {
        console.error('Video loading error:', e);
        showError('Failed to load video. Please try again later.');
    });
}

// Location Modal Functions
function showLocationModal() {
    locationModal.classList.add('active');
    document.body.style.overflow = 'hidden';
}