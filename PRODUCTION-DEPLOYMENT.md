# Production Deployment Guide

## 🚀 **You're 98% Production Ready!**

This guide will help you deploy Travian-Solo to production.

---

## ✅ **Pre-Deployment Checklist**

### **Infrastructure:**
- [x] Docker environment configured
- [x] MySQL databases (3) with 186 tables total
- [x] Redis cache configured
- [x] Nginx reverse proxy
- [x] PHP-FPM with all extensions

### **Security:**
- [x] CSRF protection implemented
- [x] XSS prevention (input sanitization)
- [x] BCrypt password hashing
- [x] Rate limiting (Redis-backed)
- [x] Security headers (7 headers)
- [ ] **TODO:** Set Redis password (production)
- [ ] **TODO:** Enable HTTPS/SSL certificates
- [ ] **TODO:** Configure firewall rules

### **Functionality:**
- [x] User registration API
- [x] User login API
- [x] Account activation API
- [x] Email system (mock for dev, SMTP for prod)
- [x] Newsletter system
- [x] Multi-world support

### **Operations:**
- [x] Database backup scripts
- [x] Health check scripts
- [x] Maintenance scripts
- [ ] **TODO:** Set up cron jobs
- [ ] **TODO:** Configure monitoring
- [ ] **TODO:** Set up log rotation

---

## 📋 **Deployment Steps**

### **Step 1: Server Setup**

#### **Minimum Requirements:**
- **OS:** Ubuntu 20.04+ or Debian 11+
- **RAM:** 4GB minimum, 8GB recommended
- **Storage:** 50GB minimum
- **CPU:** 2 cores minimum, 4 cores recommended

#### **Install Docker:**
```bash
# Install Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh

# Install Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose

# Verify
docker --version
docker-compose --version
```

---

### **Step 2: Clone Repository**

```bash
# Clone your repository
git clone https://github.com/YOUR_USERNAME/Travian-Solo.git
cd Travian-Solo

# Create .env from example
cp .env.example .env
```

---

### **Step 3: Configure Environment**

#### **Edit .env file:**

```env
# Application
APP_URL=https://your-domain.com
APP_DEBUG=false  # IMPORTANT: Set to false for production
DOMAIN=your-domain.com

# Security
SECURE_HASH_SALT=CHANGE_TO_RANDOM_STRING_32_CHARS
SESSION_LIFETIME=86400
COOKIE_SECURE=true  # Only with HTTPS

# SMTP (Production Email)
SMTP_HOST=smtp.your-provider.com
SMTP_PORT=587
SMTP_USERNAME=your-smtp-username
SMTP_PASSWORD=your-smtp-password
SMTP_ENCRYPTION=tls
SMTP_FROM_ADDRESS=noreply@your-domain.com
SMTP_FROM_NAME=Your Game Name

# Redis (Production)
REDIS_HOST=redis
REDIS_PORT=6379
REDIS_PASSWORD=YOUR_STRONG_REDIS_PASSWORD_HERE

# ReCAPTCHA (Optional but recommended)
RECAPTCHA_SITE_KEY=your_recaptcha_site_key
RECAPTCHA_SECRET_KEY=your_recaptcha_secret_key

# MySQL
MYSQL_ROOT_PASSWORD=STRONG_ROOT_PASSWORD_HERE
MYSQL_PASSWORD=STRONG_USER_PASSWORD_HERE
```

---

### **Step 4: Redis Production Setup**

#### **Edit docker/redis/redis.conf:**

```conf
# Security
protected-mode yes
requirepass YOUR_STRONG_REDIS_PASSWORD_HERE
```

Make sure this matches `REDIS_PASSWORD` in `.env`.

---

### **Step 5: Enable Production Email**

#### **Edit sections/api/include/Api/Ctrl/RegisterCtrl.php:**

Replace `MockEmailService` with `EmailService`:

```php
// Change this:
use Core\MockEmailService;
MockEmailService::sendActivationMail(...);

// To this:
use Core\EmailService;
EmailService::sendActivationMail(...);
```

---

### **Step 6: Start Services**

```bash
# Build and start containers
docker-compose up -d --build

# Check status
docker-compose ps

# Check logs
docker-compose logs -f
```

---

### **Step 7: SSL/HTTPS Setup**

#### **Option A: Let's Encrypt (Recommended)**

```bash
# Install Certbot
sudo apt-get install certbot python3-certbot-nginx

# Get certificate
sudo certbot --nginx -d your-domain.com -d www.your-domain.com

# Auto-renewal
sudo certbot renew --dry-run
```

#### **Update docker/nginx/conf.d/default.conf:**

```nginx
server {
    listen 443 ssl http2;
    server_name your-domain.com www.your-domain.com;
    
    ssl_certificate /etc/letsencrypt/live/your-domain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/your-domain.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    
    # ... rest of config
}

# Redirect HTTP to HTTPS
server {
    listen 80;
    server_name your-domain.com www.your-domain.com;
    return 301 https://$server_name$request_uri;
}
```

---

### **Step 8: Set Up Operational Scripts**

```bash
# Make scripts executable
chmod +x scripts/*.sh

# Test scripts
docker-compose exec php bash /var/www/html/scripts/health-check.sh
docker-compose exec php bash /var/www/html/scripts/backup-databases.sh

# Set up cron jobs
crontab -e
```

Add these cron jobs:

```cron
# Database backups (daily at 2 AM)
0 2 * * * /path/to/scripts/backup-databases.sh >> /var/log/travian/backup.log 2>&1

# Health checks (every 15 minutes)
*/15 * * * * /path/to/scripts/health-check.sh || /path/to/alert-script.sh

# Database maintenance (weekly on Sunday at 3 AM)
0 3 * * 0 /path/to/scripts/db-maintenance.sh >> /var/log/travian/maintenance.log 2>&1
```

---

### **Step 9: Firewall Configuration**

```bash
# UFW (Ubuntu)
sudo ufw allow 22/tcp    # SSH
sudo ufw allow 80/tcp    # HTTP
sudo ufw allow 443/tcp   # HTTPS
sudo ufw enable

# Block direct database/Redis access from outside
sudo ufw deny 3306/tcp   # MySQL
sudo ufw deny 6379/tcp   # Redis
```

---

### **Step 10: Monitoring Setup** (Optional)

#### **Option A: Simple Uptime Monitoring**

Use services like:
- UptimeRobot (free)
- Pingdom
- Better Uptime

#### **Option B: Full Stack Monitoring**

Install Prometheus + Grafana:

```bash
# Add monitoring to docker-compose.yml
# See monitoring/docker-compose.monitoring.yml
```

---

## 🧪 **Post-Deployment Testing**

### **1. Health Check:**
```bash
curl https://your-domain.com
# Should return 200 OK

# Run health check
docker-compose exec php bash /var/www/html/scripts/health-check.sh
```

### **2. Test Registration:**
```bash
curl -X POST https://your-domain.com/v1/register/register \
  -H "Content-Type: application/json" \
  -d '{
    "gameWorld": 1,
    "username": "testuser",
    "email": "test@example.com",
    "password": "TestPass123!",
    "termsAndConditions": true,
    "lang": "en"
  }'
```

### **3. Test Email:**
- Register a user
- Check if activation email arrives
- Click activation link
- Verify user can log in

### **4. Test Rate Limiting:**
```bash
# Make 10 rapid requests
for i in {1..10}; do
  curl -X POST https://your-domain.com/v1/register/register \
    -H "Content-Type: application/json" \
    -d '{"gameWorld":1,"username":"test","email":"test@test.com","password":"pass","termsAndConditions":true,"lang":"en"}'
  echo ""
done

# Should see 429 Too Many Requests after limit
```

---

## 📊 **Monitoring Metrics**

### **Key Metrics to Monitor:**

1. **Server Health:**
   - CPU usage (<80%)
   - Memory usage (<85%)
   - Disk usage (<80%)

2. **Database:**
   - Query response time
   - Active connections
   - Slow queries
   - Table sizes

3. **Redis:**
   - Memory usage
   - Hit/miss ratio
   - Connected clients
   - Ops/sec

4. **Application:**
   - API response times
   - Error rates
   - Registration rate
   - Active users
   - Rate limit hits

---

## 🔐 **Security Hardening**

### **1. Database:**
```sql
-- Create separate user with limited privileges
CREATE USER 'travian_app'@'%' IDENTIFIED BY 'strong_password';
GRANT SELECT, INSERT, UPDATE, DELETE ON travian_global.* TO 'travian_app'@'%';
GRANT SELECT, INSERT, UPDATE, DELETE ON travian_testworld.* TO 'travian_app'@'%';
GRANT SELECT, INSERT, UPDATE, DELETE ON travian_demo.* TO 'travian_app'@'%';
FLUSH PRIVILEGES;
```

### **2. File Permissions:**
```bash
# Set proper ownership
sudo chown -R www-data:www-data /path/to/Travian-Solo

# Set proper permissions
sudo find /path/to/Travian-Solo -type d -exec chmod 755 {} \;
sudo find /path/to/Travian-Solo -type f -exec chmod 644 {} \;

# Protect sensitive files
chmod 600 .env
chmod 600 sections/globalConfig.php
```

### **3. Disable Unused Services:**
```bash
# Only expose necessary ports
docker-compose.yml: Only expose 80 and 443
# Don't expose MySQL (3306) or Redis (6379) to outside
```

---

## 🚨 **Troubleshooting**

### **Services Won't Start:**
```bash
# Check logs
docker-compose logs

# Check disk space
df -h

# Check memory
free -h

# Restart services
docker-compose restart
```

### **Database Connection Errors:**
```bash
# Check MySQL is running
docker-compose ps mysql

# Check credentials in .env
# Test connection
docker-compose exec mysql mysql -u travian_user -p
```

### **Email Not Sending:**
```bash
# Check SMTP settings in .env
# Test SMTP connection
telnet smtp.your-provider.com 587

# Check logs
docker-compose logs php
```

---

## 📚 **Additional Resources**

- [Security Best Practices](PHASE4-COMPLETE.md)
- [Redis Production Guide](REDIS-PRODUCTION-GUIDE.md)
- [Email Setup Guide](PHASE3-COMPLETE.md)
- [Operational Scripts](scripts/README.md)

---

## ✅ **Production Deployment Checklist**

- [ ] Server meets minimum requirements
- [ ] Docker and Docker Compose installed
- [ ] Repository cloned
- [ ] .env configured with production values
- [ ] Redis password set
- [ ] SMTP credentials configured
- [ ] SSL/HTTPS certificates installed
- [ ] Firewall configured
- [ ] Cron jobs set up
- [ ] Health checks passing
- [ ] Backups configured
- [ ] Monitoring set up
- [ ] Registration tested
- [ ] Email delivery tested
- [ ] Rate limiting tested
- [ ] Security audit completed
- [ ] Documentation reviewed

---

## 🎉 **You're Ready for Production!**

Once all checklist items are complete, your Travian-Solo instance is production-ready!

**Need help?** Check the troubleshooting section or review the documentation.

**Happy gaming!** 🎮
