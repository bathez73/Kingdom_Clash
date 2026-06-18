# Kingdom Clash Frontend

This folder contains an independent Vue 3 SPA for the Kingdom Clash API.

## Setup

1. Install dependencies:
   ```bash
   cd frontend
   npm install
   ```

2. Run the development server:
   ```bash
   npm run dev
   ```

3. Open the app at the URL shown by Vite (default: http://localhost:5173).

## API Configuration

The frontend reads the backend API URL from `VITE_API_BASE_URL` in `.env`.

Example:

```env
VITE_API_BASE_URL=http://127.0.0.1:8000/api
```

## Notes

- This app is intentionally decoupled from the Laravel backend.
- Authentication uses a bearer token saved in `localStorage`.
- Routes are protected using Vue Router navigation guards.
