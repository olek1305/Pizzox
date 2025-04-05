## About: Fast service pizza with Mapbox feature with delivery, payment.

\
Task:
- Better look web
- Full Translation PL/ENG
- Add seeders (pizzas, coupons, additions, deliveryDrivers etc. )
- Add payment Stripe, using with promotions and blik.
- Mapbox
- Tests
- CI/CD

\
Complete:
- Added Redis
- Added payment Stripe, promotions


Create an admin account using the command:
- php bin/console app:create-admin

Mongo Express:
- http://localhost:8081/
- Login: admin
- Password: pass

Command to generate a translation:
- docker-compose exec --user appuser app php bin/console assets:install
