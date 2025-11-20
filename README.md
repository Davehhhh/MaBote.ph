# mabote_ph

A new Flutter project.

## Backend configuration (XAMPP)

1. Place your PHP API under `htdocs/mabote_php_api` in XAMPP.
   - Expected endpoints:
     - `POST /login.php` → accepts JSON `{ email, password }` and returns `{ success: true, token, message }`.
     - `POST /signup.php` → accepts JSON `{ first_name, last_name, email, password }` and returns `{ success: true, message }`.
2. For Android emulator, Flutter uses `http://10.0.2.2` to reach your host machine `localhost`.
   - For a physical Android device on same Wi‑Fi, use your PC IPv4, e.g. `http://192.168.1.10`.
   - iOS Simulator uses `http://127.0.0.1` for host.

### Configure base URL

`AuthService` reads the base URL from a compile-time environment variable `API_BASE_URL` and defaults to `http://10.0.2.2/mabote_php_api`.

Example run command:

```
flutter run --dart-define=API_BASE_URL=http://192.168.1.10/mabote_php_api
```


## Getting Started

This project is a starting point for a Flutter application.

A few resources to get you started if this is your first Flutter project:

- [Lab: Write your first Flutter app](https://docs.flutter.dev/get-started/codelab)
- [Cookbook: Useful Flutter samples](https://docs.flutter.dev/cookbook)

For help getting started with Flutter development, view the
[online documentation](https://docs.flutter.dev/), which offers tutorials,
samples, guidance on mobile development, and a full API reference.
