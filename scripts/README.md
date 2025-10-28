# Operational Scripts

Production-ready operational scripts for Travian-Solo.

## 📋 Available Scripts

### **backup-databases.sh**
Backs up all databases (global + world databases) with compression and rotation.

**Features:**
- Backs up: travian_global, travian_testworld, travian_demo
- Gzip compression
- 7-day retention (configurable)
- Single-transaction backups (no downtime)
- Includes routines, triggers, and events

**Usage:**
```bash
# Run manually
./scripts/backup-databases.sh

# Schedule with cron (daily at 2 AM)
0 2 * * * /path/to/scripts/backup-databases.sh >> /var/log/backup.log 2>&1
```

**Output:**
```
Backups saved to: /var/www/html/backups/databases/
Format: {database}_{timestamp}.sql.gz
Example: travian_global_20251028_020000.sql.gz
```

---

### **health-check.sh**
Comprehensive health check for all services.

**Checks:**
- Docker containers (MySQL, Redis, PHP, Nginx)
- Database connectivity (all 3 databases)
- Redis connectivity and memory
- Nginx configuration
- HTTP response
- Disk usage (<80%)
- Memory usage (<90%)

**Usage:**
```bash
# Run manually
./scripts/health-check.sh

# Schedule with cron (every 15 minutes)
*/15 * * * * /path/to/scripts/health-check.sh || /path/to/alert-script.sh
```

**Exit Codes:**
- 0: All checks passed
- 1: One or more checks failed

---

### **db-maintenance.sh**
Database optimization and maintenance.

**Tasks:**
- Optimizes all tables
- Analyzes and updates statistics
- Cleans old session data (7+ days)
- Shows database sizes

**Usage:**
```bash
# Run manually
./scripts/db-maintenance.sh

# Schedule with cron (weekly on Sunday at 3 AM)
0 3 * * 0 /path/to/scripts/db-maintenance.sh >> /var/log/maintenance.log 2>&1
```

**Recommended Schedule:**
- Development: Weekly
- Production: Daily or weekly depending on load

---

## 🚀 Quick Start

### **1. Make scripts executable:**
```bash
chmod +x scripts/*.sh
```

### **2. Test each script:**
```bash
# Test backup
docker-compose exec php bash /var/www/html/scripts/backup-databases.sh

# Test health check
docker-compose exec php bash /var/www/html/scripts/health-check.sh

# Test maintenance
docker-compose exec php bash /var/www/html/scripts/db-maintenance.sh
```

### **3. Set up cron jobs:**
```bash
# Edit crontab
crontab -e

# Add these lines:
0 2 * * * /path/to/scripts/backup-databases.sh >> /var/log/backup.log 2>&1
*/15 * * * * /path/to/scripts/health-check.sh || /path/to/alert-script.sh
0 3 * * 0 /path/to/scripts/db-maintenance.sh >> /var/log/maintenance.log 2>&1
```

---

## 📊 Monitoring Integration

### **Health Check Integration:**

**With Monitoring Tools:**
```bash
# Prometheus/Grafana
*/1 * * * * /path/to/scripts/health-check.sh && curl -X POST http://pushgateway/metrics/job/health

# Uptime Kuma
*/5 * * * * /path/to/scripts/health-check.sh && curl http://uptime-kuma/api/push/ABC123
```

**Email Alerts:**
```bash
#!/bin/bash
if ! /path/to/scripts/health-check.sh; then
    echo "Health check failed" | mail -s "Alert: Travian Health Check Failed" admin@example.com
fi
```

---

## 🔧 Configuration

### **Environment Variables:**

All scripts use environment variables from `.env`:

```env
DB_HOST=mysql
DB_PORT=3306
DB_USERNAME=travian_user
DB_PASSWORD=travian_password123
```

### **Customize Retention:**

Edit `backup-databases.sh`:
```bash
RETENTION_DAYS=30  # Keep backups for 30 days
```

### **Customize Backup Location:**

Edit `backup-databases.sh`:
```bash
BACKUP_DIR="/mnt/backups/databases"
```

---

## 📝 Logs

### **Recommended Log Locations:**

```bash
/var/log/travian/backup.log
/var/log/travian/health.log
/var/log/travian/maintenance.log
```

### **Log Rotation:**

Create `/etc/logrotate.d/travian`:
```
/var/log/travian/*.log {
    daily
    rotate 14
    compress
    delaycompress
    notifempty
    create 0640 www-data www-data
    sharedscripts
}
```

---

## 🔐 Security

### **File Permissions:**

```bash
chmod 750 scripts/*.sh
chown root:www-data scripts/*.sh
```

### **Database Credentials:**

- Never hardcode passwords in scripts
- Use environment variables or .env files
- Restrict .env file permissions: `chmod 600 .env`
- Never commit .env to git

---

## 🧪 Testing

### **Test Backup:**
```bash
# Run backup
./scripts/backup-databases.sh

# Verify backup file exists
ls -lh backups/databases/

# Test restore
gunzip < backups/databases/travian_global_*.sql.gz | mysql -u root -p travian_global_test
```

### **Test Health Check:**
```bash
# Should pass
./scripts/health-check.sh
echo $?  # Should be 0

# Stop a service to test failure
docker-compose stop redis
./scripts/health-check.sh
echo $?  # Should be 1

# Restart service
docker-compose start redis
```

---

## 📚 Additional Resources

- [Backup Best Practices](../docs/backup-strategy.md)
- [Monitoring Setup](../docs/monitoring.md)
- [Disaster Recovery](../docs/disaster-recovery.md)

---

## 🎯 Production Checklist

- [ ] All scripts tested
- [ ] Cron jobs configured
- [ ] Log rotation set up
- [ ] Backup retention configured
- [ ] Health check alerts configured
- [ ] Monitoring integration complete
- [ ] Backup restore tested
- [ ] Documentation reviewed

---

**All scripts are production-ready!** ✅
