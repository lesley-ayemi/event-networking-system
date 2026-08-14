import Echo from "laravel-echo";
import Pusher from "pusher-js";
import { apiClient } from "./apiClient.js";

const appKey = import.meta.env.VITE_REVERB_APP_KEY;

// Pusher throws on construction if the key is missing, and this module is
// imported at app boot (main.js) and by conversationsStore. Without this
// guard a checkout with no client/.env takes down the whole app, and the
// test run with it. Real-time delivery is the only thing that degrades:
// messages still send and still arrive, they just wait for a refetch.
function createNullEcho() {
  const channel = { listen: () => channel, stopListening: () => channel };

  return {
    private: () => channel,
    channel: () => channel,
    leave: () => {},
    leaveChannel: () => {},
    socketId: () => null,
  };
}

let echo;

if (appKey) {
  window.Pusher = Pusher;

  echo = new Echo({
    broadcaster: "reverb",
    key: appKey,
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
} else {
  echo = createNullEcho();

  if (!import.meta.env.TEST) {
    console.warn(
      "VITE_REVERB_APP_KEY is not set, so live messaging is off. " +
        "Copy client/.env.example to client/.env to enable it."
    );
  }
}

export { echo };

// Lets the backend's broadcast(...)->toOthers() exclude this tab's own
// connection from echoing back its own sent messages.
apiClient.interceptors.request.use((config) => {
  const socketId = echo.socketId();
  if (socketId) {
    config.headers["X-Socket-Id"] = socketId;
  }
  return config;
});
