# Wp-Attendence
WP Attendance Plugin is a custom-built WordPress admin-based employee attendance management system. It allows administrators to manage employees, record daily attendance, and track working hours directly from the WordPress dashboard.

This plugin is ideal for small teams and organizations that want a simple internal attendance tracking system without relying on third-party SaaS tools.

🚀 Features
👤 Employee Role Management

Automatically creates a custom Employee role on plugin activation

Add new employees directly from the admin panel

Dedicated employee listing page

🗂 Attendance Management

Mark employee attendance (Present / Absent)

Record Punch-In and Punch-Out times

Automatically calculate total working hours

Store attendance records in a custom database table

📊 Admin Dashboard

Custom admin menu with:

Dashboard overview

Add Employee

Employee List

Add Attendance

View Attendance

Clean card-based dashboard UI

WordPress-styled data tables

🔐 Security

Uses WordPress nonces for form security

Sanitizes and validates all input fields

Secure data insertion using $wpdb->insert()

🛠 Technical Overview

Built using WordPress Plugin API

Uses register_activation_hook() t
