[![Actions Status](https://github.com/FeltSussy/php-project-9/actions/workflows/hexlet-check.yml/badge.svg)](https://github.com/FeltSussy/php-project-9/actions)
[![CI](https://github.com/FeltSussy/php-project-9/actions/workflows/my-workflow.yml/badge.svg)](https://github.com/FeltSussy/php-project-9/actions/workflows/my-workflow.yml)
[![Quality gate status](https://sonarcloud.io/api/project_badges/measure?project=FeltSussy_php-project-9&metric=alert_status)](https://sonarcloud.io/summary/new_code?id=FeltSussy_php-project-9)
[![Bugs](https://sonarcloud.io/api/project_badges/measure?project=FeltSussy_php-project-9&metric=bugs)](https://sonarcloud.io/summary/new_code?id=FeltSussy_php-project-9)
[![Code Smells](https://sonarcloud.io/api/project_badges/measure?project=FeltSussy_php-project-9&metric=code_smells)](https://sonarcloud.io/summary/new_code?id=FeltSussy_php-project-9)
[![Coverage](https://sonarcloud.io/api/project_badges/measure?project=FeltSussy_php-project-9&metric=coverage)](https://sonarcloud.io/summary/new_code?id=FeltSussy_php-project-9)
[![Duplicated Lines (%)](https://sonarcloud.io/api/project_badges/measure?project=FeltSussy_php-project-9&metric=duplicated_lines_density)](https://sonarcloud.io/summary/new_code?id=FeltSussy_php-project-9)
[![Lines of Code](https://sonarcloud.io/api/project_badges/measure?project=FeltSussy_php-project-9&metric=ncloc)](https://sonarcloud.io/summary/new_code?id=FeltSussy_php-project-9)
[![Reliability Rating](https://sonarcloud.io/api/project_badges/measure?project=FeltSussy_php-project-9&metric=reliability_rating)](https://sonarcloud.io/summary/new_code?id=FeltSussy_php-project-9)
[![Security Rating](https://sonarcloud.io/api/project_badges/measure?project=FeltSussy_php-project-9&metric=security_rating)](https://sonarcloud.io/summary/new_code?id=FeltSussy_php-project-9)
[![Technical Debt](https://sonarcloud.io/api/project_badges/measure?project=FeltSussy_php-project-9&metric=sqale_index)](https://sonarcloud.io/summary/new_code?id=FeltSussy_php-project-9)
[![Maintainability Rating](https://sonarcloud.io/api/project_badges/measure?project=FeltSussy_php-project-9&metric=sqale_rating)](https://sonarcloud.io/summary/new_code?id=FeltSussy_php-project-9)
[![Vulnerabilities](https://sonarcloud.io/api/project_badges/measure?project=FeltSussy_php-project-9&metric=vulnerabilities)](https://sonarcloud.io/summary/new_code?id=FeltSussy_php-project-9)

## Page Analyzer

**Page Analyzer** is a web application for checking websites and extracting basic SEO-related information.

The application allows users to:

- add websites for analysis;
- perform page checks;
- store the history of checks;
- display HTTP status codes and extracted HTML metadata.

## Requirements

- PHP 8.4+
- Git
- Composer
- PostgreSQL

## Installation

Clone the repository:

```bash
git clone https://github.com/FeltSussy/php-project-9.git
cd php-project-9
```

Install PHP dependencies:

```bash
composer install
```

Create an environment file:

```bash
cp .env.example .env
```

Configure database connection:

```env
DATABASE_URL=pgsql://user:password@host:5432/database
```

Run database migrations:

```bash
make db-init
```

Start the application:

```bash
make start
```

## Demo

https://php-project-9-9grq.onrender.com
