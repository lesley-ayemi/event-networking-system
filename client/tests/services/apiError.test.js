import { describe, it, expect } from "vitest";
import { getApiError } from "../../src/services/apiError.js";

describe("getApiError", () => {
  it("reads the backend's standard error envelope when present", () => {
    const error = {
      response: {
        status: 409,
        data: { success: false, message: "You are already registered for this event.", errorCode: "EVENT_ALREADY_REGISTERED" },
      },
    };

    expect(getApiError(error)).toEqual({
      message: "You are already registered for this event.",
      errorCode: "EVENT_ALREADY_REGISTERED",
      errors: null,
    });
  });

  it("includes field-level errors when the backend supplies them", () => {
    const error = {
      response: {
        status: 422,
        data: {
          success: false,
          message: "Please check the highlighted fields and try again.",
          errorCode: "VALIDATION_ERROR",
          errors: { email: ["The email field is required."] },
        },
      },
    };

    const result = getApiError(error);

    expect(result.errorCode).toBe("VALIDATION_ERROR");
    expect(result.errors).toEqual({ email: ["The email field is required."] });
  });

  it("falls back to UNKNOWN_ERROR when errorCode is missing from the envelope", () => {
    const error = { response: { data: { success: false, message: "Something specific failed." } } };

    expect(getApiError(error).errorCode).toBe("UNKNOWN_ERROR");
  });

  it("uses the caller's fallback message when the response body isn't the standard envelope", () => {
    const error = { response: { status: 500, data: "<html>Internal Server Error</html>" } };

    const result = getApiError(error, "We couldn't load your matches right now. Please try again.");

    expect(result.message).toBe("We couldn't load your matches right now. Please try again.");
    expect(result.errorCode).toBe("UNKNOWN_ERROR");
  });

  it("reports a calm network-specific message when the server never responded", () => {
    const error = { request: {} };

    const result = getApiError(error, "This fallback should not be used.");

    expect(result.errorCode).toBe("NETWORK_ERROR");
    expect(result.message).toMatch(/trouble reaching the server/i);
  });

  it("uses the default calm message when no fallback is given and nothing else matches", () => {
    const result = getApiError(new Error("boom"));

    expect(result.message).toMatch(/couldn't complete that action/i);
    expect(result.errorCode).toBe("UNKNOWN_ERROR");
  });

  it("uses a caller-provided fallback for a plain rejection with no response or request", () => {
    const result = getApiError(new Error("boom"), "We couldn't load this conversation.");

    expect(result.message).toBe("We couldn't load this conversation.");
  });
});
