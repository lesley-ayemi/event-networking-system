import { createApp } from "vue";
import { createPinia } from "pinia";
import "./style.css";
import App from "./App.vue";
import { router } from "./router/index.js";
import { useAuthStore } from "./stores/authStore.js";

const app = createApp(App);
app.use(createPinia());
app.use(router);

useAuthStore().restoreSession().finally(() => {
  app.mount("#app");
});
