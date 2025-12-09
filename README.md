# 🚖 **BroTracks – Smart School Transport Tracking System**

*A secure fleet & student ride management platform built using PHP, MySQL, JavaScript and Bootstrap.*

---

## 📌 **Overview**

**BroTracks** is a complete school transportation management system designed for:

✔ **Parents** – Track rides, monitor live driver GPS, pay fees, manage children
✔ **Drivers** – Accept rides, live tracking, route updates, notifications
✔ *Admin* – Manage drivers, parents, rides, payments, alerts, reports

This system ensures **real-time visibility, safer school transport, and full automation** of ride scheduling.

---

## 🏗️ **Tech Stack**

| Layer              | Technology                                        |
| ------------------ | ------------------------------------------------- |
| **Frontend**       | HTML, CSS, JavaScript, Bootstrap 5                |
| **Backend**        | PHP (Core PHP, PDO, secure sessions)              |
| **Database**       | MySQL                                             |
| **APIs**           | Custom PHP APIs for GPS updates & ride operations |
| **Authentication** | Secure password hashing (`password_hash`)         |
| **UI/UX**          | Modern Glass UI + Dark Mode + 3D Interactions     |

---

## 📁 **Project Directory Structure**

```
BroTracks/
│── admin/
│   ├── add_notification.php
│   ├── dashboard.php
│   ├── drivers.php
│   ├── parents.php
│   ├── reports.php
│   ├── rides.php
│   └── settings.php
│
│── auth/
│   ├── forgot-password.php
│   ├── login.php
│   ├── logout.php
│   └── register.php
│
│── config/
│   └── db.php
│
│── driver/
│   ├── accept_ride.php
│   ├── dashboard.php
│   ├── notifications.php
│   ├── rides.php
│   ├── update_location.php
│   ├── update_location_api.php
│   └── fetch_driver_location.php
│
│── parent/
│   ├── add_child.php
│   ├── book_ride.php
│   ├── dashboard.php
│   ├── fetch_driver_location.php
│   ├── live_tracking.php
│   ├── pay.php
│   ├── plans.php
│   ├── recurring_ride.php
│   ├── rides.php
│   └── view_ride.php
│
│── public/
│   ├── css/
│   │   └── style.css
│   └── js/
│       └── app.js
│
│── brotracks.sql
│── index.php
```

---

## 🚀 **Core Features**

### 👨‍💼 Admin Panel

* View total drivers, parents, live rides & pending approvals
* Approve/Reject driver accounts
* Generate monthly ride & payment reports
* Send system notifications
* Manage parent & driver database
* Real-time ride monitoring
* Clean dashboard UI with analytics cards

---

### 🚗 Driver Portal

* Accept / Reject rides
* View assigned rides
* Live GPS sharing (auto-update every 10 seconds)
* Receive parent/admin notifications
* Update ride status (Ongoing → Completed)
* Beautiful UI optimized for mobile use

---

### 👨‍👧 Parent Portal

* Add child profiles
* Book one-time ride or recurring ride
* Track driver live location on map
* View previous rides
* View ride details + driver info
* Payment + subscription plan selection
* Notifications for ride start/end

---

### 🗺️ **Live GPS Tracking System**

Includes:

* `update_location_api.php` → Driver sends current GPS
* `fetch_driver_location.php` → Parent fetches updated map position

Drivers update GPS automatically via JavaScript every few seconds.

---

### 🔐 **Security Features**

* Password hashing (`password_hash`)
* Session protection (`session_regenerate_id`)
* Secure role-based redirects
* SQL injection-safe queries (PDO Prepared Statements)
* Admin-only protected endpoints

---

## 🛠️ **Installation Guide**

### 1️⃣ Clone the Repository

```
git clone https://github.com/K-PranavEswar/brotracks.git
```

### 2️⃣ Move into XAMPP

Place folder inside:

```
C:\xampp\htdocs\BroTracks
```

### 3️⃣ Import Database

* Open **phpMyAdmin**
* Create database → **brotracks**
* Import → `brotracks.sql`

### 4️⃣ Configure DB

Edit:

```
config/db.php
```

Update:

```php
$host = "localhost";
$dbname = "brotracks";
$username = "root";
$password = "";
```

### 5️⃣ Run Project

Open browser:

```
http://localhost/BroTracks/
```

---

## 🔑 Default Admin Login

| Field    | Value                                                 |
| -------- | ----------------------------------------------------- |
| Email    | **[admin@brotracks.com](mailto:admin@brotracks.com)** |
| Password | **admin123**                                          |

---

## 📌 Git Commands Cheat Sheet

```
git init
git add .
git commit -m "Initial commit"
git branch -M main
git remote add origin https://github.com/K-PranavEswar/brotracks.git
git push -u origin main
```

---

## 📸 Screenshots

✔ Modern Admin Dashboard
✔ Driver Live Tracking
✔ Parent Ride Booking UI
✔ Authentication System

*(Add real screenshots here on GitHub)*

---

## 🧩 Future Improvements

* Mobile App (Flutter/React Native)
* AI Route Optimization
* Push Notifications (FCM)
* SOS/Panic Button
* Face Recognition Attendance

---

## 🧑‍💻 Developer

**Pranav Eswar**
GitHub: [https://github.com/K-PranavEswar](https://github.com/K-PranavEswar)

