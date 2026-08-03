import Echo from "laravel-echo";
import Pusher from "pusher-js";
import { apiClient } from "./apiClient.js";

window.Pusher = Pusher;

export const echo = new Echo({
  broadcaster: "reverb",
  key: import.meta.env.VITE_REVERB_APP_KEY,
  wsHost: import.meta.env.VITE_REVERB_HOST,
  wsPort: import.meta.env.VITE_REVERB_PORT,
  wssPort: import.meta.env.VITE_REVERB_PORT,
  forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? "http") === "https",
  enabledTransports: ["ws", "wss"],
  // This app uses Sanctum token auth (Bearer header), not SPA cookie
  // sessions, so private-channel authorization goes through apiClient
  // (which already attaches the token) instead of Echo's default
  // cookie-based fetch to /broadcasting/auth.
  authorizer: (channel) => ({
    authorize: (socketId, callback) => {
      apiClient
        .post("/broadcasting/auth", { socket_id: socketId, channel_name: channel.name })
        .then((response) => callback(false, response.data))
        .catch((error) => callback(true, error));
    },
  }),
});

// Lets the backend's broadcast(...)->toOthers() exclude this tab's own
// connection from echoing back its own sent messages.
apiClient.interceptors.request.use((config) => {
  const socketId = echo.socketId();
  if (socketId) {
    config.headers["X-Socket-Id"] = socketId;
  }
  return config;
});
