# QuickByteFacts 🎬

<p align="center">
  <strong>Automated AI-Powered News-to-Video Content Creation System</strong>
</p>

<p align="center">
  Transform trending news articles into engaging short-form videos and automatically publish them across YouTube, TikTok, and Instagram.
</p>

> ⚠️ **Notice**: This is proprietary software shared for portfolio/demonstration purposes. 
> Commercial use, copying, or distribution without permission is prohibited. 
> See [LICENSE](LICENSE) for full terms.

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-12.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel Version">
  <img src="https://img.shields.io/badge/PHP-8.2+-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP Version">
  <img src="https://img.shields.io/badge/License-Proprietary-red.svg?style=for-the-badge" alt="License">
</p>

<p align="center">
  <strong>🎬 See It In Action</strong><br>
  <a href="https://www.youtube.com/@DailyQuickByte" target="_blank">
    <img src="https://img.shields.io/badge/YouTube-FF0000?style=for-the-badge&logo=youtube&logoColor=white" alt="YouTube">
  </a>
  <a href="https://www.instagram.com/dailyquickbyte/" target="_blank">
    <img src="https://img.shields.io/badge/Instagram-E4405F?style=for-the-badge&logo=instagram&logoColor=white" alt="Instagram">
  </a>
</p>

---

## 📋 Table of Contents

- [Overview](#overview)
- [Live Examples](#-live-examples)
- [Features](#features)
- [Tech Stack](#tech-stack)
- [Architecture](#architecture)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
- [Project Structure](#project-structure)
- [Development](#development)
- [Contributing](#contributing)
- [License](#license)

## 🎯 Overview

QuickByteFacts is an enterprise-grade Laravel application that automates the entire content creation pipeline—from news aggregation to video production and multi-platform distribution. The system uses cutting-edge AI technologies to classify news, generate scripts, create video assets, and intelligently schedule uploads across major social media platforms.

### Key Capabilities

- 🔄 **Automated News Aggregation**: Fetches trending news from multiple sources (NewsAPI, GNews)
- 🤖 **AI-Powered Classification**: Uses OpenAI GPT-4 to categorize and filter news content
- 🎭 **Smart Content Filtering**: Vector embeddings with Pinecone to eliminate duplicate content
- ✍️ **Script Generation**: AI-generated video scripts optimized for short-form content
- 🎨 **Asset Creation**: Automatic generation of visuals and voiceovers for each scene
- 🎬 **Video Assembly**: Professional video composition with transitions, subtitles, and background music
- 📅 **Intelligent Scheduling**: Optimized posting times based on platform best practices
- 🚀 **Multi-Platform Upload**: Automated uploads to YouTube, TikTok, and Instagram Reels

## 🎬 Live Examples

This project is currently in production and actively generating content! Check out the automated videos created by this system:

- **📺 [YouTube Channel - DailyQuickByte](https://www.youtube.com/@DailyQuickByte)**: Watch the latest automated news videos
- **📸 [Instagram - @dailyquickbyte](https://www.instagram.com/dailyquickbyte/)**: Follow for daily automated content posts

These channels are fully automated and showcase the complete pipeline in action—from news fetching to video generation and publishing.

## ✨ Features

### News Processing Pipeline
- **Multi-source News Fetching**: Aggregates news from NewsAPI and GNews with configurable adapters
- **AI Classification**: Categorizes news articles using OpenAI GPT-4
- **Novelty Filtering**: Uses Pinecone vector database to prevent duplicate content
- **Article Extraction**: Intelligent content scraping with Readability parser
- **AI Summarization**: Automatic article summarization using Hugging Face models

### Content Generation
- **Batch Script Generation**: Efficient OpenAI batch API integration for script creation
- **Scene-based Structure**: Multi-scene video scripts with hooks, transitions, and metadata
- **Visual Asset Generation**: AI-powered video generation using Google Cloud Veo
- **Voiceover Synthesis**: ElevenLabs integration for natural-sounding narration
- **Background Music**: Intelligent music selection and mixing

### Video Production
- **Professional Video Assembly**: FFmpeg-based video composition with transitions
- **Word-level Subtitles**: Dynamic ASS subtitle generation with precise timing
- **Audio Mixing**: Background music and sound effects integration
- **Vertical Format**: Optimized for 9:16 aspect ratio (TikTok, Reels, Shorts)

### Distribution & Scheduling
- **Platform Integration**: OAuth2 authentication for YouTube, TikTok, and Instagram
- **Smart Scheduling**: Optimal posting times based on platform analytics
- **Upload Management**: Queue-based upload system with retry logic
- **Status Tracking**: Real-time monitoring of upload status and responses

### Developer Experience
- **Laravel Horizon**: Beautiful queue monitoring dashboard
- **Laravel Telescope**: Comprehensive debugging and monitoring
- **Docker Support**: Full containerization with Laravel Sail
- **Model Caching**: Performance optimization with intelligent caching

## 🛠 Tech Stack

### Backend
- **Framework**: Laravel 12.x
- **Language**: PHP 8.2+
- **Queue System**: Redis + Laravel Horizon
- **Database**: MySQL 8.0
- **Cache**: Redis

### AI & ML
- **OpenAI API**: GPT-4 for classification and script generation
- **Pinecone**: Vector database for embeddings and similarity search
- **Hugging Face**: Article summarization models
- **Google Cloud Veo**: AI video generation
- **ElevenLabs**: Voice synthesis
- **PHP-ML**: Machine learning utilities

### Media Processing
- **FFmpeg**: Video composition, transitions, and audio mixing
- **Browsershot**: Web scraping and screenshot capabilities
- **Google Cloud Storage**: Media asset storage

### Frontend
- **Tailwind CSS 4**: Modern, utility-first CSS framework
- **Vite**: Next-generation frontend build tool
- **Axios**: HTTP client for API requests

### Infrastructure
- **Docker**: Containerization with Laravel Sail
- **ngrok**: Local development tunneling
- **Google Cloud Platform**: Cloud storage and AI services

## 🏗 Architecture

### Pipeline Flow

```
News Fetching → Classification → Novelty Filtering → Article Extraction 
→ Summarization → Script Generation → Asset Generation → Video Assembly 
→ Upload Scheduling → Multi-Platform Distribution
```

### Event-Driven Architecture

The application uses Laravel's event system to create a decoupled, scalable pipeline:

1. **FetchNewsJob** → Triggers `NewsFetchedEvent`
2. **ClassifyNewsJob** → Processes and classifies news
3. **NewsEmbeddingsJob** → Creates vector embeddings
4. **NewsNoveltyFilterJob** → Filters duplicate content
5. **FetchArticleSummaryJob** → Extracts and summarizes articles
6. **GenerateScriptJob** → Creates video scripts via OpenAI batch API
7. **StoreGeneratedScriptJob** → Saves and processes scripts
8. **GenerateAssetsJob** → Creates visuals and voiceovers
9. **ComposeVideoJob** → Assembles final video
10. **ScheduleVideoUploadJob** → Schedules uploads
11. **UploadVideoJob** → Publishes to platforms

### Scheduled Tasks

- **Daily**: `FetchNewsJob` - Fetch new news articles
- **Every 5 minutes**: Process OpenAI batches, queued assets, and ready scripts
- **Every minute**: Process scheduled uploads

## 📦 Requirements

- PHP 8.2 or higher
- Composer 2.x
- Node.js 18+ and npm
- MySQL 8.0+
- Redis 6.0+
- FFmpeg 5.0+ (with libass support)
- Docker & Docker Compose (optional, for Sail)

## 🚀 Installation

### Prerequisites

Ensure you have PHP 8.2+, Composer, Node.js, and MySQL installed on your system.

### Step 1: Clone the Repository

```bash
git clone git@github.com:AbdAlhadiJ/quick-byte.git
cd QuickByteFacts
```

### Step 2: Install Dependencies

```bash
# Install PHP dependencies
composer install

# Install Node.js dependencies
npm install
```

### Step 3: Environment Configuration

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### Step 4: Configure Environment Variables

Edit `.env` file with your configuration (see [Configuration](#configuration) section).

### Step 5: Database Setup

```bash
# Run migrations
php artisan migrate

# (Optional) Seed database with sample data
php artisan db:seed
```

### Step 6: Build Frontend Assets

```bash
# Development
npm run dev

# Production
npm run build
```

### Step 7: Start Queue Workers

```bash
# Start Horizon dashboard
php artisan horizon

# Or use queue:work in development
php artisan queue:work
```

### Step 8: Start Development Server

```bash
php artisan serve
```

The application will be available at `http://localhost:8000`

### Using Docker (Laravel Sail)

```bash
# Start containers
./vendor/bin/sail up -d

# Install dependencies
./vendor/bin/sail composer install
./vendor/bin/sail npm install

# Run migrations
./vendor/bin/sail artisan migrate

# Access application at http://localhost
```

## ⚙️ Configuration

### Required Environment Variables

#### Application
```env
APP_NAME="QuickByteFacts"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost
```

#### Database
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=quickbytefacts
DB_USERNAME=root
DB_PASSWORD=
```

#### Redis (Queue & Cache)
```env
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

#### OpenAI
```env
OPENAI_API_KEY=your_openai_api_key
OPENAI_BATCH_SIZE=50
OPENAI_CHAT_ENDPOINT=/v1/chat/completions
```

#### Pinecone (Vector Database)
```env
PINECONE_API_KEY=your_pinecone_api_key
PINECONE_INDEXHOST=your_index_host
```

#### News Sources
```env
# NewsAPI
NEWSAPI_ENABLED=true
NEWSAPI_API_KEY=your_newsapi_key
NEWSAPI_LIMIT=25

# GNews
GNEWS_ENABLED=true
GNEWS_API_KEY=your_gnews_key
GNEWS_LIMIT=25
```

#### Google Cloud (Video Generation & Storage)
```env
GOOGLE_APPLICATION_CREDENTIALS=/path/to/service-account.json
GCP_PROJECT_ID=your_project_id
GCP_LOCATION=us-central1
GCP_RESULTS_BUCKET=your_bucket_name
GCP_MODEL=veo-2.0-generate-001
```

#### ElevenLabs (Voice Synthesis)
```env
ELEVENLABS_API_KEY=your_elevenlabs_key
```

#### Platform OAuth Credentials

**YouTube:**
```env
YOUTUBE_APPLICATION_CREDENTIALS=/path/to/youtube-credentials.json
YOUTUBE_REFRESH_TOKEN=your_refresh_token
```

**TikTok:**
```env
TIKTOK_CLIENT_KEY=your_client_key
TIKTOK_CLIENT_SECRET=your_client_secret
TIKTOK_REDIRECT_URI=http://localhost/oauth2/tiktok/callback
```

**Instagram:**
```env
IG_APP_ID=your_app_id
IG_APP_SECRET=your_app_secret
IG_REDIRECT_URI=http://localhost/oauth2/instagram/callback
```

#### Other Services
```env
# Article Scraping
SCRAPDO_API_KEY=your_scrapdo_key

# Summarization
HUGGINGFACE_API_KEY=your_huggingface_key
HUGGINGFACE_MODEL=facebook/bart-large-cnn

# Upload Schedule
UPLOAD_SCHEDULE_MODE=weekly  # or 'daily'
```

## 📖 Usage

### Dashboard

Access the dashboard at `/dashboard` after logging in to:
- Monitor upload status
- Retry failed uploads
- Manage scheduled content
- Configure platform settings

### Platform Authorization

Authorize platforms via the integrations page:
- Navigate to `/admin/authorize/{platform}` (youtube, tiktok, instagram)
- Complete OAuth flow
- Credentials are stored automatically

### Manual Job Execution

```bash
# Fetch news manually
php artisan queue:work --queue=default

# Process OpenAI batches
php artisan batches:process

# Process queued assets
php artisan assets:process

# Process ready scripts
php artisan scripts:process

# Process scheduled uploads (with force option)
php artisan uploads:query --force
```

### Monitoring

- **Horizon Dashboard**: `/horizon` - Queue monitoring
- **Telescope Dashboard**: `/telescope` - Application debugging

## 📁 Project Structure

```
QuickByteFacts/
├── app/
│   ├── Console/          # Artisan commands
│   ├── Contracts/        # Service interfaces
│   ├── Enums/            # Enumeration classes
│   ├── Events/           # Event classes
│   ├── Http/             # Controllers, middleware
│   ├── Jobs/             # Queue jobs
│   ├── Listeners/        # Event listeners
│   ├── Models/           # Eloquent models
│   ├── Services/         # Business logic services
│   │   ├── Embeddings/   # Vector embedding services
│   │   ├── Media/        # Media processing
│   │   ├── News/         # News fetching & processing
│   │   ├── OpenAi/       # OpenAI integration
│   │   ├── PlatformAuth/ # OAuth implementations
│   │   ├── Scrappers/    # Web scraping
│   │   ├── Script/       # Script generation
│   │   ├── Uploader/     # Platform uploaders
│   │   └── VideoAssembler/ # Video composition
│   └── Providers/        # Service providers
├── config/               # Configuration files
├── database/
│   ├── migrations/       # Database migrations
│   └── seeders/          # Database seeders
├── resources/
│   ├── views/            # Blade templates
│   ├── css/              # Stylesheets
│   └── js/               # JavaScript
├── routes/
│   ├── web.php           # Web routes
│   └── console.php       # Scheduled tasks
└── tests/                # Test suite
```

## 💻 Development

### Running the Development Environment

```bash
# Using Composer script (runs all services concurrently)
composer dev

# This runs:
# - Laravel development server
# - Queue worker
# - Laravel Pail (logs)
# - Vite dev server
```

### Code Style

The project uses Laravel Pint for code formatting:

```bash
./vendor/bin/pint
```

### Testing

```bash
# Run tests
php artisan test

# Or with PHPUnit
./vendor/bin/phpunit
```

### Database Migrations

```bash
# Create migration
php artisan make:migration create_example_table

# Run migrations
php artisan migrate

# Rollback
php artisan migrate:rollback
```

## 🤝 Contributing

This repository is a portfolio project. While contributions are appreciated, please note:

- This is **proprietary software** - all contributions become the property of the copyright holder
- For substantial contributions, please contact me first to discuss terms
- Small bug fixes and improvements are welcome via pull requests
- By contributing, you agree that your contributions may be used under the same proprietary license

### Contact

For collaboration or contribution inquiries, please contact: **abdalhadijouda@gmail.com**

### Code of Conduct

Please be respectful and professional in all interactions. Harassment or discriminatory behavior will not be tolerated.

## 📝 License

**Copyright (c) 2024 AbdAlhadi Jouda. All Rights Reserved.**

This software is **proprietary and confidential**. 

This repository is made available for **portfolio and demonstration purposes only**. 

### Permitted Uses:
- ✅ Viewing and examining the code for learning purposes
- ✅ Reference in portfolio/showcase contexts
- ✅ Evaluation by potential employers or collaborators

### Prohibited Uses:
- ❌ Commercial use without explicit permission
- ❌ Copying, modifying, or creating derivative works
- ❌ Redistribution or sublicensing
- ❌ Using this code in competing products

### Commercial Licensing:

For commercial use or licensing inquiries, please contact: **abdalhadijouda@gmail.com**

See the [LICENSE](LICENSE) file for complete terms and conditions.

## 🙏 Acknowledgments

- [Laravel](https://laravel.com) - The PHP Framework for Web Artisans
- [OpenAI](https://openai.com) - AI-powered script generation
- [Pinecone](https://www.pinecone.io) - Vector database for embeddings
- [FFmpeg](https://ffmpeg.org) - Multimedia framework
- All the amazing open-source contributors

## 📧 Support

For issues, questions, or contributions, please use the [GitHub Issues](https://github.com/AbdAlhadiJ/quick-byte/issues) page.

---

<p align="center">Made with ❤️ using Laravel</p>
