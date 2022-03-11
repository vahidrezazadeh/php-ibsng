# PHP IBSNG

IBSNG Library For Php.
## Installation

Copy IBSng.php in Your Project And Use.


## Usage And Example

```php

$ip = 'IBSNG IP';
$username = 'Admin Username in IBSNG';
$password = 'Admin Password in IBSNG';

$ibsngClient = new IBSng($username , $password , $ip);

// Login
$ibsngClient ->doLogin($username , $password);

// Check Exist User (return user Id if Exist)
$ibsngClient ->userExist('username');

// Add User
$ibsngClient ->addUser('group_name','username','pass');

// addUid
$ibsngClient ->addUid('group name');

// Charge User Account
$ibsngClient ->chargeUser('group_name','username','pass');

// Get User Info
$ibsngClient ->GetUserInfo('username');

```

## 🔗 Links

[![linkedin](https://img.shields.io/badge/linkedin-0A66C2?style=for-the-badge&logo=linkedin&logoColor=white)](https://www.linkedin.com/in/vahidrezazadeh/)
[![twitter](https://img.shields.io/badge/twitter-1DA1F2?style=for-the-badge&logo=twitter&logoColor=white)](https://twitter.com/vahidrezazadeh5/)
