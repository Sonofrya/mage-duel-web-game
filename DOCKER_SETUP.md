# 🐳 Docker Setup Guide for Mage Duel

This guide provides detailed instructions for setting up and running Mage Duel using Docker containers.

## 📋 Prerequisites

Before starting, ensure you have:

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) installed
- [Docker Compose](https://docs.docker.com/compose/install/) (usually included with Docker Desktop)
- At least 4GB of available RAM
- 2GB of free disk space

## 🚀 Quick Start

### Windows Users
1. Clone the repository
2. Double-click `docker-start.bat`
3. Wait for containers to build and start
4. Open http://localhost:8080 in your browser

### Linux/macOS Users
```bash
# Clone and start
git clone https://github.com/Sonofrya/mage-duel-web-game.git
cd mage-duel-web-game
docker-compose up --build -d

# View logs
docker-compose logs -f
```

## 🏗️ Architecture Overview

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Web Browser   │    │   Web Browser   │    │   Web Browser   │
│   (Port 8080)   │    │   (Port 8081)   │    │   (Port 80)     │
└─────────┬───────┘    └─────────┬───────┘    └─────────┬───────┘
          │                      │                      │
          │                      │                      │
┌─────────▼───────┐    ┌─────────▼───────┐    ┌─────────▼───────┐
│   PHP/Apache    │    │   WebSocket     │    │   Nginx         │
│   Container     │    │   Container     │    │   Container     │
│   (web)         │    │   (websocket)   │    │   (nginx)       │
└─────────┬───────┘    └─────────────────┘    └─────────┬───────┘
          │                                            │
          └────────────────┬───────────────────────────┘
                           │
                ┌─────────▼───────┐
                │   PostgreSQL    │
                │   Container     │
                │   (postgres)    │
                └─────────────────┘
```

## 🔧 Services Configuration

### 1. PostgreSQL Database (`postgres`)
- **Port**: 5432
- **Database**: mydatabase
- **User**: admin
- **Password**: 1
- **Volume**: `postgres_data` (persistent storage)

### 2. PHP/Apache Web Server (`web`)
- **Port**: 8080
- **Document Root**: /var/www/html
- **PHP Version**: 8.2
- **Extensions**: pdo_pgsql, opcache

### 3. WebSocket Server (`websocket`)
- **Port**: 8081
- **Protocol**: WebSocket
- **Real-time**: Game communication

### 4. Nginx Reverse Proxy (`nginx`) - Optional
- **Port**: 80 (HTTP), 443 (HTTPS)
- **Profile**: production
- **Features**: Load balancing, SSL termination

## 📁 File Structure

```
mage-duel-web-game/
├── docker/
│   ├── apache/
│   │   └── 000-default.conf      # Apache virtual host config
│   ├── nginx/
│   │   └── default.conf          # Nginx server config
│   └── php/
│       └── php.ini               # PHP configuration
├── Dockerfile                    # Main application image
├── docker-compose.yml           # Multi-service orchestration
├── .env.example                 # Environment variables template
├── docker-start.bat             # Windows start script
├── docker-stop.bat              # Windows stop script
└── docker-logs.bat              # Windows logs viewer
```

## ⚙️ Environment Variables

Create a `.env` file from the example:
```bash
cp .env.example .env
```

### Key Variables:
```env
# Database
DB_HOST=postgres
DB_NAME=mydatabase
DB_USER=admin
DB_PASSWORD=1

# WebSocket
WEBSOCKET_HOST=0.0.0.0
WEBSOCKET_PORT=8081

# Application
APP_ENV=development
APP_DEBUG=true
```

## 🎮 Running the Game

### Development Mode
```bash
# Start all services
docker-compose up -d

# View logs
docker-compose logs -f

# Stop services
docker-compose stop
```

### Production Mode
```bash
# Start with Nginx
docker-compose --profile production up -d

# Access via Nginx (port 80)
# WebSocket automatically proxied to port 8081
```

## 🔍 Troubleshooting

### Common Issues

#### 1. Port Already in Use
```bash
# Check what's using the port
netstat -tulpn | grep :8080

# Stop conflicting services or change ports in docker-compose.yml
```

#### 2. Database Connection Failed
```bash
# Check database container
docker-compose logs postgres

# Restart database
docker-compose restart postgres
```

#### 3. WebSocket Not Connecting
```bash
# Check WebSocket container
docker-compose logs websocket

# Verify port mapping
docker-compose ps
```

#### 4. Permission Issues
```bash
# Fix file permissions
sudo chown -R $USER:$USER .

# Rebuild containers
docker-compose up --build --force-recreate
```

### Debug Commands

```bash
# Check container status
docker-compose ps

# View specific service logs
docker-compose logs web
docker-compose logs postgres
docker-compose logs websocket

# Execute commands inside container
docker-compose exec web bash
docker-compose exec postgres psql -U admin -d mydatabase

# Monitor resource usage
docker stats

# Clean up everything
docker-compose down -v --rmi all
```

## 🔄 Development Workflow

### Making Changes
1. Edit source code
2. Changes are automatically reflected (volume mounting)
3. For PHP changes, no restart needed
4. For WebSocket changes, restart: `docker-compose restart websocket`

### Database Changes
1. Modify `setup_database.sql`
2. Recreate database: `docker-compose down && docker-compose up -d`
3. Or connect directly: `docker-compose exec postgres psql -U admin -d mydatabase`

### Adding Dependencies
1. Update `composer.json`
2. Rebuild: `docker-compose up --build`

## 📊 Performance Optimization

### Resource Limits
Add to `docker-compose.yml`:
```yaml
services:
  web:
    deploy:
      resources:
        limits:
          memory: 512M
        reservations:
          memory: 256M
```

### Caching
- PHP OpCache enabled by default
- Static files served with proper headers
- Database connections pooled

### Scaling
```bash
# Scale web service
docker-compose up --scale web=3

# Use with load balancer (Nginx)
docker-compose --profile production up
```

## 🔒 Security Considerations

### Production Setup
1. Change default passwords in `.env`
2. Use HTTPS with SSL certificates
3. Enable firewall rules
4. Regular security updates
5. Database backup strategy

### Network Security
```yaml
# In docker-compose.yml
networks:
  mage-duel-network:
    driver: bridge
    internal: false  # Set to true for internal-only network
```

## 📈 Monitoring

### Health Checks
```bash
# Check all services
curl http://localhost:8080/health
curl http://localhost:8081/health

# Database connectivity
docker-compose exec postgres pg_isready -U admin
```

### Logs Management
```bash
# Rotate logs
docker-compose exec web logrotate /etc/logrotate.conf

# Centralized logging (optional)
# Add ELK stack or similar
```

## 🚀 Deployment

### Cloud Deployment
1. **AWS**: Use ECS or EKS
2. **Google Cloud**: Use Cloud Run or GKE
3. **Azure**: Use Container Instances or AKS
4. **DigitalOcean**: Use App Platform or Kubernetes

### CI/CD Pipeline
```yaml
# Example GitHub Actions
name: Deploy Mage Duel
on:
  push:
    branches: [main]
jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Deploy to production
        run: |
          docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d
```

## 📞 Support

If you encounter issues:
1. Check this guide first
2. Review Docker logs: `docker-compose logs -f`
3. Search existing [Issues](https://github.com/Sonofrya/mage-duel-web-game/issues)
4. Create a new issue with logs and system info

---

**Happy gaming! 🎮✨**
