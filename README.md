
# GoodGeek Fake Data Module

The **GoodGeek Fake Data** module for Magento 2 is designed to anonymize customer and order data by replacing sensitive information with random values using the `fzaninotto/faker` library. It's ideal for development, testing, and demonstration purposes, allowing you to work safely without risking data exposure.

## Key Features
- **Data Anonymization**: Anonymizes customer profiles, addresses, orders, invoices, and shipping information.
- **Support for Both Registered and Guest Users**: Handles data for both customer types seamlessly.
- **Automated Reindexing**: Ensures data remains up-to-date and consistent after anonymization.

## Install module via composer
Use composer command

```bash
composer require good-geek/module-fake-data
```

## Important: Database Backup
Before using the **GoodGeek Fake Data** module, it is crucial to **back up your database** to prevent potential data loss. Here is a simple command to create a database backup:

```bash
mysqldump -u [username] -p[password] [database_name] > backup_filename.sql
```

Replace `[username]`, `[password]`, and `[database_name]` with your database credentials and desired backup file name.

## How to Use
To start the data anonymization process, run the following command in your Magento 2 console:
```bash
php bin/magento goodgeek:fakedata:anonymize
```

This will initiate the anonymization process and provide a detailed log of changes in the console.

### Contact and Support
For improvements and customization, please contact: [My page](https://goodgeek.cc) or [Telegram](https://t.me/sarootua).

<div align="center">
  <strong>If you find this module useful and would like to buy me a beer, just click the donate button below. Thank you!</strong><br/>
  <a href="https://send.monobank.ua/jar/4Zii5ZQYfg" target="_blank">
    <img src="https://community-openhab-org.s3.dualstack.eu-central-1.amazonaws.com/original/3X/5/3/539d11b64d92dc34977fd4f07e10bb1c4fc86edc.png" alt="Buy Me a Beer" style="width: 100px; height: 100px;" />
  </a>
</div>

## About the Module
The **GoodGeek Fake Data** module is a reliable solution for secure testing and development in Magento 2, protecting sensitive information while allowing full functionality in your development environment.
