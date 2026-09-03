# Mini Talabat API

A Laravel API for a small food delivery workflow with Sanctum authentication, role-based authorization, restaurant management, order processing, and admin-only checks.

## Authentication

- Public registration is available at `/api/v1/register`.
- Registration always creates a user with the `customer` role server-side.
- Clients cannot submit `role` during registration; it is rejected by validation.
- Login is at `/api/v1/login` and returns a Sanctum token.
- Logout is at `/api/v1/logout` and invalidates the current token.
- Authenticated routes use `auth:sanctum`.

## Roles

- `customer`
- `restaurant_owner`
- `admin`

Role values are kept as string constants on the `User` model and are assigned only on the server side.

## Customer permissions

- Manage only their own addresses.
- Create orders only for themselves.
- View only their own orders.
- Cancel only pending orders.
- Cannot create or manage restaurants.
- Cannot manage restaurant meals.
- Cannot perform restaurant-owner-only order status updates.

## Restaurant owner permissions

- Create restaurants.
- Update or delete only their own restaurants.
- Create, update, and delete meals only for restaurants they own.
- View orders only for their own restaurants.
- Transition order status only through valid business rules.
- Cannot access another owner’s restaurants, meals, or orders.

## Admin permissions

- Admin-only routes remain behind the `admin` middleware.
- Admin access is explicit and checked through the user role.
- The blanket `Gate::before` admin bypass is not used for normal authorization checks.
- Keep admin-only endpoints minimal and intentional.

## Order status flow

Current statuses are:

- `pending`
- `preparing`
- `on_the_way`
- `delivered`
- `cancelled`

Allowed transitions are enforced server-side:

- `pending -> preparing`
- `preparing -> on_the_way`
- `on_the_way -> delivered`
- `pending -> cancelled` (customer only)

## Public vs protected endpoints

Public:

- `GET /api/v1/ping`
- `POST /api/v1/register`
- `POST /api/v1/login`
- `GET /api/v1/restaurants`
- `GET /api/v1/restaurants/{restaurant}`
- `GET /api/v1/restaurants/{restaurant}/meals`
- `GET /api/v1/meals/{meal}`

Protected:

- `/api/v1/me`
- `/api/v1/logout`
- `/api/v1/restaurants` write routes
- `/api/v1/restaurants/{restaurant}/meals` write routes
- `/api/v1/orders`
- `/api/v1/addresses`
- `/api/v1/admin/overview` (admin-only)

## SMS configuration

SMS notifications use environment-based settings in `config/services.php`:

- `SMS_PROVIDER_URL`
- `SMS_API_KEY`
- `SMS_TIMEOUT`

Notifications are best-effort and do not fail order creation when the provider is unavailable.
# Order-App
# Order-App
