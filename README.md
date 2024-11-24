# App Creator

A web-based platform that allows users to create Android applications without coding knowledge. This tool provides an intuitive interface for building and customizing mobile applications, generating APK files, and managing user projects.

## Features

- User authentication and project management
- Custom app creation interface
- APK generation system
- Project download functionality
- User dashboard for project management
- Secure file handling and storage

## Requirements

- PHP 7.4 or higher
- MySQL Database
- XAMPP/Apache Server
- Android SDK/Build Tools
- Kotlin compiler

## Project Structure

```
appbuilder/
├── api/
│   └── auth.php          # Authentication API endpoints
├── classes/
│   └── Auth.php          # Authentication class implementation
├── config/
│   ├── database.php      # Database configuration
│   └── user_apps.sql     # Database schema
├── uploads/              # Directory for uploaded files
├── user_projects/        # User project storage
├── users/                # User-specific data
├── build.php            # Build system implementation
├── dashboard.php        # User dashboard interface
├── download.php         # File download handler
├── generate_apk.php     # APK generation system
├── index.php           # Main entry point
├── login.php           # User authentication interface
└── setup_kotlin.php    # Kotlin environment setup
```

## Installation

1. Clone the repository:
```bash
git clone https://github.com/pimanlee/appcreator.git
```

2. Configure your XAMPP environment:
   - Place the project in the htdocs directory
   - Ensure Apache and MySQL services are running

3. Set up the database:
   - Import the `config/user_apps.sql` file into your MySQL server
   - Update database credentials in `config/database.php`

4. Configure Android SDK and Kotlin compiler:
   - Install Android SDK and Build Tools
   - Set up Kotlin compiler
   - Update paths in `setup_kotlin.php` if necessary

5. Set appropriate permissions:
   - Ensure write permissions for uploads/, user_projects/, and users/ directories

## Usage

1. Access the application through your web browser
2. Create an account or log in
3. Use the dashboard to create and manage your apps
4. Customize your app using the provided interface
5. Generate and download your APK file

## Security Considerations

- All user inputs are sanitized and validated
- Secure file handling implementation
- Password hashing and secure session management
- Protected configuration files

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## License

This project is licensed under the MIT License - see the LICENSE file for details.
