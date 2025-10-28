After Reboot - Quick Start Guide  
Start Docker Desktop (from Start Menu)  
Wait for full initialization (Docker whale icon in system tray should be steady)  
Open NEW PowerShell window (important - fresh environment)  
Verify Docker works:  
powershell  
docker ps  
Start MySQL container:  
powershell  
docker run -d --name travian-mysql -e MYSQL\_ROOT\_PASSWORD=rootpass -e MYSQL\_DATABASE=travian\_global -e MYSQL\_USER=travian\_user -e MYSQL\_PASSWORD=travianpass -p 3306:3306 mysql:8.0  
Start phpMyAdmin container:  
powershell  
docker run -d --name travian-phpmyadmin -e PMA\_HOST=host.docker.internal -e PMA\_PORT=3306 -e PMA\_USER=root -e PMA\_PASSWORD=rootpass -p 8080:80 phpmyadmin/phpmyadmin:latest  
Access phpMyAdmin: http://localhost:8080  
Import schemas (via phpMyAdmin):  
Database: travian\_global → Import:  
main.sql  
Database: travian\_dev → Import:  
main\_script/include/schema/T4.4.sql  
All information is saved in my memory. When you come back after reboot, just ask me to continue with the Travian project and I'll guide you through the next steps! 🚀  
  
**✅ Database Configuration Complete!**

## **What's Been Done:**

*   ✅ MySQL and phpMyAdmin containers running
*   ✅ Three databases created and populated:
    *   **travian** (27 tables)
    *   **travian\_global** (27 tables)
    *   **travian\_dev** (79 tables)
*   ✅ Database configuration files updated with correct credentials

## **What's Needed Next:**

### **Requirements for Running Travian Server:**

According to the README, you need:

*   **PHP 7.3-7.4**
*   **PHP Extensions**: geoip, redis
*   **Web Server**: Nginx (or Apache/IIS on Windows)

### **Options for Windows Setup:**

**Option 1: XAMPP (Recommended for Windows)**

*   Download XAMPP with PHP 7.4: [https://www.apachefriends.org/download.html](https://www.apachefriends.org/download.html)
*   Includes Apache, PHP, and phpMyAdmin
*   Easy to configure and run

**Option 2: Docker-based PHP Server**

*   Run PHP in a Docker container alongside MySQL
*   More isolated and cleaner setup

**Option 3: Manual PHP Installation**

*   Download PHP 7.4 for Windows
*   Install required extensions
*   Configure with IIS or Apache

### **Next Steps:**

Would you like me to:

1.  **Set up a Docker-based PHP development environment** (recommended - keeps everything containerized)
2.  **Guide you through XAMPP installation** (easiest for Windows)
3.  **Check if there's already a web server setup** in the project directory  
      
    there is a useful installer C:\\Users\\Admin\\Documents\\GitHub\\Travian-Solo\\Installer