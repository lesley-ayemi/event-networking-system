const DEFAULT_MESSAGE = "We couldn't complete that action. Your information has not been lost. Please try again.";
const NETWORK_MESSAGE = "We're having trouble reaching the server. Please check your connection and try again.";

/**
 * Normalizes any axios error into { message, errorCode, errors }, reading
 * the backend's {success, message, errorCode} envelope when present. Always
 * pass a context-specific fallbackMessage — the backend only supplies a
 * useful message for expected business-rule failures, not for network
 * failures or truly unexpected errors.
 */
export function getApiError(error, fallbackMessage = DEFAULT_MESSAGE) {
  const data = error?.response?.data;

  if (data && typeof data === "object" && data.success === false && data.message) {
    return {
      message: data.message,
      errorCode: data.errorCode ?? "UNKNOWN_ERROR",
      errors: data.errors ?? null,
    };
  }

  if (error?.response) {
    return { message: fallbackMessage, errorCode: "UNKNOWN_ERROR", errors: null };
  }

  if (error?.request) {
    return { message: NETWORK_MESSAGE, errorCode: "NETWORK_ERROR", errors: null };
  }

  return { message: fallbackMessage, errorCode: "UNKNOWN_ERROR", errors: null };
}
