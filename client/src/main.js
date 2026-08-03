import { createApp } from "vue";
import { createPinia } from "pinia";
import "./style.css";
import App from "./App.vue";
import { router } from "./router/index.js";
import { useAuthStore } from "./stores/authStore.js";

const app = createApp(App);
app.use(createPinia());

// Vue Router triggers its initial navigation synchronously inside app.use(),
// which would evaluate the auth guard before restoreSession()'s network
// request resolves — always losing that race and getting stuck on /login
// even once the session is actually restored. Installing the router only
// after the session is known avoids it.
useAuthStore()
  .restoreSession()
  .finally(() => {
    app.use(router);
    app.mount("#app");
  });
