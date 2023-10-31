# Flatapp
Flatapp is an app made for managing flat renting.
![dashboard](screens/dashboard.png)
## Technologies
+ PHP - Symfony
+ CSS - Bootstrap
+ jQuery
+ Docker - Docker-compose
+ MariaDB
+ Nginx
+ PHPUnit
+ [Ratchet](https://github.com/ratchetphp/Ratchet)
## Features
+ Registering as Tenant or Landlord
  ![Registering](screens/register.png)
+ Adding flats - multi-step form
  ![step1](screens/step1.png)
  ![step2](screens/step2.png)
  ![step3](screens/step3.png)
  ![step4](screens/step4.png)
  ![step5](screens/step5.png)
+ Inviting new tenants with invitation code (in Base58)
  ![Invitation code](screens/invitation.png)
+ Live chat for all related users (for example, if John is Andrew's tenant, they can send messages to each other, and so can Andrew to all of his tenants)
  ![Chat](screens/chat.png)
+ Managing profile
![Profile](screens/profile.png)
+ Adding new utility meters readings (as tenant)
  ![Utility meters reading](screens/adding-utility.png)
+ Adding utility meters costs (as landlord)
![Utility meters reading](screens/adding-utility-cost.png)
![Utility meters reading](screens/adding-utility-submit.png)
+ Adding new specialists
  ![Adding specialist](screens/adding-specialist.png)
![Specialist submit](screens/adding-specialist-submit.png)
+ Flat summary
  ![Flat summary](screens/flat-summary.png)
+ Adding tasks to small Todo-app on dashboard (stored in DB, can be sorted and marked as done)
  ![Tasks](screens/tasks.png)
## Testing
Tests can be found at tests/, it is recommended to run them with -d parameter:
```
php bin/phpunit -d memory_limit=-1
```