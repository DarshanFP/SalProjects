# Login Error Message Discovery

**Objective:** Identify why the login page does not display an error message when the user ID is incorrect or the password is incorrect.

**Scope:** Discovery only. No code was modified.

---

## 1. Login Route

### GET login (login page)

- **`routes/web.php`** (lines 49–51): Defines `GET /login` with `->name('login')`, returning `view('auth.login')` via a closure (no controller). This route is registered **before** `require __DIR__.'/auth.php'`, so it takes precedence.
- **`routes/auth.php`** (lines 19–21): Inside `middleware('guest')`, defines `GET login` → `AuthenticatedSessionController@create` (also returns `view('auth.login')`). Because it is loaded after `web.php`, the **named route `login`** ultimately resolves to the first matching GET route, which is the one in `web.php`. The login **page** is therefore served by the closure in `web.php`; both paths render the same view `auth.login`.

### POST login (form submit)

- **`routes/auth.php`** (line 23): `Route::post('login', [AuthenticatedSessionController::class, 'store'])` (no route name). This is the only POST handler for `/login`.
- The login form uses `action="{{ route('login') }}"`, which resolves to the URL for the named route `login` (i.e. `/login`). Submitting the form sends **POST /login** to `AuthenticatedSessionController@store`.

**Summary:** GET `/` and GET `/login` show the login view; POST `/login` is handled by `AuthenticatedSessionController::store()`.

---

## 2. Controller Handling

- **Controller:** `App\Http\Controllers\Auth\AuthenticatedSessionController`
- **POST login method:** `store(LoginRequest $request): RedirectResponse`
- **Flow:**
  1. `$request->authenticate()` is called (validation and auth happen inside `LoginRequest`).
  2. If authentication fails, `LoginRequest::authenticate()` throws a `ValidationException` (see below). The controller’s success path (session regenerate, role-based redirect) is never reached.
  3. If authentication succeeds, session is regenerated and the user is redirected by role (e.g. `/admin/dashboard`, `/coordinator/dashboard`, etc.).

No custom failed-login handling exists in the controller; failure is entirely handled by the form request and Laravel’s `ValidationException` handling.

---

## 3. Validation Logic

- **Form request:** `App\Http\Requests\Auth\LoginRequest`
- **Rules:** `'login' => ['required', 'string'], 'password' => ['required', 'string']`
- **Authentication:** Done in `authenticate()` (not in rules):
  - User is resolved by `email`, `name`, `phone`, or `username` matching the `login` input.
  - If no user is found or `Hash::check($this->password, $user->password)` fails, the code calls:
    - `RateLimiter::hit($this->throttleKey());`
    - `throw ValidationException::withMessages(['login' => trans('auth.failed')]);`
  - So **all credential failures (wrong user id or wrong password) put the error on the `login` key** with the message from `auth.failed` (e.g. “These credentials do not match our records.”).
- **Rate limiting:** After 5 failed attempts, `ValidationException::withMessages(['login' => trans('auth.throttle', [...])])` is thrown. Again, the error is on the `login` key.

Laravel’s default handling of `ValidationException` in a web context is to redirect back (to the previous URL) and flash the errors into the session, so `$errors` is available on the next response (the login page).

---

## 4. Failed Login Handling

- **Where:** `App\Http\Requests\Auth\LoginRequest::authenticate()` (lines 50–56).
- **Behaviour:**
  - On invalid credentials: `throw ValidationException::withMessages(['login' => trans('auth.failed')]);`
  - On throttle: `throw ValidationException::withMessages(['login' => trans('auth.throttle', [...])]);`
- **No** `return back()->withErrors([...])` or `return redirect()->back()->with('error', ...)` in the controller; the redirect and error flashing are done by the framework when it catches `ValidationException`.
- **Result:** After a failed login, the user is redirected back to the previous page (e.g. `/` or `/login`) with the default error bag containing the key `login`. The view receives `$errors` with that key.

---

## 5. Blade Error Display Analysis

- **View file:** `resources/views/auth/login.blade.php`
- **Form:** `method="POST" action="{{ route('login') }}"`; fields: `login`, `password`, `remember`.
- **Session status:** Line 62: `<x-auth-session-status class="mb-4" :status="session('status')" />` — displays `session('status')` only, not validation errors.
- **Error display:**
  - **Login field (lines 69–72):** Only the label and input. **There is no `@error('login')`, no `$errors->get('login')`, and no `<x-input-error>` for the `login` field.**
  - **Password field (lines 75–84):** Has `<x-input-error :messages="$errors->get('password')" class="mt-2" />` (line 84). So only **password** validation errors are shown.
- **Component:** `resources/views/components/input-error.blade.php` renders a list of messages when `$messages` is non-empty; it is used only for `password` on the login page.

**Conclusion:** The application **does** redirect back with errors and the error bag **does** contain the key `login` for both “wrong user id” and “wrong password” (and for throttle). The login view **never renders** `$errors->get('login')` or any directive for the `login` key, so the user sees no message for failed login.

---

## 6. Identified Root Cause

**Root cause:** The login blade view does not display validation errors for the `login` field.

- Failed login (wrong user id or wrong password) is correctly turned into a `ValidationException` with message on the **`login`** key in `LoginRequest::authenticate()`.
- Laravel correctly redirects back and passes the error bag to the view.
- The view only shows errors for **`password`** via `<x-input-error :messages="$errors->get('password')" />`. There is no equivalent for **`login`**, so the user never sees “These credentials do not match our records.” or the throttle message.

**Secondary note:** The app uses a single `login` input (email/name/username/phone) and a single error key `login` for all credential failures, which is consistent; the only fix needed on the view is to display that key.

---

## 7. Recommended Minimal Fix

**Location:** `resources/views/auth/login.blade.php`

- Add error display for the **login** field so that messages under the `login` key are shown (e.g. “These credentials do not match our records.” and throttle message).
- **Minimal change:** After the login input (e.g. after the closing `</div>` of the “Login” block, around line 72), add the same pattern used for password, e.g.  
  `<x-input-error :messages="$errors->get('login')" class="mt-2" />`

This reuses the existing `x-input-error` component and keeps behaviour consistent with the password field. No controller or request changes are required; the backend already attaches the error to `login` and Laravel already flashes it to the view.

---

**Discovery completed.** No code was modified.
