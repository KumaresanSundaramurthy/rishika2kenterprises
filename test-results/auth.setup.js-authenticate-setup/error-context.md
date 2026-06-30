# Instructions

- Following Playwright test failed.
- Explain why, be concise, respect Playwright best practices.
- Provide a snippet of code with the fix, if possible.

# Test info

- Name: auth.setup.js >> authenticate
- Location: tests\auth.setup.js:5:1

# Error details

```
Test timeout of 30000ms exceeded.
```

```
Error: page.waitForURL: Test timeout of 30000ms exceeded.
=========================== logs ===========================
waiting for navigation to "**/dashboard" until "load"
  navigated to "http://localhost:8080/portal"
============================================================
```

# Page snapshot

```yaml
- generic [ref=e2]:
  - generic [ref=e4]:
    - img "R2K Enterprises" [ref=e6]
    - generic [ref=e7]:
      - generic [ref=e8]: RISHIKA 2K
      - generic [ref=e9]: ENTERPRISES
    - paragraph [ref=e10]: Agricultural Machinery · Tamil Nadu
    - generic [ref=e11]:
      - generic [ref=e15]:
        - strong [ref=e16]: Secure Billing
        - text: End-to-end encrypted transactions
      - generic [ref=e20]:
        - strong [ref=e21]: Instant Invoicing
        - text: Generate & share invoices in seconds
      - generic [ref=e25]:
        - strong [ref=e26]: Smart Reports
        - text: Real-time sales & inventory insights
    - generic [ref=e27]: Authorized Dealer — Rotoking & Bharat Baler
  - generic [ref=e30]:
    - generic [ref=e31]:
      - heading "Welcome back" [level=3] [ref=e32]
      - paragraph [ref=e33]: Sign in to manage your billing operations
    - generic [ref=e34]:
      - alert [ref=e36]:
        - text: Oops! User Account not found.
        - button "Close" [ref=e37] [cursor=pointer]
      - generic [ref=e38]:
        - generic [ref=e39]: Username or Email
        - textbox "Username or Email" [active] [ref=e41]:
          - /placeholder: Enter your username
      - generic [ref=e42]:
        - generic [ref=e43]: Password
        - generic [ref=e44]:
          - textbox "Password" [ref=e45]:
            - /placeholder: Enter your password
          - button "Toggle password visibility" [ref=e46] [cursor=pointer]
      - generic [ref=e48]:
        - generic [ref=e49] [cursor=pointer]:
          - checkbox "Remember me" [ref=e50]
          - generic [ref=e51]: Remember me
        - link "Forgot password?" [ref=e52] [cursor=pointer]:
          - /url: /forgot-password
      - button "Sign In" [ref=e53] [cursor=pointer]:
        - generic [ref=e54]: Sign In
    - generic [ref=e57]: or continue with
    - generic [ref=e58]:
      - link "Continue with Google" [ref=e59] [cursor=pointer]:
        - /url: /auth/google
        - img [ref=e60]
        - text: Continue with Google
      - link "Continue with Facebook" [ref=e65] [cursor=pointer]:
        - /url: /auth/facebook
        - img [ref=e66]
        - text: Continue with Facebook
    - paragraph [ref=e68]: © 2026 R2K Enterprises. All rights reserved.
```

# Test source

```ts
  1  | import { test as setup, expect } from '@playwright/test';
  2  | 
  3  | const authFile = 'playwright/.auth/user.json';
  4  | 
  5  | setup('authenticate', async ({ page }) => {
  6  |   // 1. Go to the login page (appended to baseURL in playwright.config.js)
  7  |   await page.goto('/portal');
  8  | 
  9  |   // 2. Read test user credentials from environment variables
  10 |   const email = process.env.TEST_USER_EMAIL;
  11 |   const password = process.env.TEST_USER_PASSWORD;
  12 | 
  13 |   if (!email || !password) {
  14 |     throw new Error('TEST_USER_EMAIL and TEST_USER_PASSWORD must be defined in your .env file');
  15 |   }
  16 | 
  17 |   // 3. Fill in the login form fields
  18 |   await page.fill('#UserName', email);
  19 |   await page.fill('#UserPassword', password);
  20 | 
  21 |   // 4. Click the "Sign In" button and wait for it to process
  22 |   await page.click('#lrSubmit');
  23 | 
  24 |   // 5. Wait for the URL to change to the dashboard, indicating successful login
> 25 |   await page.waitForURL('**/dashboard');
     |              ^ Error: page.waitForURL: Test timeout of 30000ms exceeded.
  26 | 
  27 |   // 6. Double check that the dashboard loads by checking for a header or sidebar element
  28 |   await expect(page).toHaveTitle(/Dashboard/i);
  29 | 
  30 |   // 7. Save the login state (cookies/local storage) to a file for reuse in E2E tests
  31 |   await page.context().storageState({ path: authFile });
  32 | });
  33 | 
```